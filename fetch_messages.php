<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

// Enable errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Input ---
$complaint_id = intval($_GET['complaint_id'] ?? 0);
if (!$complaint_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid complaint id']);
    exit;
}

// --- DB Connection ---
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

// --- Get complaint info ---
$stmt = $conn->prepare("SELECT user_id, assigned_technician_id FROM complaints WHERE id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Complaint not found']);
    exit;
}
$complaint = $res->fetch_assoc();
$stmt->close();

// --- Authorization ---
$user_id = $_SESSION['user_id'] ?? null;
$tech_id = $_SESSION['technician_id'] ?? null;

if (isset($user_id)) {
    if ($complaint['user_id'] != $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }
} elseif (isset($tech_id)) {
    if ($complaint['assigned_technician_id'] != $tech_id) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// --- Fetch messages with sender names ---
$sql = "
SELECT cm.sender_type, cm.message,
       CASE cm.sender_type
           WHEN 'user' THEN u.username
           WHEN 'technician' THEN t.username
       END AS sender_name
FROM chat_messages cm
LEFT JOIN users u ON cm.sender_type='user' AND cm.sender_id=u.id
LEFT JOIN technician_users t ON cm.sender_type='technician' AND cm.sender_id=t.id
WHERE cm.complaint_id=?
ORDER BY cm.created_at ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'sender_type' => $row['sender_type'],
        'sender_name' => $row['sender_name'],
        'message' => $row['message']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'messages' => $messages]);
exit;
?>