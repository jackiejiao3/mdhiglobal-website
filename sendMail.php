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

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    mdhi_respond(false, 'Please submit the enquiry form.', 405, 'METHOD_NOT_ALLOWED');
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
$token = mdhi_field('g-recaptcha-response');
if ($token === '' || strlen($token) > 8192) {
    mdhi_respond(false, 'Please complete the verification before submitting.', 422, 'CAPTCHA_REQUIRED');
}

try {
    $configPath = getenv('MDHI_MAIL_CONFIG_PATH') ?: dirname(__DIR__) . '/mdhi-mail-config.php';
    if (!is_file($configPath)) mdhi_unavailable('CONFIG_MISSING');
    $config = require $configPath;
    if (!is_array($config)) mdhi_unavailable('CONFIG_INVALID');
    foreach (['smtp_username', 'smtp_password', 'recaptcha_secret'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) || trim($config[$key]) === '' ||
            strpos($config[$key], 'REPLACE_') !== false) mdhi_unavailable('CONFIG_INCOMPLETE');
    }
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) mdhi_unavailable('AUTOLOAD_MISSING');
    require $autoload;
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') || !class_exists('ReCaptcha\\ReCaptcha')) {
        mdhi_unavailable('DEPENDENCY_MISSING');
    }

    // Never bypass verification on missing configuration, network failure or invalid tokens.
    $captcha = new \ReCaptcha\ReCaptcha($config['recaptcha_secret']);
    $verification = $captcha->verify($token);
    $hostname = strtolower(rtrim((string) $verification->getHostname(), '.'));
    if (!$verification->isSuccess() || !in_array($hostname, ['mdhiglobal.com', 'www.mdhiglobal.com'], true)) {
        mdhi_respond(false, 'Verification failed or expired. Please complete it again.', 422, 'CAPTCHA_FAILED');
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
    error_log('[MDHI contact] SEND_OR_VERIFICATION_EXCEPTION');
    mdhi_respond(false, 'Your enquiry could not be sent. Please email info@mdhiglobal.com.', 502, 'SEND_FAILED');
}
