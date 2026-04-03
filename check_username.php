<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'No username provided']);
    exit;
}

$username = strtolower(trim($_GET['username']));
$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// ✅ Check inside technician_users (current active table)
$stmt = $conn->prepare("SELECT id FROM technician_users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'taken']);
} else {
    echo json_encode(['status' => 'available']);
}

$stmt->close();
$conn->close();
?>