<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized access.");
}

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Initialize filters
$filters = [
    'response_status' => $_GET['response_status'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'device_type' => $_GET['device_type'] ?? '',
    'username' => $_GET['username'] ?? ''
];

// Build dynamic WHERE clause
$where = [];

if ($filters['response_status'] === 'responded') {
    $where[] = "cr.admin_response IS NOT NULL";
} elseif ($filters['response_status'] === 'not_responded') {
    $where[] = "cr.admin_response IS NULL";
}

if (!empty($filters['start_date'])) {
    $where[] = "DATE(cr.created_at) >= '" . $conn->real_escape_string($filters['start_date']) . "'";
}
if (!empty($filters['end_date'])) {
    $where[] = "DATE(cr.created_at) <= '" . $conn->real_escape_string($filters['end_date']) . "'";
}

if (!empty($filters['device_type'])) {
    $where[] = "c.device_type = '" . $conn->real_escape_string($filters['device_type']) . "'";
}

if (!empty($filters['username'])) {
    $where[] = "u.username = '" . $conn->real_escape_string($filters['username']) . "'";
}

$where_clause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT 
            cr.*, 
            c.device_type, 
            c.status, 
            c.assigned_technician, 
            c.resolved_by,
            u.username
        FROM complaint_reviews cr
        JOIN complaints c ON cr.complaint_id = c.id
        JOIN users u ON cr.user_id = u.id
        $where_clause
        ORDER BY cr.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complaint Reviews</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background: linear-gradient(to right, #eef2f3, #f9f9f9);
            padding: 40px 20px;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }

        .section {
            max-width: 900px;
            margin: auto;
        }

        .filter-form {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-form input,
        .filter-form select {
            padding: 8px;
            margin-right: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-family: inherit;
            font-size: 14px;
        }

        .filter-form button {
            padding: 8px 14px;
            background-color: #3498db;
            border: none;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
        }

        .review-card {
            background-color: #fff;
            border-left: 5px solid #3498db;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .review-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        }

        .review-card h3 {
            margin: 0 0 10px;
            color: #2c3e50;
        }

        .badge-technician {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 50px;
            margin-top: 8px;
            font-weight: 600;
        }

        .badge-admin {
            background-color: #2ecc71;
        }

        .admin-response {
            background-color: #ecfdf5;
            padding: 15px 18px;
            margin-top: 18px;
            border-left: 4px solid #27ae60;
            border-radius: 8px;
        }

        .response-form {
            margin-top: 20px;
        }

        .response-form textarea {
            width: 100%;
            height: 90px;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px;
            font-family: inherit;
            font-size: 14px;
            transition: border 0.2s;
        }

        .response-form textarea:focus {
            border-color: #2980b9;
            outline: none;
        }

        .response-form button {
            margin-top: 12px;
            background-color: #2980b9;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .response-form button:hover {
            background-color: #2471a3;
            transform: scale(1.02);
        }

        .timestamp {
            color: #888;
            font-size: 0.85em;
            margin-top: 8px;
        }

        p {
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="section">
    <h1>User Complaint Reviews</h1>

    
<!-- Filter Form -->
<form method="GET" class="filter-form" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
    <select name="response_status">
        <option value="">-- Response Status --</option>
        <option value="responded" <?= $filters['response_status'] === 'responded' ? 'selected' : '' ?>>Responded</option>
        <option value="not_responded" <?= $filters['response_status'] === 'not_responded' ? 'selected' : '' ?>>Not Responded</option>
    </select>

    <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>">
    

    <input type="text" name="device_type" placeholder="Device Type" value="<?= htmlspecialchars($filters['device_type']) ?>">
    <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($filters['username']) ?>">

    <button type="submit">Apply Filters</button>

    <a href="view_reviews.php" style="text-decoration: none;">
        <span style="
            padding: 8px 14px;
            background-color: #e74c3c;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
        ">Clear Filters</span>
    </a>
</form>
    <!-- Reviews -->
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='review-card'>";
            echo "<h3>Complaint ID: " . htmlspecialchars($row['complaint_id']) . "</h3>";
            echo "<p><strong>User:</strong> " . htmlspecialchars($row['username']) . "</p>";
            echo "<p><strong>Device:</strong> " . htmlspecialchars($row['device_type']) . "</p>";

            if ($row['resolved_by'] === 'technician' && !empty($row['assigned_technician'])) {
                echo "<p><span class='badge-technician'>Resolved by Technician: " . htmlspecialchars($row['assigned_technician']) . "</span></p>";
            } elseif ($row['resolved_by'] === 'admin') {
                echo "<p><span class='badge-technician badge-admin'>Resolved by Admin</span></p>";
            }

            echo "<p><strong>Review:</strong><br>" . nl2br(htmlspecialchars($row['feedback'])) . "</p>";
            echo "<p class='timestamp'><em>Submitted on: " . htmlspecialchars($row['created_at']) . "</em></p>";

            if ($row['admin_response']) {
                echo "<div class='admin-response'>";
                echo "<strong>Admin Response:</strong><br>" . nl2br(htmlspecialchars($row['admin_response']));
                echo "<p class='timestamp'><em>Responded at: " . htmlspecialchars($row['responded_at']) . "</em></p>";
                echo "</div>";
            } else {
                echo "<form method='POST' action='respond_review.php' class='response-form'>";
                echo "<input type='hidden' name='complaint_id' value='" . $row['complaint_id'] . "'>";
                echo "<label for='admin_response'><strong>Your Response:</strong></label>";
                echo "<textarea name='admin_response' required></textarea><br>";
                echo "<button type='submit'>Submit Response</button>";
                echo "</form>";
            }

            echo "</div>";
        }
    } else {
        echo "<p>No reviews found matching your filters.</p>";
    }

    $conn->close();
    ?>
</div>

</body>
</html>