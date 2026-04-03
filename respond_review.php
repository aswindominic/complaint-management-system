<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "complaint_system");
    if ($conn->connect_error) {
        die("DB connection failed: " . $conn->connect_error);
    }

    $complaint_id = intval($_POST['complaint_id']);
    $response = $conn->real_escape_string(trim($_POST['admin_response']));

    if ($response === '') {
        die("Response cannot be empty.");
    }

    $sql = "UPDATE complaint_reviews 
            SET admin_response = '$response', responded_at = NOW() 
            WHERE complaint_id = $complaint_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        echo "Error updating response: " . $conn->error;
    }

    $conn->close();
} else {
    echo "Invalid request.";
}
?>