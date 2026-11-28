<?php
// Secrets belong in /home1/mdhiglob/mdhi-mail-config.php, outside public_html.
use PHPMailer\PHPMailer\PHPMailer;

function mdhi_respond($success, $message, $status = 200, $code = '') {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode(['success' => $success, 'message' => $message, 'code' => $code]);
    exit;
}

function mdhi_field($name) {
    return isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
}

function mdhi_unavailable($code) {
    error_log('[MDHI contact] ' . $code);
    mdhi_respond(false, 'Sending is currently unavailable. Please email info@mdhiglobal.com.', 503, $code);
}

// Account-private, locked rolling window. Store only times and HMAC-hashed IPs.
function mdhi_within_rate_limit($configPath, $secret) {
    $dir = dirname($configPath) . '/mdhi-contact-state';
    if (is_link($dir) || (!is_dir($dir) && !@mkdir($dir, 0700, true))) {
        throw new \RuntimeException('RATE_STORAGE_UNAVAILABLE');
    }
    $path = $dir . '/rate.json';
    if (is_link($path)) throw new \RuntimeException('RATE_STORAGE_UNAVAILABLE');
    $oldMask = umask(0077);
    $file = @fopen($path, 'c+');
    umask($oldMask);
    if (!$file) throw new \RuntimeException('RATE_STORAGE_UNAVAILABLE');
    try {
        if (!flock($file, LOCK_EX | LOCK_NB)) throw new \RuntimeException('RATE_STORAGE_BUSY');
        $raw = stream_get_contents($file, 1048577);
        if ($raw === false || strlen($raw) > 1048576) throw new \RuntimeException('RATE_STORAGE_INVALID');
        $events = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($events)) throw new \RuntimeException('RATE_STORAGE_INVALID');
        $now = time();
        $ip = hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), $secret);
        $retained = [];
        $recent = 0;
        foreach ($events as $event) {
            if (!is_array($event) || count($event) !== 2 || !isset($event[0], $event[1]) ||
                !is_int($event[0]) || !is_string($event[1])) throw new \RuntimeException('RATE_STORAGE_INVALID');
            if ($event[0] > $now - 3600) {
                $retained[] = $event;
                if ($event[1] === $ip && $event[0] > $now - 900) $recent++;
            }
        }
        // Count send attempts, including SMTP failures, to prevent automated retries.
        if ($recent >= 5 || count($retained) >= 100) return false;
        $retained[] = [$now, $ip];
        $payload = json_encode($retained);
        if ($payload === false || !rewind($file) || !ftruncate($file, 0) ||
            fwrite($file, $payload) !== strlen($payload) || !fflush($file)) {
            throw new \RuntimeException('RATE_STORAGE_WRITE_FAILED');
        }
        return true;
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    mdhi_respond(false, 'Please submit the enquiry form.', 405, 'METHOD_NOT_ALLOWED');
}

// Ordinary browser cross-site submissions are rejected without changing host WAF rules.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://mdhiglobal.com', 'https://www.mdhiglobal.com'], true)) {
    mdhi_respond(false, 'Please submit your enquiry from the MDHI contact page.', 403, 'ORIGIN_REJECTED');
}
if (isset($_POST['website']) && (!is_string($_POST['website']) || trim($_POST['website']) !== '')) {
    mdhi_respond(false, 'Your enquiry could not be accepted. Please email info@mdhiglobal.com.', 422, 'SPAM_FIELD');
}

$fields = [];
$limits = ['Field2' => 400, 'Field3' => 400, 'Field4' => 255, 'Field5' => 240,
    'Field6' => 400, 'Field8' => 400, 'Field16' => 4000];
foreach ($limits as $name => $limit) {
    $value = mdhi_field($name);
    if ($name !== 'Field5' && $value === '') {
        mdhi_respond(false, 'Please complete all required fields.', 422, 'INVALID_FIELDS');
    }
    if (strlen($value) > $limit) {
        mdhi_respond(false, 'Please shorten your enquiry or contact details.', 422, 'INVALID_FIELDS');
    }
    $fields[$name] = $value;
}
if (!filter_var($fields['Field4'], FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $fields['Field4'])) {
    mdhi_respond(false, 'Please enter a valid email address.', 422, 'INVALID_EMAIL');
}
if (preg_match('/[\r\n]/', $fields['Field2'] . $fields['Field3'])) {
    mdhi_respond(false, 'Please check your name fields.', 422, 'INVALID_FIELDS');
}
try {
    $configPath = getenv('MDHI_MAIL_CONFIG_PATH') ?: dirname(__DIR__) . '/mdhi-mail-config.php';
    if (!is_file($configPath)) mdhi_unavailable('CONFIG_MISSING');
    $config = require $configPath;
    if (!is_array($config)) mdhi_unavailable('CONFIG_INVALID');
    foreach (['smtp_username', 'smtp_password'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) || trim($config[$key]) === '' ||
            strpos($config[$key], 'REPLACE_') !== false) mdhi_unavailable('CONFIG_INCOMPLETE');
    }
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) mdhi_unavailable('AUTOLOAD_MISSING');
    require $autoload;
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) mdhi_unavailable('DEPENDENCY_MISSING');
    try {
        $permitted = mdhi_within_rate_limit($configPath, $config['smtp_password']);
    } catch (\Throwable $storageError) {
        mdhi_unavailable('RATE_LIMIT_UNAVAILABLE');
    }
    if (!$permitted) {
        mdhi_respond(false, 'Too many enquiries have been submitted. Please try again later or email info@mdhiglobal.com.', 429, 'RATE_LIMITED');
    }

    $labels = ['Field2' => 'First name', 'Field3' => 'Last name', 'Field4' => 'Email',
        'Field5' => 'Phone (optional)', 'Field6' => 'Business type', 'Field8' => 'Enquiry type',
        'Field16' => 'Project details'];
    $html = '';
    $plain = '';
    foreach ($labels as $key => $label) {
        $html .= '<p><strong>' . $label . ':</strong><br>' .
            nl2br(htmlspecialchars($fields[$key], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
        $plain .= $label . ': ' . $fields[$key] . "\n\n";
    }

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->Timeout = 20;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['smtp_username'], 'Modern Heavy Industries (Taicang) Co., Ltd.');
    $mail->addAddress('info@mdhiglobal.com');
    $mail->addReplyTo($fields['Field4']);
    $mail->isHTML(true);
    $mail->Subject = 'Message From MDHI Global Portal';
    $mail->Body = $html;
    $mail->AltBody = $plain;
    if (!$mail->send()) {
        error_log('[MDHI contact] SMTP_SEND_FAILED');
        mdhi_respond(false, 'Your enquiry could not be sent. Please email info@mdhiglobal.com.', 502, 'SMTP_SEND_FAILED');
    }
    mdhi_respond(true, 'Your enquiry has been sent. Thank you — our team will be in touch.');
} catch (\Throwable $error) {
    // Do not expose credentials, SMTP debug output or submitted personal information.
    error_log('[MDHI contact] SEND_EXCEPTION');
    mdhi_respond(false, 'Your enquiry could not be sent. Please email info@mdhiglobal.com.', 502, 'SEND_FAILED');
}
