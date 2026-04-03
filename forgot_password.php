<?php
session_start();
require 'config.php';
require 'vendor/autoload.php'; // PHPMailer autoload

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

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id);
            $stmt->fetch();

            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $conn->query("DELETE FROM password_resets WHERE user_id = $user_id");
            $stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $user_id, $token, $expires_at);
            $stmt2->execute();

            $reset_link = $BASE_URL . "/reset_password.php?token=" . $token;

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

                $mail->setFrom($SMTP_USER, 'Support');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
    <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px; background: #f9f9f9;'>
        
        <h2 style='color: #007BFF; text-align: center;'>Complaint Registration System</h2>
        
        <p>Hello,</p>

        <p>We received a request to reset your password for your <strong>Complaint Registration System</strong> account.</p>

        <p>If you did not request this, please ignore this email.  
        Otherwise, click the button below to reset your password:</p>

        <div style='text-align: center; margin: 20px 0;'>
            <a href='$reset_link' 
               style='display: inline-block; padding: 12px 20px; background-color: #007BFF; color: white; text-decoration: none; font-weight: bold; border-radius: 5px;'>
               Reset My Password
            </a>
        </div>

        <p>This link will expire in <strong>1 hour</strong> for your security.</p>

        <p>Thank you,<br>
        <strong>Complaint Registration System Support Team</strong></p>

        <hr style='margin-top: 20px;'>
        <small style='color: #777;'>This is an automated message, please do not reply to this email.</small>
    </div>
";
                $mail->send();
                $message = "Password reset link has been sent to your email.";
            } catch (Exception $e) {
                $message = "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            $message = "No account found with that email address.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:
                linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
                url('https://www.toptal.com/designers/subtlepatterns/uploads/dot-grid.png'),
                linear-gradient(135deg, #2c3e50, #3498db);
            background-size: auto, auto, cover;
            background-position: center;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
            animation: fadeUp 0.8s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 0;
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #2980b9;
        }

        p {
            color: #333;
            font-size: 14px;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Forgot Password</h2>
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