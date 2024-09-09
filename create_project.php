<?php
session_start();
include 'config.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $project_name = trim($_POST['project_name']);
    $color = trim($_POST['color']);
    $created_by = $_SESSION['user_email']; // Use logged-in user's email as 'created_by'

    // Validate form data
    if (empty($project_name) || empty($color)) {
        // Redirect back to the projects page with an error message
        echo "<script>alert('Please fill in all the fields.'); window.location.href = 'project.php';</script>";
        exit;
    }

    // Prepare and execute the query to insert a new project into the database
    $stmt = $conn->prepare("INSERT INTO projects (name, color, created_by, is_favorite, is_archived) VALUES (?, ?, ?, 0, 0)");
    $stmt->bind_param("sss", $project_name, $color, $created_by);

    // Execute the query
    if ($stmt->execute()) {
        // Redirect back to the projects page with a success message
        echo "<script>alert('Project created successfully!'); window.location.href = 'project.php';</script>";
    } else {
        // Redirect back with an error message
        echo "<script>alert('Error creating project. Please try again.'); window.location.href = 'project.php';</script>";
    }

    // Close the statement
    $stmt->close();
}
?>
