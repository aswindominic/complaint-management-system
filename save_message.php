<?php
session_start();
header('Content-Type: application/json');
require 'config.php';

// Enable errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Input ---
$complaint_id = intval($_POST['complaint_id'] ?? 0);
$message      = trim($_POST['message'] ?? '');
$sender_type  = $_POST['sender_type'] ?? '';

if (!$complaint_id || $message === '' || !in_array($sender_type, ['user', 'technician'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// --- DB Connection ---
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// --- Identify sender ---
if ($sender_type === 'user') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit;
    }
    $sender_id = $_SESSION['user_id'];

    // Verify user owns this complaint
    $stmt = $conn->prepare("SELECT assigned_technician_id FROM complaints WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $complaint_id, $sender_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Complaint not found or access denied']);
        exit;
    }
    $assigned_tech_id = $res->fetch_assoc()['assigned_technician_id'];
    if (empty($assigned_tech_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Complaint not yet assigned to a technician']);
        exit;
    }
    $stmt->close();

} else { // Technician
    if (!isset($_SESSION['technician_logged_in']) || !$_SESSION['technician_logged_in']) {
        echo json_encode(['status' => 'error', 'message' => 'Technician not logged in']);
        exit;
    }
    $sender_id = $_SESSION['technician_id'];

    // Verify technician is assigned OR complaint is in progress with their username
    $stmt = $conn->prepare("SELECT assigned_technician_id, assigned_technician FROM complaints WHERE id = ?");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Complaint not found']);
        exit;
    }
    $row = $res->fetch_assoc();
    $assigned_tech_id = $row['assigned_technician_id'];
    $assigned_tech_name = $row['assigned_technician'];

    // Allow chat if either ID or username matches
    // Fixed: Only check technician ID
if (empty($assigned_tech_id) || $assigned_tech_id != $sender_id) {
    echo json_encode(['status' => 'error', 'message' => 'You are not assigned to this complaint']);
    exit;
}
    $stmt->close();
}

// --- Save message ---
$stmt = $conn->prepare("INSERT INTO chat_messages (complaint_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isis", $complaint_id, $sender_type, $sender_id, $message);
if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
}

$stmt->close();
$conn->close();
?>