<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear user session variables to prevent conflicts
unset($_SESSION['user_id'], $_SESSION['username']);

// Connect to database
$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get username and password from form
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    die("Please fill all fields.");
}

// Check if technician exists
$stmt = $conn->prepare("SELECT id, password, device_type FROM technician_users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $hashed_password, $device_type);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {
        if (strtolower($device_type) === 'fridge') {
            $device_type = 'Refrigerator';
        }

        // Set technician session variables
        $_SESSION['technician_logged_in'] = true;
        $_SESSION['technician_id'] = $id;
        $_SESSION['technician_username'] = $username;
        $_SESSION['technician_device_type'] = $device_type;

        header("Location: technician_dashboard.php");
        exit();
    } else {
        echo "Invalid password.";
    }
} else {
    echo "Technician not found.";
}

$stmt->close();
$conn->close();
?>