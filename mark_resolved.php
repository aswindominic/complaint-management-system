<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure technician is logged in
if (!isset($_SESSION['technician_logged_in']) || !isset($_SESSION['technician_device_type'])) {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: technician_dashboard.php");
    exit();
}

$complaint_id = $_POST['complaint_id'] ?? null;
$device_type = $_SESSION['technician_device_type'];
$technician_username = $_SESSION['technician_username'] ?? '';

if (!$complaint_id || !is_numeric($complaint_id)) {
    $_SESSION['error_message'] = "Invalid complaint ID.";
    header("Location: technician_dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    $_SESSION['error_message'] = "Database connection failed.";
    header("Location: technician_dashboard.php");
    exit();
}

// Step 1: Fetch complaint info
$stmt = $conn->prepare("SELECT status, assigned_technician FROM complaints WHERE id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['error_message'] = "Complaint not found.";
    $stmt->close();
    $conn->close();
    header("Location: technician_dashboard.php");
    exit();
}

$stmt->bind_result($currentStatus, $assignedTechnician);
$stmt->fetch();
$stmt->close();

// ✅ New authorization check
if ($assignedTechnician !== $technician_username) {
    $_SESSION['error_message'] = "⚠️ You are not authorized to resolve this complaint.";
    $conn->close();
    header("Location: technician_dashboard.php");
    exit();
}

// Prevent double resolution
if ($currentStatus === 'Resolved') {
    $_SESSION['error_message'] = "Complaint #$complaint_id is already resolved.";
    $conn->close();
    header("Location: technician_dashboard.php");
    exit();
}

// Step 2: Determine resolved_by value
$resolvedBy = !empty($assignedTechnician) ? $assignedTechnician : $technician_username;

// Step 3: Update complaint status
$updateComplaint = $conn->prepare("UPDATE complaints SET status = 'Resolved', resolved_by = ? WHERE id = ?");
$updateComplaint->bind_param("si", $resolvedBy, $complaint_id);
$updateComplaint->execute();
$updateComplaint->close();

// Step 4: Mark the technician as available again and update last_assigned_at
if (!empty($assignedTechnician)) {
    $updateTech = $conn->prepare("
        UPDATE technician_users
        SET is_available = 1,
            last_assigned_at = NOW()
        WHERE username = ? AND device_type = ?
    ");
    $updateTech->bind_param("ss", $assignedTechnician, $device_type);
    $updateTech->execute();
    $updateTech->close();
}

$conn->close();

$_SESSION['success_message'] = "✅ Complaint #$complaint_id marked as resolved.";
header("Location: technician_dashboard.php");
exit();
?>