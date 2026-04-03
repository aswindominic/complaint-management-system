<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch statistics
$user_count = $conn->query("SELECT COUNT(*) AS total_users FROM users")->fetch_assoc()['total_users'];
$total_complaints = $conn->query("SELECT COUNT(*) AS total_complaints FROM complaints")->fetch_assoc()['total_complaints'];
$resolved_complaints = $conn->query("SELECT COUNT(*) AS resolved_complaints FROM complaints WHERE status = 'Resolved'")->fetch_assoc()['resolved_complaints'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <link rel="stylesheet" href="admin-reports.css"> <!-- separate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

    <h1>System Reports</h1>

    <div class="report-card">
        <i class="fas fa-users icon"></i>
        <h2>Total Registered Users</h2>
        <p><?php echo $user_count; ?></p>
    </div>

    <div class="report-card">
        <i class="fas fa-folder-open icon"></i>
        <h2>Total Complaints Registered</h2>
        <p><?php echo $total_complaints; ?></p>
    </div>

    <div class="report-card">
        <i class="fas fa-check-circle icon"></i>
        <h2>Total Complaints Resolved</h2>
        <p><?php echo $resolved_complaints; ?></p>
    </div>

    <footer>
        &copy; 2025 Admin Panel – Complaint Management System
    </footer>

</body>
</html>