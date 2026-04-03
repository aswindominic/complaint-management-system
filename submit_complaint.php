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

$user_id       = $_SESSION['user_id'];
$device_type   = $_POST['device_type'] ?? '';
$complaint_type = $_POST['complaint_type'] ?? '';
$description   = $_POST['description'] ?? '';
$address       = $_POST['address'] ?? '';
$make = $_POST['product_make'] ?? '';
$year = $_POST['product_year'] ?? '';

// ✅ Convert empty year to NULL so MySQL accepts it
$year = ($year === '' || $year === null) ? null : $year;

$image_path = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_name = basename($_FILES['image']['name']);
    $timestamp = time();
    $image_name_clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', $image_name);
    $target_file = $target_dir . "user_" . $user_id . "_" . $timestamp . "_" . $image_name_clean;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_path = $target_file;
    } else {
        die("Failed to upload image.");
    }
}

// Updated query to include product_make and product_year
$sql = "INSERT INTO complaints (user_id, device_type, complaint_type, description, image_path, address, product_make, product_year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// ✅ Change binding type for year to "i" (integer) and allow NULL
$stmt->bind_param("issssssi", $user_id, $device_type, $complaint_type, $description, $image_path, $address, $make, $year);

if ($stmt->execute()) {
    header("Location: view_complaints.php");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>