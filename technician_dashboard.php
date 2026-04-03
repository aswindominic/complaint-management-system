<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Correct session variable names
if (!isset($_SESSION['technician_logged_in']) || !isset($_SESSION['technician_device_type'])) {
    die("Unauthorized access. Please login.");
}

$deviceType = $_SESSION['technician_device_type'];
$username = $_SESSION['technician_username'];

// Store and clear session messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch complaints assigned to this device type with status = 'In Progress'
$sql = "SELECT c.*, u.username AS user_name, u.phone, u.email 
        FROM complaints c
        JOIN users u ON c.user_id = u.id
        WHERE c.device_type = ? AND c.status = 'In Progress'
        ORDER BY c.submitted_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $deviceType);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($deviceType) ?> Technician Dashboard</title>
  <link rel="stylesheet" href="technician-dashboard.css">
  <style>
/* Hover effect for clickable images */
.clickable-image {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.clickable-image:hover {
    transform: scale(1.2);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
</style>
</head>
<body>
  <div class="container">
    <h2><?= htmlspecialchars($deviceType) ?> Technician Dashboard</h2>
    <p class="welcome-msg">👨‍🔧 Welcome, <?= htmlspecialchars($username) ?></p>

    <!-- Message Display -->
    <?php if ($successMessage): ?>
      <div class="message-box success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
      <div class="message-box error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
      <table>
<thead>
  <tr>
    <th>Complaint ID</th>
    <th>User</th>
    <th>Phone</th>
    <th>Email</th>
    <th>Address</th>
    <th>Product Info</th>
    <th>Description</th>
    <th>Image</th>
    <th>Submitted At</th>
    <th>Action</th>
    <th>Chat</th>
  </tr>
</thead>
<tbody>
<?php while ($row = $result->fetch_assoc()): ?>
  <tr>
    <td>
      <?= $row['id'] ?><br>
      <small style="color: #555;">
        Assigned: <strong><?= htmlspecialchars($row['assigned_technician']) ?></strong>
      </small>
    </td>
    <td><?= htmlspecialchars($row['user_name']) ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= nl2br(htmlspecialchars($row['address'])) ?></td>

    <!-- ✅ Product Info Column -->
    <td>
      <small>
        <strong>Make:</strong> <?= htmlspecialchars($row['product_make'] ?? '—') ?><br>
        <strong>Year:</strong> <?= htmlspecialchars($row['product_year'] ?? '—') ?>
      </small>
    </td>

    <td>
      <?php 
        $complaintOption = $row['complaint_type'];
        $customDesc = $row['description'];

        if (!empty($complaintOption) && !empty($customDesc)) {
            echo nl2br(htmlspecialchars($complaintOption)) . "<br>— " . nl2br(htmlspecialchars($customDesc));
        } elseif (!empty($complaintOption)) {
            echo nl2br(htmlspecialchars($complaintOption));
        } else {
            echo nl2br(htmlspecialchars($customDesc));
        }
      ?>
    </td>

    <td>
      <?php if (!empty($row['image_path'])): ?>
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Device Image" class="clickable-image" style="width: 80px; cursor: pointer;">
      <?php else: ?>
        No image
      <?php endif; ?>
    </td>
    <td><?= $row['submitted_at'] ?></td>
    <td>
      <?php if ($row['assigned_technician'] === $username): ?>
        <form action="mark_resolved.php" method="POST">
          <input type="hidden" name="complaint_id" value="<?= $row['id'] ?>">
          <button type="submit" onclick="return confirm('Mark this complaint as resolved?')">Mark Resolved</button>
        </form>
      <?php else: ?>
        <span style="color: gray;">Assigned to <?= htmlspecialchars($row['assigned_technician']) ?></span>
      <?php endif; ?>
    </td>
    <td>
  <?php if($row['assigned_technician'] === $username): ?>
    <a href="technician_chat.php?complaint_id=<?= $row['id'] ?>" 
       style="display:inline-block;padding:6px 12px;background:#17a2b8;color:#fff;border-radius:6px;text-decoration:none;">
       Chat
    </a>
  <?php else: ?>
    <span style="color: gray;">Assigned to <?= htmlspecialchars($row['assigned_technician']) ?></span>
  <?php endif; ?>
</td>
  </tr>
<?php endwhile; ?>
</tbody>
      </table>
    <?php else: ?>
      <p>No active complaints found.</p>
    <?php endif; ?>
  </div>

  <!-- Image Modal -->
  <div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
      background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:1000;">
    <span id="closeModal" style="position:absolute; top:20px; right:30px; color:white; font-size:30px; cursor:pointer;">&times;</span>
    <img id="modalImg" src="" style="max-width:90%; max-height:90%; border-radius:4px;">
  </div>

  <script>
  // Auto-hide success/error messages after 3 seconds
  setTimeout(() => {
    const message = document.querySelector('.message-box');
    if (message) {
      message.style.animation = "fadeOut 1s forwards";
      setTimeout(() => {
        message.remove();
      }, 1000); // Remove from DOM after animation
    }
  }, 3000); // Show for 3 seconds

  // Image modal functionality
  const modal = document.getElementById('imageModal');
  const modalImg = document.getElementById('modalImg');
  const closeModal = document.getElementById('closeModal');

  document.querySelectorAll('.clickable-image').forEach(img => {
    img.addEventListener('click', () => {
      modal.style.display = 'flex';
      modalImg.src = img.src;
    });
  });

  closeModal.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  // Close modal when clicking outside the image
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });
  </script>
</body>
</html>
<?php
$conn->close();
?>