<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$db = "complaint_system";
$user = "root";
$pass = "";

// Database connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Sanitize inputs
$username = trim($_POST['username']);
$phone    = trim($_POST['phone']);
$email    = strtolower(trim($_POST['email']));
$password = $_POST['password'];
$address  = trim($_POST['address']);

// Check if user already exists
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("Error: Email already registered.");
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$insert = "INSERT INTO users (username, phone, email, password, address) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("sssss", $username, $phone, $email, $hashedPassword, $address);

if ($stmt->execute()) {
    header("Location: login.html?registered=true");
    exit();
}
 else {
    die("Insert failed: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>