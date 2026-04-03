<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit();
}

$host = "localhost";
$db = "complaint_system";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = strtolower(trim($_POST['email']));
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: login.html?error=nouser");
    exit();
}

$row = $result->fetch_assoc();

if (!password_verify($password, $row['password'])) {
    $stmt->close();
    $conn->close();
    header("Location: login.html?error=wrongpass");
    exit();
}

$stmt->close();
$conn->close();
// Clear any existing technician session to prevent conflicts
unset($_SESSION['technician_logged_in']);
unset($_SESSION['technician_id']);
unset($_SESSION['technician_username']);
$_SESSION['user_id'] = $row['id'];
$_SESSION['username'] = $row['username'];

header("Location: firstselection.html");
exit();
?>