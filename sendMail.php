<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';
$content_fb = $_POST['Field16'] ? trim($_POST['Field16']) : '';
if (true) {
    $first = $_POST['Field2'] ? trim($_POST['Field2']) : '';
    $last = $_POST['Field3'] ? trim($_POST['Field3']) : '';
    $email = $_POST['Field4'] ? trim($_POST['Field4']) : '';
    $phone = $_POST['Field5'] ? trim($_POST['Field5']) : '';
    $business = $_POST['Field6'] ? trim($_POST['Field6']) : '';
    $way = $_POST['Field8'] ? trim($_POST['Field8']) : '';


    $content .= 'Name:&nbsp;' . $first . '&nbsp;' . $last . '<br>';
    $content .= 'Email:&nbsp;' . $email . '<br>';
    $content .= 'Phone:&nbsp;' . $phone . '<br>';
    $content .= 'Business Type:&nbsp;' . $business . '<br>';
    $content .= 'Requested:&nbsp;' . $way . '<br>';
    $content .= 'Message:&nbsp;' . $content_fb . '<br>';

    $mail = new PHPMailer();                              // Passing `true` enables exceptions
    try {
        //Server settings
        $mail->SMTPDebug = 3;                                 // Enable verbose debug output
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
        $mail->addReplyTo('info@mdhiglobal.com', 'Global Web');

        //Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Message From MDHI Global Portal';
        $mail->Body = $content;
        header("location:tips.html");//修改发送成功时的地址 到中转页面提示信息
        $res = $mail->send();
        exit();
    } catch (Exception $e) {
        header("location:mdhicont.html");
    }
} else {
    header("location:index.html");
}
