<?php
session_start();
require 'config.php';

$message = "";
$show_form = false;

// Connect to DB
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate token
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $expires_at);
        $stmt->fetch();

        if (strtotime($expires_at) > time()) {
            $show_form = true; // Token valid
        } else {
            $message = "This password reset link has expired.";
        }
    } else {
        $message = "Invalid password reset token.";
    }
    $stmt->close();
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['confirm_password'], $_POST['token'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Get user_id from token
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id);
            $stmt->fetch();

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update user password
            $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt2->bind_param("si", $hashed_password, $user_id);
            $stmt2->execute();

            // Delete token
            $conn->query("DELETE FROM password_resets WHERE user_id = $user_id");

            $message = "Your password has been reset successfully.";
        } else {
            $message = "Invalid token.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:
                linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
                url('https://www.toptal.com/designers/subtlepatterns/uploads/dot-grid.png'),
                linear-gradient(135deg, #2c3e50, #3498db);
            background-size: auto, auto, cover;
            background-position: center;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
            animation: fadeUp 0.8s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 0;
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #2980b9;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            font-size: 14px;
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Reset Password</h2>
        <?php if (!empty($message)) {
            $class = (strpos($message, 'successfully') !== false) ? 'success' : 'error';
            echo "<div class='message $class'>$message</div>";
        } ?>

        <?php if ($show_form): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Reset Password</button>
                <script>
document.querySelector('form').addEventListener('submit', function(e) {
    const password = this.password.value;
    const confirmPassword = this.confirm_password.value;
    const regex = /[A-Z]/;

    if (password.length < 6) {
        alert("⚠️ Password must be at least 6 characters long.");
        e.preventDefault();
        return;
    }

    if (!regex.test(password)) {
        alert("⚠️ Password must contain at least one capital letter.");
        e.preventDefault();
        return;
    }

    if (password !== confirmPassword) {
        alert("⚠️ Passwords do not match.");
        e.preventDefault();
    }
});
</script>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>