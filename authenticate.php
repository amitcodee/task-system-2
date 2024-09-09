<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture form input
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Admin login credentials
    $admin_email = 'admin@example.com';
    $admin_password = 'admin';

    // Check if the entered credentials match the admin credentials
    if ($email === $admin_email && $password === $admin_password) {
        // Store session variables and redirect to dashboard
        $_SESSION['user'] = 'admin';
        header('Location: dashboard.php');
        exit;
    } else {
        echo "<script>alert('Invalid email or password'); window.history.back();</script>";
    }
}
