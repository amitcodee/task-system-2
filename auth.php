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

        // Use password_verify() if passwords are hashed
        if (password_verify($password, $stored_password)) {
            // Login successful, set session variables
            $_SESSION['user_email'] = $email;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid credentials!";
            header("Location: login.php");
            exit();
        }
    } else {
        // Invalid email
        $_SESSION['error'] = "Invalid credentials!";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // If accessed directly, redirect to login page
    header("Location: login.php");
    exit();
}
