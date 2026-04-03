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

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php");
    exit();
}

// Search logic
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $sql = "SELECT id, username, email, phone FROM users 
            WHERE username LIKE ? OR email LIKE ? OR phone LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
} else {
    $sql = "SELECT id, username, email, phone FROM users";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <link rel="stylesheet" href="manage-users.css">
</head>
<body>
    <div class="container">
        <h1>Manage Users</h1>

        <form method="GET" action="manage_users.php">
    <input type="text" name="search" placeholder="Search by name, email, phone" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
    <a class="reset-link" href="manage_users.php">Reset</a>
</form>
        <table class="user-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php $sn = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td>
                            <a href="manage_users.php?delete=<?= $row['id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>