<?php
session_start();
include 'config.php'; // Include your database connection

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect the form data
    $name = $_POST['member_name'];
    $email = $_POST['member_email'];
    $pass = password_hash($_POST['member_password'], PASSWORD_DEFAULT); // Hash the password
    $role = $_POST['member_role'];

    // Prepare the SQL query to insert the new member
    $stmt = $conn->prepare("INSERT INTO users (name, email, pass, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $pass, $role);

    // Execute the query
    if ($stmt->execute()) {
        echo "Member created successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
