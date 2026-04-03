<?php
session_start();
require 'config.php';
require 'vendor/autoload.php'; // Using Composer autoload for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
    } else {
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }

        // ✅ Check technician table instead of users
        $stmt = $conn->prepare("SELECT id FROM technician_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($tech_id);
            $stmt->fetch();

            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // ✅ Store reset token in technician_users table (or a separate table if you prefer)
            $update = $conn->prepare("UPDATE technician_users SET reset_token=?, reset_token_expiry=? WHERE id=?");
            $update->bind_param("ssi", $token, $expires_at, $tech_id);
            $update->execute();

            // ✅ Reset link for technician reset page
            $reset_link = $BASE_URL . "/technician_reset_password.php?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host = $SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = $SMTP_USER;
                $mail->Password = $SMTP_PASS;
                $mail->SMTPSecure = $SMTP_SECURE;
                $mail->Port = $SMTP_PORT;

                $mail->setFrom($SMTP_USER, 'Complaint System');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Technician Password Reset Request';
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px; background: #f9f9f9;'>
                    <h2 style='color: #007BFF; text-align: center;'>Complaint Registration System</h2>
                    <p>Hello Technician,</p>
                    <p>We received a request to reset your password for your technician account.</p>
                    <p>If you did not request this, please ignore this email. Otherwise, click below to reset your password:</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='$reset_link' 
                           style='display: inline-block; padding: 12px 20px; background-color: #007BFF; color: white; text-decoration: none; font-weight: bold; border-radius: 5px;'>
                           Reset My Password
                        </a>
                    </div>
                    <p>This link will expire in <strong>1 hour</strong> for your security.</p>
                    <p>Thank you,<br><strong>Complaint Registration System Support</strong></p>
                </div>
                ";
                $mail->send();
                $message = "Password reset link has been sent to your email.";
            } catch (Exception $e) {
                $message = "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            $message = "No technician account found with that email address.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Technician Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #2980b9;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Technician Forgot Password</h2>
        <?php if (!empty($message)) {
            $class = (strpos($message, 'sent') !== false) ? 'success' : 'error';
            echo "<div class='message $class'>$message</div>";
        } ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
    </div>
</body>
</html>