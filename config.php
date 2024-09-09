<?php
// Database configuration
$host = "localhost";        // Server hostname
$username = "root";         // Database username
$password = "";             // Database password (leave empty for localhost)
$dbname = "task";           // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
