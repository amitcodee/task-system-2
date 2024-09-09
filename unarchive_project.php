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

    // Unarchive the project by setting is_archived to 0
    $stmt = $conn->prepare("UPDATE projects SET is_archived = 0 WHERE id = ?");
    $stmt->bind_param("i", $project_id);

    if ($stmt->execute()) {
        // Redirect back to the project page after unarchiving
        header('Location: project.php');
    } else {
        echo "Error unarchiving project.";
    }

    $stmt->close();
}
?>
