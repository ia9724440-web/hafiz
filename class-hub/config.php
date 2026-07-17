<?php
$host = "localhost";
$username = "root";
$password = ""; // Default is empty on local setups (XAMPP)
$dbname = "class_hub";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>