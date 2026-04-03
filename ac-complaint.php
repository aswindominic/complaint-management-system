<?php
session_start();  // start session

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Connect to DB
$conn = new mysqli("localhost", "root", "", "complaint_system");

$availableTechs = "N/A";
$userAddress = "";

if (!$conn->connect_error) {
    // Get technician availability
   // Get technician availability from technician_users table
$deviceType = 'Air Conditioner'; // Or dynamically if you have variable
$result = $conn->query("SELECT COUNT(*) AS available_count 
                        FROM technician_users 
                        WHERE device_type = '$deviceType' AND is_available = 1");
if ($result && $row = $result->fetch_assoc()) {
    $availableTechs = $row['available_count'];
}

    // Get user's registered address
    $stmt = $conn->prepare("SELECT address FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($userAddress);
    $stmt->fetch();
    $stmt->close();

    $conn->close();
} else {
    // handle DB error if needed
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Air Conditioner Complaint Form</title>
  <link rel="stylesheet" href="ac-style.css">
</head>
<body>
  <div class="container">
  <h2>Air Conditioner Complaint Registration</h2>

  <!-- Technician Availability -->
  <p style="text-align: center; color: green; font-weight: bold;">
    🛠 Available Technicians for Air Conditioner: <?php echo htmlspecialchars($availableTechs); ?>
  </p>

  <form class="complaint-form" id="complaintForm" action="submit_complaint.php" method="POST" enctype="multipart/form-data">
    <!-- Hidden input for device type -->
    <input type="hidden" name="device_type" value="Air Conditioner" />

    <div class="input-group">
      <label for="preset">Choose a common complaint (optional)</label>
      <select id="preset" name="complaint_type">
        <option value="">-- Select an issue --</option>
        <option value="Not cooling properly">Not cooling properly</option>
        <option value="Water leakage">Water leakage</option>
        <option value="Remote not working">Remote not working</option>
        <option value="Unusual noise or vibration">Unusual noise or vibration</option>
        <option value="Power supply issue">Power supply issue</option>
        <option value="Bad smell from vents">Bad smell from vents</option>
        <option value="Fan not working">Fan not working</option>
        <option value="Other (please specify)">Other complaints(please specify)</option>
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
</div>

<script>
  const form = document.getElementById('complaintForm');

  form.addEventListener('submit', function(event) {
    const description = document.getElementById('customComplaint').value.trim();
    const complaint = document.getElementById('preset').value;

    // Check if both fields are empty
    if (description === "" && complaint === "") {
      event.preventDefault();
      alert("Please fill at least one field: 'Other complaint' or choose a common complaint.");
    }
  });
</script>
</body>
</html>