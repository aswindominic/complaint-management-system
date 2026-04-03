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
    $deviceType = 'Refrigerator'; // Or dynamically from hidden input if you want
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
  <title>Refrigerator Complaint Form</title>
  <link rel="stylesheet" href="refrigerator-style.css">
</head>
<body>
  <div class="container">
    <h2>Refrigerator Complaint Registration</h2>

    <!-- Technician Availability -->
    <p style="text-align: center; color: green; font-weight: bold;">
      🛠 Available Technicians for Refrigerator: <?php echo htmlspecialchars($availableTechs); ?>
    </p>

    <form class="complaint-form" id="complaintForm" action="submit_complaint.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="device_type" value="Refrigerator" />

      <div class="input-group">
        <label for="preset">Choose a common complaint (optional)</label>
        <select id="preset" name="complaint_type">
          <option value="">-- Select an issue --</option>
          <option value="Not cooling properly">Not cooling properly</option>
          <option value="Unusual noise">Unusual noise</option>
          <option value="Water leakage">Water leakage</option>
          <option value="Door not closing properly">Door not closing properly</option>
          <option value="Freezer issues">Freezer issues</option>
          <option value="Interior light not working">Interior light not working</option>
          <option value="Compressor running constantly">Compressor running constantly</option>
          <option value="Other">Other complaints(please specify)</option>
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

      // Ensure at least one field is filled
      if (description === "" && complaint === "") {
        event.preventDefault();
        alert("Please fill at least one field: 'Other complaint' or choose a common complaint.");
      }
    });
  </script>
</body>
</html>