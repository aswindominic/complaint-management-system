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

$device = isset($_GET['device']) ? $conn->real_escape_string($_GET['device']) : '';
if ($device === '') {
    echo "No device specified.";
    exit();
}

$statusFilter = $_GET['status'] ?? 'All';
$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';
$searchTerm = $_GET['search'] ?? '';
$complaintId = $_GET['complaint_id'] ?? '';

// ✅ Fixed technician availability query
$tech_result = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) AS available
    FROM technician_users
    WHERE device_type = '$device'
");
$technician_info = $tech_result ? $tech_result->fetch_assoc() : ['available' => 0, 'total' => 0];


// Main complaints query
$sql = "SELECT c.*, u.username, u.email, u.phone 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.device_type = '$device'";

if ($statusFilter !== 'All') {
    $sql .= " AND c.status = '$statusFilter'";
}

if (!empty($fromDate) && !empty($toDate)) {
    $sql .= " AND DATE(c.timestamp) BETWEEN '$fromDate' AND '$toDate'";
}

if (!empty($searchTerm)) {
    $sql .= " AND (
        u.username LIKE '%$searchTerm%' OR 
        u.phone LIKE '%$searchTerm%' OR 
        u.email LIKE '%$searchTerm%'
    )";
}
if (!empty($complaintId)) {
    $sql .= " AND c.id = " . intval($complaintId);
}

$sql .= " ORDER BY c.timestamp DESC";
$result = $conn->query($sql);

// Fetch available technicians from technician_users
$available_techs = [];
$techQuery = "SELECT id, name 
              FROM technician_users 
              WHERE device_type = '$device' AND is_available = 1 
              ORDER BY IFNULL(last_assigned_at,'1970-01-01') ASC, id ASC";
