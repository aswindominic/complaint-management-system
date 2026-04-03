<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // leave blank for default XAMPP
$dbname = "complaint_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>