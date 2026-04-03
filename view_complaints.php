<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access. Please login.");
}

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = intval($_SESSION['user_id']);

/* ───────────────────────────────
   ❶ HANDLE REVIEW SUBMISSION
   ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['review_complaint_id'], $_POST['feedback'])
    && trim($_POST['feedback']) !== '') {

    $complaint_id = intval($_POST['review_complaint_id']);
    $feedback     = $conn->real_escape_string(trim($_POST['feedback']));

    // Allow exactly one review per complaint (complaint_id is UNIQUE)
    $exists = $conn->query("SELECT id FROM complaint_reviews WHERE complaint_id = $complaint_id");
    if ($exists && $exists->num_rows === 0) {
        $stmt = $conn->prepare(
            "INSERT INTO complaint_reviews (complaint_id, user_id, feedback, created_at)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param("iis", $complaint_id, $user_id, $feedback);
        $stmt->execute();
        $stmt->close();
    }
}

/* ───────────────────────────────
   ❷ FILTER LOGIC (unchanged)
   ─────────────────────────────── */
$whereClauses = ["c.user_id = $user_id"];

$status     = $_GET['status']     ?? '';
$from_date  = $_GET['from_date']  ?? '';
$to_date    = $_GET['to_date']    ?? '';
$search     = $_GET['search']     ?? '';

if ($status !== '') {
    $whereClauses[] = "c.status = '" . $conn->real_escape_string($status) . "'";
}
if ($from_date && $to_date) {
    $whereClauses[] = "DATE(c.submitted_at) BETWEEN '$from_date' AND '$to_date'";
}
if ($search !== '') {
    $safeSearch = $conn->real_escape_string($search);
    $whereClauses[] = "(c.complaint_type LIKE '%$safeSearch%' OR c.description LIKE '%$safeSearch%')";
}

$whereSql = implode(" AND ", $whereClauses);

$sql = "SELECT c.*, u.username, u.email, u.phone
        FROM complaints c
        JOIN users u ON c.user_id = u.id
        WHERE $whereSql
        ORDER BY c.submitted_at DESC";

$result = $conn->query($sql);

/* ───────────────────────────────
   ❸ STATUS HELPER
   ─────────────────────────────── */
