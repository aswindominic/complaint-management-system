<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "complaint_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ----- AJAX username check (check both technician_users and pending_technicians) -----
if (isset($_GET['username'])) {
    $username = strtolower(trim($_GET['username']));

    // check technician_users
    $stmt = $conn->prepare("SELECT id FROM technician_users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'taken']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // check pending_technicians
    $stmt2 = $conn->prepare("SELECT id FROM pending_technicians WHERE username = ? LIMIT 1");
    $stmt2->bind_param("s", $username);
    $stmt2->execute();
    $stmt2->store_result();
    if ($stmt2->num_rows > 0) {
        echo json_encode(['status' => 'taken']);
    } else {
        echo json_encode(['status' => 'available']);
    }
    $stmt2->close();
    $conn->close();
    exit;
}

// ----- AJAX email check (check both technician_users and pending_technicians) -----
if (isset($_GET['check_email'])) {
    $emailToCheck = strtolower(trim($_GET['check_email']));

    // check technician_users
    $stmt = $conn->prepare("SELECT id FROM technician_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $emailToCheck);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'taken']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // check pending_technicians
    $stmt2 = $conn->prepare("SELECT id FROM pending_technicians WHERE email = ? LIMIT 1");
    $stmt2->bind_param("s", $emailToCheck);
    $stmt2->execute();
    $stmt2->store_result();
    if ($stmt2->num_rows > 0) {
        echo json_encode(['status' => 'taken']);
    } else {
        echo json_encode(['status' => 'available']);
    }
    $stmt2->close();
    $conn->close();
    exit;
}

session_start();

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ----- Admin session check -----
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.html");
    exit();
}

// ----- PHPMailer function with username included -----
function sendTechnicianEmail($email, $link, $name, $device, $username) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aswindominic2020@gmail.com';
        $mail->Password = 'wztvcqvwyktljqka';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('aswindominic2020@gmail.com', 'Technician Portal');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Technician Portal';

        $mail->Body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f6f6f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; }
        h2 { color: #333333; }
        p { color: #555555; line-height: 1.5; }
        a.button { display: inline-block; padding: 10px 20px; margin: 15px 0; background-color: #007BFF; color: #ffffff; text-decoration: none; border-radius: 5px; }
        .footer { font-size: 12px; color: #999999; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>Welcome to Technician Portal</h2>
        <p>Hi <strong>$name</strong>,</p>
        <p>Your username for the Technician Portal is: <strong>$username</strong></p>
        <p>You have been added as a technician for the <strong>$device</strong> department.</p>
        <p>To set your password and activate your account, please click the button below (link valid for 24 hours):</p>
        <a href='$link' class='button'>Set Your Password</a>
        <p>If you did not expect this email, please ignore it.</p>
        <div class='footer'>
            &copy; " . date('Y') . " Technician Portal. All rights reserved.
        </div>
    </div>
</body>
</html>
";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device = $conn->real_escape_string($_POST['device_type']);
    $count = (int)$_POST['count'];
    $emailsSent = 0;

    for ($i = 0; $i < $count; $i++) {
        $nameKey = "tech_name_$i";
        $emailKey = "tech_email_$i";

        if (isset($_POST[$nameKey], $_POST[$emailKey])) {
            $name = $conn->real_escape_string(trim($_POST[$nameKey]));
            $email = strtolower($conn->real_escape_string(trim($_POST[$emailKey])));
            if (empty($name) || empty($email)) continue;

            // ---------- Check for duplicate email in both tables ----------
            $exists = false;
            $stmtCheckEmail = $conn->prepare("SELECT id FROM technician_users WHERE email = ? LIMIT 1");
            $stmtCheckEmail->bind_param("s", $email);
            $stmtCheckEmail->execute();
            $stmtCheckEmail->store_result();
            if ($stmtCheckEmail->num_rows > 0) {
                $exists = true;
            }
            $stmtCheckEmail->close();

            if (!$exists) {
                $stmtCheckEmail2 = $conn->prepare("SELECT id FROM pending_technicians WHERE email = ? LIMIT 1");
                $stmtCheckEmail2->bind_param("s", $email);
                $stmtCheckEmail2->execute();
                $stmtCheckEmail2->store_result();
                if ($stmtCheckEmail2->num_rows > 0) {
                    $exists = true;
                }
                $stmtCheckEmail2->close();
            }

            if ($exists) {
                echo "<p style='color:red;'>❌ Technician with email $email already exists (active or pending). Skipping.</p>";
                continue;
            }
            // -----------------------------------------------

            // ---------- Generate username and check for duplicate across both tables ----------
            $username = strtolower(preg_replace("/[^a-zA-Z0-9]/", "_", $name));
            $existsU = false;

            $stmtCheckUsername = $conn->prepare("SELECT id FROM technician_users WHERE username = ? LIMIT 1");
            $stmtCheckUsername->bind_param("s", $username);
            $stmtCheckUsername->execute();
            $stmtCheckUsername->store_result();
            if ($stmtCheckUsername->num_rows > 0) {
                $existsU = true;
            }
            $stmtCheckUsername->close();

            if (!$existsU) {
                $stmtCheckUsername2 = $conn->prepare("SELECT id FROM pending_technicians WHERE username = ? LIMIT 1");
                $stmtCheckUsername2->bind_param("s", $username);
                $stmtCheckUsername2->execute();
                $stmtCheckUsername2->store_result();
                if ($stmtCheckUsername2->num_rows > 0) {
                    $existsU = true;
                }
                $stmtCheckUsername2->close();
            }

            if ($existsU) {
                echo "<p style='color:red;'>❌ Technician username '$username' already exists (active or pending). Skipping.</p>";
                continue;
            }
            // -----------------------------------------------

            $reset_token = bin2hex(random_bytes(16));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // INSERT INTO pending_technicians (NOT technician_users)
            $stmt = $conn->prepare("INSERT INTO pending_technicians 
                (device_type, name, email, username, reset_token, reset_token_expiry, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $device, $name, $email, $username, $reset_token, $reset_expiry);
            $stmt->execute();
            $stmt->close();

            $link = "http://localhost/dashboard/mini_project/set_password.php?token=$reset_token";

            if (sendTechnicianEmail($email, $link, $name, $device, $username)) {
                $emailsSent++;
            }
        }
    }

    $message = "
<div style='padding: 15px; border-radius: 8px; background-color: #e6ffed; color: #2d7a2d; font-weight: bold; margin-top: 15px;'>
    ✅ Successfully processed technician additions for <strong>$device</strong>.<br>
    📧 <strong>$emailsSent</strong> activation email(s) sent to the respective technician(s).
</div>
";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Technicians</title>
    <link rel="stylesheet" href="add-technicians.css">
    <script>
function generateRows() {
    let count = parseInt(document.getElementById('count').value) || 0;
    let container = document.getElementById('techRows');
    container.innerHTML = '';

    const form = document.querySelector("form");

    for (let i = 0; i < count; i++) {
        let row = document.createElement('div');
        row.className = 'tech-row';
        row.innerHTML = `
            <label>Technician ${i+1} Name:</label>
            <input type="text" name="tech_name_${i}" id="tech_name_${i}" required>
            <div id="username_preview_${i}" style="color: #555; font-size: 0.9em;">Username: —</div>

            <label>Email:</label>
            <input type="email" name="tech_email_${i}" id="tech_email_${i}" required>
            <div id="email_preview_${i}" style="color: #555; font-size: 0.9em;">Email: —</div>
            <hr>
        `;
        container.appendChild(row);

        // ---------- Username live preview + DB + local duplicate check ----------
        let nameInput = document.getElementById(`tech_name_${i}`);
        let usernamePreview = document.getElementById(`username_preview_${i}`);

        nameInput.addEventListener('input', () => {
            let username = nameInput.value.trim().toLowerCase().replace(/[^a-zA-Z0-9]/g, "_");

            if (!username) {
                usernamePreview.textContent = "Username: —";
                usernamePreview.style.color = "#555";
                updateSubmitButton();
                return;
            }

            usernamePreview.textContent = `Username: ${username}`;
            usernamePreview.style.color = "#555";

            // AJAX check against DB (same file)
            fetch(`add_technicians.php?username=${encodeURIComponent(username)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'taken') {
                        usernamePreview.textContent = `Username '${username}' is already taken`;
                        usernamePreview.style.color = "red";
                    } else if (data.status === 'available') {
                        // local duplicate check (within the same form)
                        let duplicates = 0;
                        document.querySelectorAll("[id^='tech_name_']").forEach(input => {
                            let val = input.value.trim().toLowerCase().replace(/[^a-zA-Z0-9]/g, "_");
                            if (val === username) duplicates++;
                        });
                        if (duplicates > 1) {
                            usernamePreview.textContent = `Username '${username}' is duplicated in this form`;
                            usernamePreview.style.color = "red";
                        } else {
                            usernamePreview.textContent = `Username: ${username} (available)`;
                            usernamePreview.style.color = "green";
                        }
                    }
                    updateSubmitButton();
                })
                .catch(err => {
                    console.error("Username check error:", err);
                    updateSubmitButton();
                });
        });

        // ---------- Email live preview + DB + local duplicate check ----------
        let emailInput = document.getElementById(`tech_email_${i}`);
        let emailPreview = document.getElementById(`email_preview_${i}`);

        emailInput.addEventListener('input', () => {
            let email = emailInput.value.trim().toLowerCase();

            if (!email) {
                emailPreview.textContent = "Email: —";
                emailPreview.style.color = "#555";
                updateSubmitButton();
                return;
            }

            // basic client-side email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                emailPreview.textContent = "Invalid email format";
                emailPreview.style.color = "red";
                updateSubmitButton();
                return;
            }

            // check against DB via AJAX
            fetch(`add_technicians.php?check_email=${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'taken') {
                        emailPreview.textContent = `Email '${email}' already exists in database`;
                        emailPreview.style.color = "red";
                    } else {
                        // local duplicate check among form emails
                        let duplicates = 0;
                        document.querySelectorAll("[id^='tech_email_']").forEach(input => {
                            if (input.value.trim().toLowerCase() === email) duplicates++;
                        });

                        if (duplicates > 1) {
                            emailPreview.textContent = `Email '${email}' is duplicated in this form`;
                            emailPreview.style.color = "red";
                        } else {
                            emailPreview.textContent = `Email: ${email} (available)`;
                            emailPreview.style.color = "green";
                        }
                    }
                    updateSubmitButton();
                })
                .catch(err => {
                    console.error("Email check error:", err);
                    updateSubmitButton();
                });
        });
    }

    // ---------- button enabling/disabling ----------
    function updateSubmitButton() {
        const submitBtn = document.querySelector("button[type='submit']");
        if (!submitBtn) return;
        const usernamePreviews = document.querySelectorAll("[id^='username_preview_']");
        const emailPreviews = document.querySelectorAll("[id^='email_preview_']");
        let hasError = false;

        usernamePreviews.forEach(div => {
            if (div.textContent.includes("already taken") || div.textContent.includes("duplicated")) {
                hasError = true;
            }
        });
        emailPreviews.forEach(div => {
            if (div.textContent.includes("duplicated") || div.textContent.includes("Invalid email") || div.textContent.includes("already exists")) {
                hasError = true;
            }
        });

        submitBtn.disabled = hasError;
    }

    // ---------- prevent duplicate submit handlers ----------
    if (form._techSubmitHandler) {
        form.removeEventListener('submit', form._techSubmitHandler);
    }
    form._techSubmitHandler = function(e) {
        const usernamePreviews = document.querySelectorAll("[id^='username_preview_']");
        const emailPreviews = document.querySelectorAll("[id^='email_preview_']");
        let blocked = false;

        usernamePreviews.forEach(div => {
            if (div.textContent.includes("already taken") || div.textContent.includes("duplicated")) blocked = true;
        });
        emailPreviews.forEach(div => {
            if (div.textContent.includes("duplicated") || div.textContent.includes("Invalid email") || div.textContent.includes("already exists")) blocked = true;
        });

        if (blocked) {
            e.preventDefault();
            alert("❌ Some usernames or emails are invalid (duplicate, already in DB, or incorrectly formatted). Please fix them before submitting.");
        }
    };
    form.addEventListener('submit', form._techSubmitHandler);
}

</script>
</head>
<body>
<div class="container">
<h2 style="text-align:center;">Add Technicians</h2>

<form method="POST">
    <label>Device Type:</label>
    <select name="device_type" required>
        <option value="" disabled selected>Select a device</option>
        <option value="Smartphone">Smartphone</option>
        <option value="Laptop">Laptop</option>
        <option value="Smart Watch">Smart Watch</option>
        <option value="Desktop">Desktop</option>
        <option value="Television">Television</option>
        <option value="Refrigerator">Refrigerator</option>
        <option value="Washing Machine">Washing Machine</option>
        <option value="Air Conditioner">Air Conditioner</option>
    </select>

    <label>Number of Technicians to Add:</label>
    <input type="number" id="count" name="count" min="1" required onchange="generateRows()">

    <div id="techRows"></div>

    <button type="submit">Add Technicians</button>
</form>

<?php if ($message): ?>
    <p class="message"><?php echo $message; ?></p>
<?php endif; ?>

<a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
</div>
</body>
</html>