<?php
session_start();
include 'config.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Handle archiving a project
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_id'])) {
    $project_id = $_POST['project_id'];

    // Archive the project by setting is_archived to 1
    $stmt = $conn->prepare("UPDATE projects SET is_archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $project_id);

    if ($stmt->execute()) {
        header('Location: project.php'); // Redirect back to project page
    } else {
        echo "Error archiving project.";
    }

    $stmt->close();
}
?>
