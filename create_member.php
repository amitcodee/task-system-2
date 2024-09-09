<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['member_name'];
    $email = $_POST['member_email'];
    $password = $_POST['member_password'];
    $role = $_POST['member_role'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        echo "<script>alert('All fields are required!'); window.location.href = 'member.php';</script>";
        exit;
    }

    // Check if email is already in use
    $check_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_query->bind_param("s", $email);
    $check_query->execute();
    $check_query->store_result();

    if ($check_query->num_rows > 0) {
        echo "<script>alert('Email already in use.'); window.location.href = 'member.php';</script>";
        $check_query->close();
        exit;
    }
    $check_query->close();

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new member into the users table
    $query = $conn->prepare("INSERT INTO users (name, email, pass, role) VALUES (?, ?, ?, ?)");
    if (!$query) {
        echo "<script>alert('Database error. Please try again later.'); window.location.href = 'member.php';</script>";
        exit;
    }
    $query->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($query->execute()) {
        echo "<script>alert('Member created successfully!'); window.location.href = 'member.php';</script>";
    } else {
        echo "<script>alert('Error creating member. Please try again later.'); window.location.href = 'member.php';</script>";
    }

    $query->close();
} else {
    header('Location: member.php'); // Redirect if accessed directly
}
?>
