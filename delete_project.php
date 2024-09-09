<?php
session_start();
include 'config.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Check if project_id is provided via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    $project_id = $_POST['project_id'];

    // Prepare and execute the delete statement
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);

    if ($stmt->execute()) {
        // Redirect back to the project page after deletion
        header('Location: project.php');
    } else {
        echo "Error deleting project.";
    }

    $stmt->close();
}
?>
