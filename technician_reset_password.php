<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';
$message = '';
$id = null;

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id, reset_token_expiry FROM technician_users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($id, $expiry);
    $stmt->fetch();
    $stmt->close();

    if (!$id || strtotime($expiry) < time()) {
        $message = "<p style='color:red;'>This reset link has expired or is invalid.</p>";
    }
} else {
    $message = "<p style='color:red;'>Invalid reset link.</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Technician Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #004e92, #000428);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 0 15px rgba(255,255,255,0.2);
            width: 350px;
        }
        h2 {
            margin-bottom: 20px;
        }
        input {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
        }
        button {
            background: #00c6ff;
            border: none;
            padding: 10px 20px;
            color: #fff;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        button:hover {
            background: #0072ff;
        }
        p {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>

        <?php if (!empty($message)) : ?>
            <?= $message ?>
        <?php elseif ($id): ?>
            <form method="POST" action="update_password.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>