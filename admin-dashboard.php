<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Devices list
$devices = [
  'Smartphone', 'Laptop', 'Smart Watch', 'Desktop',
  'Television', 'Refrigerator', 'Washing Machine', 'Air Conditioner'
];

// Pending counts
$pending_counts = [];
foreach ($devices as $device) {
    $escaped = $conn->real_escape_string($device);
    $sql = "SELECT COUNT(*) as pending FROM complaints WHERE device_type = '$escaped' AND status = 'Pending'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $pending_counts[$device] = $row['pending'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="admin-dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    .badge {
      position: absolute;
      top: 8px;
      right: 10px;
      background-color: red;
      color: white;
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 50%;
      font-weight: bold;
      min-width: 18px;
      text-align: center;
    }
    .device-card {
      position: relative;
    }
  </style>
</head>
<body>

  <h1>Admin Dashboard</h1>

  <div class="dashboard">

    <a href="view_device_complaints.php?device=Smartphone" class="device-card">
      <?php if ($pending_counts['Smartphone'] > 0): ?><div class="badge"><?php echo $pending_counts['Smartphone']; ?></div><?php endif; ?>
      <i class="fas fa-mobile-alt"></i><span>Smartphone Complaints</span>
    </a>

    <a href="view_device_complaints.php?device=Laptop" class="device-card">
      <?php if ($pending_counts['Laptop'] > 0): ?><div class="badge"><?php echo $pending_counts['Laptop']; ?></div><?php endif; ?>
      <i class="fas fa-laptop"></i><span>Laptop Complaints</span>
    </a>

    <a href="view_device_complaints.php?device=Smart Watch" class="device-card">
      <?php if ($pending_counts['Smart Watch'] > 0): ?><div class="badge"><?php echo $pending_counts['Smart Watch']; ?></div><?php endif; ?>
      <i class="fas fa-clock"></i><span>Smartwatch Complaints</span>
    </a>

    <a href="view_device_complaints.php?device=Desktop" class="device-card">
      <?php if ($pending_counts['Desktop'] > 0): ?><div class="badge"><?php echo $pending_counts['Desktop']; ?></div><?php endif; ?>
      <i class="fas fa-desktop"></i><span>Desktop Complaints</span>
    </a>

    <a href="view_device_complaints.php?device=Television" class="device-card">
      <?php if ($pending_counts['Television'] > 0): ?><div class="badge"><?php echo $pending_counts['Television']; ?></div><?php endif; ?>
      <i class="fas fa-tv"></i><span>Television Complaints</span>
    </a>

    <a href="view_device_complaints.php?device=Refrigerator" class="device-card">
      <?php if ($pending_counts['Refrigerator'] > 0): ?><div class="badge"><?php echo $pending_counts['Refrigerator']; ?></div><?php endif; ?>
      <i class="fas fa-snowflake"></i><span>Refrigerator Complaints</span>
    </a>

    <a href="view_device_complaints.php?device=Washing Machine" class="device-card">
      <?php if ($pending_counts['Washing Machine'] > 0): ?><div class="badge"><?php echo $pending_counts['Washing Machine']; ?></div><?php endif; ?>
      <i class="fas fa-soap"></i><span>Washing Machine Complaints</span>
    </a>

    <a href="view_device_complaints.php?device=Air Conditioner" class="device-card">
      <?php if ($pending_counts['Air Conditioner'] > 0): ?><div class="badge"><?php echo $pending_counts['Air Conditioner']; ?></div><?php endif; ?>
      <i class="fas fa-fan"></i><span>Air Conditioner Complaints</span>
    </a>

    <a href="add_technicians.php" class="device-card">
      <i class="fas fa-user-cog"></i><span>Add Technicians</span>
    </a>

    <a href="admin_reports.php" class="device-card">
      <i class="fas fa-chart-bar"></i><span>Reports</span>
    </a>

    <a href="manage_users.php" class="device-card">
      <i class="fas fa-users-cog"></i><span>Manage Users</span>
    </a>
    <a href="view_reviews.php" class="device-card">
  <i class="fas fa-comments"></i><span>Review Responses</span>
</a>

  </div>

  <footer>
    &copy; 2025 Admin Panel – Complaint Management System
  </footer>

</body>
</html>