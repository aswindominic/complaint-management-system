<?php
require 'config.php';
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

$id = $_POST['id'] ?? 0;
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($password !== $confirm) die("Passwords do not match");

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE technician_users SET password=?, reset_token=NULL, reset_token_expiry=NULL WHERE id=?");
$stmt->bind_param("si", $hash, $id);

if ($stmt->execute()) {
    // ✅ Make sure this points to the login form page
    echo "Password successfully reset. <a href='technician_login.html'>Login here</a>";
} else {
    echo "Error updating password";
}

$stmt->close();
$conn->close();