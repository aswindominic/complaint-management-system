<?php
session_start();

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin-dashboard.php");
        exit;
    } else {
        $loginError = "Invalid admin credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Login</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: url('electronic.png') no-repeat center center fixed;
      background-size: cover;
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-container {
      background-color: rgba(255, 255, 255, 0.85); /* Semi-transparent white */
      padding: 35px 30px;
      border-radius: 28px;
      width: 100%;
      max-width: 320px;
      height: 400px; /* Reduced height */
      color: #333;
      display: flex;
      flex-direction: column;
      justify-content: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 16px;
      border: 1px solid #ccc;
      border-radius: 8px;
      background-color: #f9f9f9;
      color: #333;
      font-size: 15px;
    }

    input[type="submit"] {
      width: 100%;
      background-color: #007bff;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    input[type="submit"]:hover {
      background-color: #0056b3;
    }

    .error {
      color: #ff4d4d;
      text-align: center;
      margin-top: 10px;
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
      color: #007bff;
      text-decoration: none;
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Admin Login</h2>
    <form method="POST" action="">
      <input type="text" name="username" placeholder="Enter admin username" required />
      <input type="password" name="password" placeholder="Enter password" required />
      <input type="submit" value="Login" />
      <?php if ($loginError): ?>
        <div class="error"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>
    </form>
    <div class="back-link">
      <a href="registration.html">Back to User Login</a>
    </div>
  </div>
</body>
</html>