$tech_result = $conn->query($techQuery);
if ($tech_result && $tech_result->num_rows > 0) {
    while ($tech_row = $tech_result->fetch_assoc()) {
        $available_techs[] = ['id' => $tech_row['id'], 'name' => $tech_row['name']];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($device); ?> Complaints</title>
  <link rel="stylesheet" href="admin-complaints.css">
</head>
<body>

<h1><?php echo htmlspecialchars($device); ?> Complaints</h1>

<?php if ($technician_info): ?>
<p style="text-align:center; color:#007BFF;">
  👨‍🔧 Technician Availability →
  <strong><?php echo $technician_info['available']; ?></strong> /
  <strong><?php echo $technician_info['total']; ?></strong>
</p>
<?php endif; ?>

<p style="text-align:center;">🔍 Filters →
  Device: <strong><?php echo htmlspecialchars($device); ?></strong> |
  Status: <strong><?php echo htmlspecialchars($statusFilter); ?></strong> |
  From: <strong><?php echo $fromDate ?: '—'; ?></strong> |
  To: <strong><?php echo $toDate ?: '—'; ?></strong> |
  Search: <strong><?php echo $searchTerm ?: '—'; ?></strong>
</p>

<form method="GET" style="margin-bottom: 20px; text-align: center;">
  <input type="hidden" name="device" value="<?php echo htmlspecialchars($device); ?>">

  <label for="status">Status:</label>
  <select name="status" id="status">
    <?php
    $statuses = ['All', 'Pending', 'In Progress', 'Resolved'];
    foreach ($statuses as $status) {
        $selected = ($status === $statusFilter) ? 'selected' : '';
        echo "<option value=\"$status\" $selected>$status</option>";
    }
    ?>
  </select>

  <label for="from">From:</label>
  <input type="date" name="from" id="from" value="<?php echo htmlspecialchars($fromDate); ?>">

  <label for="to">To:</label>
  <input type="date" name="to" id="to" value="<?php echo htmlspecialchars($toDate); ?>">

  <label for="complaint_id">Complaint ID:</label>
  <input type="number" name="complaint_id" id="complaint_id" placeholder="Enter Complaint ID" value="<?php echo htmlspecialchars($complaintId ?? ''); ?>">

  <label for="search">Search:</label>
  <input type="text" name="search" id="search" placeholder="Name, Phone, Email" value="<?php echo htmlspecialchars($searchTerm); ?>">

  <button type="submit" style="margin-left: 10px; padding: 6px 12px;">Apply Filters</button>
  <button type="button" style="margin-left: 10px; padding: 6px 12px;" onclick="resetFilters()">Reset</button>
</form>

<?php if ($result && $result->num_rows > 0): ?>
<table>
  <tr>
  <th>Complaint ID</th>
  <th>User ID</th>
  <th>Username</th>
  <th>Phone</th>
  <th>Email</th>
  <th>Address</th>
  <th>Complaint Type</th>
<th>Make</th>
<th>Year</th>
<th>Custom Message</th>
<th>Image</th>
<th>Timestamp</th>
<th>Status</th>
</tr>
  <?php while ($row = $result->fetch_assoc()): ?>
  <tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['user_id']; ?></td>
    <td><?php echo htmlspecialchars($row['username']); ?></td>
    <td><?php echo htmlspecialchars($row['phone']); ?></td>
    <td><?php echo htmlspecialchars($row['email']); ?></td>
    <td><?php echo htmlspecialchars($row['address']); ?></td>
    <td><?php echo htmlspecialchars($row['complaint_type']); ?></td>
<td><?php echo htmlspecialchars($row['product_make'] !== null ? $row['product_make'] : ''); ?></td>
<td><?php echo htmlspecialchars($row['product_year'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo nl2br(htmlspecialchars($row['description'])); ?></td>
    <td>
      <?php if (!empty($row['image_path'])): ?>
        <a href="<?php echo htmlspecialchars($row['image_path']); ?>" target="_blank">
          <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Complaint Image" style="width: 80px;">
        </a>
      <?php else: ?>
        No Image
      <?php endif; ?>
    </td>
    <td><?php echo $row['timestamp']; ?></td>
    <td>
      <?php if ($row['status'] === 'Resolved'): ?>
        <span style="color: green; font-weight: bold;">Resolved</span><br>
        <?php if (!empty($row['resolved_by']) && $row['resolved_by'] !== 'admin'): ?>
          <span style="color: red; font-weight: bold;">(Resolved by <?php echo htmlspecialchars($row['resolved_by']); ?>)</span>
        <?php else: ?>
          <span style="color: blue; font-weight: bold;">(Resolved by Admin)</span>
        <?php endif; ?>

      <?php elseif ($row['status'] === 'In Progress' && $row['assigned_technician']): ?>
        <strong style="color: orange;">In Progress</strong><br>
        <span><strong>Technician:</strong> <?php echo htmlspecialchars($row['assigned_technician']); ?></span>

      <?php else: ?>
        <form method="POST" action="update_status.php" style="margin-bottom: 4px;">
          <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">

          <label>Status:</label>
          <select name="status" required>
            <option value="Pending" <?php if ($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
            <option value="In Progress">In Progress</option>
          </select>
<?php if ($row['status'] == 'Pending'): ?>
  <br>
  <label>Select Technician:</label>
<select name="assigned_technician" <?php echo ($technician_info['available'] == 0) ? 'disabled' : 'required'; ?>>
    <option value="">-- Select Technician --</option>
    <?php
    // Fetch available technicians for this device type
    $techQuery = "SELECT id, name 
                  FROM technician_users 
                  WHERE device_type = '{$row['device_type']}' AND is_available = 1 
                  ORDER BY IFNULL(last_assigned_at,'1970-01-01') ASC, id ASC";
    $techResult = $conn->query($techQuery);
    
    if ($techResult && $techResult->num_rows > 0) {
        while ($techRow = $techResult->fetch_assoc()) {
            echo '<option value="' . (int)$techRow['id'] . '">' . htmlspecialchars($techRow['name']) . '</option>';
        }
    } else {
        // Disabled option if no technicians available
        echo '<option disabled>No technicians available</option>';
    }
    ?>
</select>
  <br>
  <button type="submit" style="margin-top: 5px;">Update</button>
     
  <script>
    const form = document.currentScript.closest('form');
    form.addEventListener('submit', function(e) {
      const status = form.querySelector("select[name='status']").value;
      const techSelect = form.querySelector("select[name='assigned_technician']");
      if (status !== 'In Progress') {
        alert("⚠️ You can only update status to 'In Progress'.");
        e.preventDefault();
        return;
      }
      if (!techSelect.value) {
        alert("⚠️ Please select a technician.");
        e.preventDefault();
      }
    });
  </script>
<?php elseif ($row['assigned_technician']): ?>   
  <br><strong>Technician: </strong><?php echo htmlspecialchars($row['assigned_technician']); ?>
<?php endif; ?>

          
          <script>
            const form = document.currentScript.closest("form");
            form.addEventListener("submit", function (e) {
              const status = form.querySelector("select[name='status']").value;
              const techSelect = form.querySelector("select[name='assigned_technician']");
              const tech = techSelect ? techSelect.value : "";

              if (status !== 'In Progress') {
                alert("⚠️ You can only update status to 'In Progress'.");
                e.preventDefault();
                return;
              }

              if (techSelect && tech === "") {
                alert("⚠️ Please select a technician.");
                e.preventDefault();
              }
            });
          </script>
        </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
<?php else: ?>
  <p style="text-align: center;">No complaints found for this device with selected filters.</p>
<?php endif; ?>

<a href="admin-dashboard.php" class="back-link">⬅ Back to Dashboard</a>

<!-- GLOBAL Reset Filter Script -->
<script>
function resetFilters() {
    const device = "<?php echo htmlspecialchars($device); ?>";
    window.location.href = "view_device_complaints.php?device=" + encodeURIComponent(device);
}
</script>

</body>
</html>

<?php $conn->close(); ?>