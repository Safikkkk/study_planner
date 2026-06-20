<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Enable MySQLi error reporting (useful during development)
// Error reporting: use MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT only in development
// For production hosting, set to MYSQLI_REPORT_OFF to prevent exposing errors to users
mysqli_report(MYSQLI_REPORT_OFF);

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "study_planner";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed.");
}

// Set charset
$conn->set_charset("utf8mb4");
?>