<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function respond($ok, $message, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Please submit the contact form.', 405);
$values = [];
foreach (['Field2', 'Field3', 'Field4', 'Field5', 'Field6', 'Field8', 'Field16'] as $key) {
    $values[$key] = isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
    if ($key !== 'Field5' && $values[$key] === '') respond(false, 'Please complete all required fields.', 422);
}
if (!filter_var($values['Field4'], FILTER_VALIDATE_EMAIL)) respond(false, 'Please enter a valid email address.', 422);
if (strlen($values['Field16']) > 4000) respond(false, 'Please shorten your message.', 422);
if (!file_exists(__DIR__ . '/vendor/autoload.php')) respond(false, 'Sending is currently unavailable. Please email info@mdhiglobal.com.', 503);
require __DIR__ . '/vendor/autoload.php';
$email = $values['Field4'];
$content = '';
foreach (['Field2'=>'First name', 'Field3'=>'Last name', 'Field4'=>'Email', 'Field5'=>'Phone (optional)', 'Field6'=>'Business type', 'Field8'=>'Enquiry type', 'Field16'=>'Project details'] as $key=>$label) {
    $content .= $label . ': ' . nl2br(htmlspecialchars($values[$key], ENT_QUOTES, 'UTF-8')) . '<br>';
}
if (true) {
    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
        //Server settings
        $mail->SMTPDebug = 0;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.office365.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'modernheavy@outlook.com';                 // SMTP username
        $mail->Password = '88Taicang88';                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
        //Recipients
        $mail->setFrom('modernheavy@outlook.com', 'Modern Heavy Industries (Taicang) Co., Ltd.');//First field is same with $mail->Username
        // $mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
        $mail->addAddress('info@mdhiglobal.com');              // Name is optional
        //$mail->addAddress('info@mdhiglobal.com');
        //Global Web
        $mail->addReplyTo($email);
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;

        //Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Message From MDHI Global Portal';
        $mail->Body = $content;
        if ($mail->send()) respond(true, 'Submitted successfully. Thank you — our team will be in touch.');
        respond(false, 'Your enquiry could not be sent. Please try again or email info@mdhiglobal.com.', 502);
    } catch (\Throwable $e) {
        respond(false, 'Your enquiry could not be sent. Please try again or email info@mdhiglobal.com.', 502);
    }
}
