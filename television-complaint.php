<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "complaint_system");

$availableTechs = "N/A";
$userAddress = "";

if (!$conn->connect_error) {
    $deviceType = 'Television';
$result = $conn->query("SELECT COUNT(*) AS available_count 
                        FROM technician_users 
                        WHERE device_type = '$deviceType' AND is_available = 1");
if ($result && $row = $result->fetch_assoc()) {
    $availableTechs = $row['available_count'];
}

    $stmt = $conn->prepare("SELECT address FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($userAddress);
    $stmt->fetch();
    $stmt->close();

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Television Complaint Form</title>
  <link rel="stylesheet" href="television-style.css">
</head>
<body>
  <div class="container">
    <h2>Television Complaint Registration</h2>

    <!-- Technician Availability -->
    <p style="text-align: center; color: green; font-weight: bold;">
      🛠 Available Technicians for Television: <?php echo htmlspecialchars($availableTechs); ?>
    </p>

<form class="complaint-form" id="complaintForm" action="submit_complaint.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="device_type" value="Television" />

  <div class="input-group">
    <label for="preset">Choose a common complaint (optional)</label>
    <select id="preset" name="complaint_type">
      <option value="">-- Select an issue --</option>
      <option value="No display / blank screen">No display / blank screen</option>
      <option value="No sound / distorted sound">No sound / distorted sound</option>
      <option value="Remote not working">Remote not working</option>
      <option value="TV not turning on">TV not turning on</option>
      <option value="Wi-Fi or smart features not working">Wi-Fi or smart features not working</option>
      <option value="Screen flickering">Screen flickering</option>
      <option value="HDMI or USB not working">HDMI or USB not working</option>
      <option value="Other (please specify)">Other complaints (please specify)</option>
    </select>
  </div>

  <div class="input-group">
    <label for="customComplaint">Other complaint</label>
    <textarea id="customComplaint" name="description" rows="4" placeholder="Describe your complaint..."></textarea>
  </div>
   <div class="input-group">
      <label for="productMake">Product Make (Brand/Model)</label>
      <input type="text" id="productMake" name="product_make" placeholder="LG, Samsung, Voltas....">
    </div>

    <div class="input-group">
      <label for="productYear">Year of Manufacture</label>
      <input type="number" id="productYear" name="product_year" placeholder="2020" min="1990" max="<?php echo date('Y'); ?>">
    </div>

  <div class="input-group">
    <label for="address">Address for this complaint (editable)</label>
    <textarea id="address" name="address" rows="4" required><?php echo htmlspecialchars($userAddress); ?></textarea>
  </div>

  <div class="input-group">
    <label for="imageUpload">Upload an image of the device</label>
    <input type="file" id="imageUpload" name="image" accept="image/*">
  </div>

  <button type="submit">Submit Complaint</button>
</form>

<script>
const form = document.getElementById('complaintForm');

form.addEventListener('submit', function(event) {
  const description = document.getElementById('customComplaint').value.trim();
  const complaint = document.getElementById('preset').value;

  if (description === "" && complaint === "") {
    event.preventDefault();
    alert("Please fill at least one field: 'Other complaint' or choose a common complaint.");
  }
});
</script>
</body>
</html>