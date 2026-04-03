<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Technician Forgot Password</title>
</head>
<body>
    <h2>Forgot Password</h2>
    <form method="POST" action="send_reset_link.php">
        <label>Email:</label>
        <input type="email" name="email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</body>
</html>