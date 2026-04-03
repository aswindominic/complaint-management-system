<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['status'])) {
    $complaintId = (int) $_POST['complaint_id'];
    $newStatus = $conn->real_escape_string($_POST['status']);
    $selectedTechnicianId = isset($_POST['assigned_technician']) ? (int) $_POST['assigned_technician'] : null;

    // Step 1: Get current complaint info
    $query = "SELECT status, device_type, resolved_by, assigned_technician, assigned_technician_id 
              FROM complaints WHERE id = $complaintId";
    $result = $conn->query($query);

    if (!$result || $result->num_rows === 0) {
        echo "<script>alert('❌ Complaint not found.'); window.location.href = 'admin-dashboard.php';</script>";
        exit();
    }

    $row = $result->fetch_assoc();
    $currentStatus = $row['status'];
    $deviceType = $conn->real_escape_string($row['device_type']);
    $resolvedBy = $row['resolved_by'];
    $currentTechnician = $row['assigned_technician'];
    $currentTechnicianId = $row['assigned_technician_id'];

    // 🚫 Prevent reverting In Progress → Pending
    if ($currentStatus === 'In Progress' && $newStatus === 'Pending') {
        echo "<script>
            alert('⚠️ Cannot change status from In Progress back to Pending.');
            window.location.href = 'view_device_complaints.php?device=" . urlencode($deviceType) . "';
        </script>";
        exit();
    }

    // 🚫 Block duplicate admin resolution
    if ($currentStatus === 'Resolved' && $resolvedBy === 'technician' && $newStatus === 'Resolved') {
        echo "<script>
            alert('⚠️ Already resolved by technician. No need to mark again.');
            window.location.href = 'view_device_complaints.php?device=" . urlencode($deviceType) . "';
        </script>";
        exit();
    }

    // Step 2: Assign technician (In Progress)
    if ($newStatus === 'In Progress') {
        if (!$selectedTechnicianId) {
            echo "<script>
                alert('⚠️ Please select a technician.');
                window.location.href = 'view_device_complaints.php?device=" . urlencode($deviceType) . "';
            </script>";
            exit();
        }

        // Fetch technician name and check availability
        $techResult = $conn->query("SELECT name FROM technician_users 
                                    WHERE id = $selectedTechnicianId 
                                    AND device_type = '$deviceType' 
                                    AND is_available = 1");
        if (!$techResult || $techResult->num_rows === 0) {
            echo "<script>
                alert('❌ Selected technician not available.');
                window.location.href = 'view_device_complaints.php?device=" . urlencode($deviceType) . "';
            </script>";
            exit();
        }
        $techRow = $techResult->fetch_assoc();
        $techName = $techRow['name'];

        // Update complaint
        $conn->query("UPDATE complaints 
                      SET status = 'In Progress',
                          assigned_technician = '$techName',
                          assigned_technician_id = $selectedTechnicianId
                      WHERE id = $complaintId");

        // Mark technician unavailable
        $conn->query("UPDATE technician_users 
                      SET is_available = 0, last_assigned_at = NOW() 
                      WHERE id = $selectedTechnicianId");
    }

    // Step 3: Resolve complaint
    elseif ($newStatus === 'Resolved') {
        $resolvedByValue = $currentTechnician ?: 'admin';
        $conn->query("UPDATE complaints 
                      SET status = 'Resolved',
                          resolved_by = '$resolvedByValue'
                      WHERE id = $complaintId");

        // Make technician available again
        if ($currentTechnicianId) {
            $conn->query("UPDATE technician_users 
                          SET is_available = 1, last_assigned_at = NOW() 
                          WHERE id = $currentTechnicianId");
        }
    }

    // Step 4: Update other statuses (e.g., Pending)
    else {
        $conn->query("UPDATE complaints SET status = '$newStatus' WHERE id = $complaintId");
    }

    $_SESSION['status_updated'] = true;
    header("Location: view_device_complaints.php?device=" . urlencode($deviceType));
    exit();
} else {
    echo "<script>alert('❌ Invalid request.'); window.location.href = 'admin-dashboard.php';</script>";
    exit();
}

$conn->close();
?>