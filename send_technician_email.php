<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendTechnicianEmail($email, $link, $name) {
 $mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'aswindominic2020@gmail.com';
    $mail->Password = 'wztvcqvwyktljqkak';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('aswindominic2020@gmail.com', 'Technician Portal');
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Welcome to Technician Portal';
    $mail->Body = "Hello $name, ...";

    $mail->send();
    echo "Email sent to $email <br>";
    return true;
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo} <br>";
    return false;
}
}
?>