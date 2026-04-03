<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$showForm = false;

// ------------------ Check token ------------------
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Look up technician in pending_technicians (not in technician_users)
    $stmt = $conn->prepare("SELECT id, username, email, name, device_type, reset_token_expiry 
                            FROM pending_technicians 
                            WHERE reset_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $tech = $result->fetch_assoc();
    $stmt->close();

    if ($tech) {
        $expiry = strtotime($tech['reset_token_expiry']);
        if ($expiry >= time()) {
            $showForm = true;
            $tech_id = $tech['id'];
            $tech_username = $tech['username'];
            $tech_email = $tech['email'];
            $tech_name = $tech['name'];
            $tech_device = $tech['device_type'];
        } else {
            $message = "❌ This link has expired. Please contact the administrator.";
        }
    } else {
        $message = "❌ Invalid or already used token.";
    }
} else {
    $message = "❌ No token provided.";
}

// ------------------ Handle form submission ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tech_id'])) {
    $tech_id = (int)$_POST['tech_id'];
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $message = "❌ Passwords do not match.";
        $showForm = true;
    } elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters.";
        $showForm = true;
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Get technician info again (from pending_technicians)
        $stmt = $conn->prepare("SELECT username, email, name, device_type 
                                FROM pending_technicians WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $tech_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $tech = $res->fetch_assoc();
        $stmt->close();

        if ($tech) {
            // Insert into technician_users
            $stmt = $conn->prepare("INSERT INTO technician_users 
                (username, email, name, device_type, password, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", 
                $tech['username'], $tech['email'], $tech['name'], $tech['device_type'], $hashedPassword
            );
            $stmt->execute();
            $stmt->close();

            // Remove from pending_technicians
            $stmt = $conn->prepare("DELETE FROM pending_technicians WHERE id = ?");
            $stmt->bind_param("i", $tech_id);
            $stmt->execute();
            $stmt->close();

            $message = "✅ Your account has been activated successfully! You can now log in.";
            $showForm = false;
        } else {
            $message = "❌ Something went wrong. Please contact the administrator.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Set Technician Password</title>
    <link rel="stylesheet" href="set-password.css">
</head>
<body>
<div class="container">
    <h2 style="text-align:center;">Set Your Password</h2>

    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($showForm): ?>
    <form method="POST" id="passwordForm">
        <input type="hidden" name="tech_id" value="<?php echo htmlspecialchars($tech_id); ?>">

        <label>New Password:
            <span class="lock-icon">🔒</span>
        </label>
        <input type="password" name="password" id="password" required>
        <div id="strength-bar"><div id="strength-bar-inner"></div></div>
        <div class="strength-text" id="strength-text">Strength: —</div>

        <label>Confirm Password:
            <span class="lock-icon">🔒</span>
        </label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <button type="submit" id="submitBtn" disabled>Set Password</button>
    </form>
    <?php endif; ?>

    <a href="technician_login.html" class="back-link">← Back to Login</a>
</div>

<script>
// ========== Password Strength Logic ==========
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm_password');
const strengthBar = document.getElementById('strength-bar-inner');
const strengthText = document.getElementById('strength-text');
const submitBtn = document.getElementById('submitBtn');

function checkStrength(password) {
    let strength = 0;
    if (password.length >= 6) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[\W]/.test(password)) strength++;
    return strength;
}

function updateStrength() {
    const pwd = passwordInput.value;
    const confirmPwd = confirmInput.value;
    const strength = checkStrength(pwd);
    let width = (strength / 4) * 100;
    let color = 'red';
    let text = 'Weak';

    if (strength === 2) { color = 'orange'; text = 'Medium'; }
    if (strength >= 3) { color = 'green'; text = 'Strong'; }

    strengthBar.style.width = width + '%';
    strengthBar.style.backgroundColor = color;
    strengthText.textContent = 'Strength: ' + text;

    // Enable submit if passwords match & strength >=2
    submitBtn.disabled = !(pwd === confirmPwd && strength >= 2 && pwd.length>0);
}

passwordInput.addEventListener('input', updateStrength);
confirmInput.addEventListener('input', updateStrength);
</script>
</body>
</html>