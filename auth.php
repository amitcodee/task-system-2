<?php
session_start();
include 'config.php'; // Include the database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute the query to fetch user details
    $stmt = $conn->prepare("SELECT name, pass, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // If user exists, verify the password
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($name, $stored_password, $role);
        $stmt->fetch();

        // Compare plain text password
        if ($password === $stored_password) {
            // Login successful
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;

            // Redirect all users to the dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid password
            echo "Invalid credentials!";
        }
    } else {
        // Invalid email
        echo "Invalid credentials!";
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect to login page if accessed directly
    header("Location: login.php");
    exit();
}
?>