function getStatusDisplay($status, $assigned_technician) {
    switch ($status) {
        case 'Pending':
            return ['Pending', 'status-pending'];
        case 'In Progress':
            return ['Assigned to ' . htmlspecialchars($assigned_technician), 'status-inprogress'];
        case 'Resolved':
            return ['Resolved', 'status-resolved'];
        default:
            return [$status, ''];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Your Complaints</title>
  <style>
    body{
      font-family: Arial, sans-serif;
      background:#f1f1f1;
      padding:20px;
    }
    h1{text-align:center;color:#333;margin-bottom:20px;}

    /* NAV-DROPDOWN (unchanged) */
    .nav-dropdown{display:flex;justify-content:flex-end;margin-bottom:15px;}
    .nav-dropdown button{background:#28a745;border:none;border-radius:10px;padding:10px 15px;cursor:pointer;}
    .nav-dropdown button div{width:25px;height:3px;background:#fff;margin:4px 0;}

    /* Filter form */
    form.filter{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;justify-content:center;}
    form.filter label{font-weight:bold;}
    form.filter input,form.filter select{padding:5px;margin-left:5px;}

    table{
      width:100%;border-collapse:collapse;background:#fff;
      box-shadow:0 0 10px rgba(0,0,0,0.1);
      margin-bottom:30px;
    }
    th,td{border:1px solid #ccc;padding:12px;text-align:center;}
    th{background:#28a745;color:#fff;}
    tr:nth-child(even){background:#f9f9f9;}
    img{width:100px;height:auto;border-radius:5px;}

    /* Status badges */
    .status-badge {
      padding: 5px 10px;
      border-radius: 6px;
      font-weight: bold;
      display: inline-block;
    }
    .status-pending { background-color: #ffc107; color: #000; }       /* yellow */
    .status-inprogress { background-color: #17a2b8; color: #fff; }    /* teal */
    .status-resolved { background-color: #28a745; color: #fff; }      /* green */

    /* Review form / display */
    .review-box{background:#f8f8f8;padding:10px;text-align:left;}
    textarea{width:100%;padding:8px;border:1px solid #bbb;border-radius:6px;resize:vertical;}
    .review-submit{margin-top:8px;background:#007bff;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;}
    .review-submit:hover{background:#0062cc;}
    .feedback-label{font-weight:bold;margin-bottom:4px;display:inline-block;}

    /* Back-to-top nav menu (unchanged JS below) */
  </style>
</head>
<body>

<!-- NAVIGATION MENU (unchanged) -->
<div class="nav-dropdown" style="position: relative;">
  <button onclick="toggleMenu()">
    <div></div><div></div><div></div>
  </button>
  <div id="dropdown-menu" style="
      display:none;position:absolute;top:50px;right:0;background:#fff;
      border:1px solid #ccc;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.15);z-index:10;min-width:180px;">
    <a href="cover.html" style="display:block;padding:10px;text-decoration:none;color:#333;">🏠 Home</a>
    <a href="firstselection.html" style="display:block;padding:10px;text-decoration:none;color:#333;">📱 Device Selection</a>
  </div>
</div>
<script>
  function toggleMenu(){const m=document.getElementById("dropdown-menu");m.style.display=m.style.display==="block"?"none":"block";}
  window.addEventListener('click',e=>{
    const m=document.getElementById("dropdown-menu");
    if(!e.target.closest('.nav-dropdown')) m.style.display='none';
  });
</script>

<h1>Your Complaints</h1>

<form method="GET" class="filter">
  <label>Status:
    <select name="status">
      <option value="" <?= $status===''?'selected':''?>>All</option>
      <option value="Pending" <?= $status==='Pending'?'selected':''?>>Pending</option>
      <option value="In Progress" <?= $status==='In Progress'?'selected':''?>>Assigned to technician</option>
      <option value="Resolved" <?= $status==='Resolved'?'selected':''?>>Resolved</option>
    </select>
  </label>
  <label>From: <input type="date" name="from_date" value="<?=htmlspecialchars($from_date)?>"></label>
  <label>To: <input type="date" name="to_date" value="<?=htmlspecialchars($to_date)?>"></label>
  <label>Search: <input type="text" name="search" value="<?=htmlspecialchars($search)?>"></label>
  <button type="submit">Filter</button>
</form>

<?php if($result && $result->num_rows>0): ?>
<table>
  <tr>
    <th>Complaint ID</th><th>Device Type</th><th>Complaint Type</th>
    <th>Description</th><th>Image</th><th>Status</th><th>Submitted At</th><th>Chat</th>
  </tr>

  <?php while($row=$result->fetch_assoc()): ?>
    <!-- Complaint row -->
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['device_type']) ?></td>
      <td><?= htmlspecialchars($row['complaint_type']) ?></td>
      <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
      <td>
        <?php if(!empty($row['image_path'])): ?>
          <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Image">
        <?php else: ?>No image<?php endif; ?>
      </td>
      <?php list($statusText, $statusClass) = getStatusDisplay($row['status'], $row['assigned_technician']); ?>
      <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
      <td><?= $row['submitted_at'] ?></td>
     <td>
  <?php if($row['assigned_technician'] && $row['status'] !== 'Pending' && $row['status'] !== 'Resolved'): ?>
  <a href="chat.php?complaint_id=<?= $row['id'] ?>" 
     style="display:inline-block;padding:6px 12px;background:#17a2b8;color:#fff;border-radius:6px;text-decoration:none;">
     Chat
  </a>
<?php else: ?>
  <span style="color:#aaa;display:inline-block;padding:6px 12px;border-radius:6px;border:1px solid #ccc;">
    Chat Disabled
  </span>
<?php endif; ?>
</td>
    </tr>
<?php if($row['status'] === 'Resolved'): ?>
  <!-- Review section row -->
  <tr>
    <td colspan="7" class="review-box">
      <?php
        $cid = $row['id'];
        $rev = $conn->query("SELECT feedback, admin_response FROM complaint_reviews WHERE complaint_id = $cid");
      ?>

      <?php if ($rev && $rev->num_rows === 0): ?>
        <!-- Give Feedback Button -->
        <button class="review-submit" onclick="toggleFeedbackForm(<?= $cid ?>)">Give Feedback</button>

        <!-- Feedback Form (Initially Hidden) -->
        <div id="feedback-form-<?= $cid ?>" style="display:none; margin-top:10px;">
          <form method="POST">
            <input type="hidden" name="review_complaint_id" value="<?= $cid ?>">
            <span class="feedback-label">Leave feedback:</span><br>
            <textarea name="feedback" rows="3" required></textarea><br>
            <button class="review-submit" type="submit">Submit Review</button>
          </form>
        </div>

      <?php else: ?>
        <?php $r = $rev->fetch_assoc(); ?>
        <p><span class="feedback-label">Your Feedback:</span> <?= nl2br(htmlspecialchars($r['feedback'])) ?></p>
        <?php if ($r['admin_response']): ?>
          <p><span class="feedback-label">Admin Response:</span> <?= nl2br(htmlspecialchars($r['admin_response'])) ?></p>
        <?php endif; ?>
      <?php endif; ?>
    </td>
  </tr>
<?php endif; ?>

  <?php endwhile; ?>
</table>
<?php else: ?>
  <p style="text-align:center;color:#777;">No complaints found.</p>
<?php endif; ?>

<?php $conn->close(); ?>
<script>
  function toggleFeedbackForm(id) {
    const box = document.getElementById('feedback-form-' + id);
    if (box) {
      box.style.display = box.style.display === 'none' ? 'block' : 'none';
    }
  }
</script>
</body>
</html>