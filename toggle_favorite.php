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

    // Retrieve the current is_favorite status of the project
    $query = $conn->prepare("SELECT is_favorite FROM projects WHERE id = ?");
    $query->bind_param("i", $project_id);
    $query->execute();
    $query->bind_result($is_favorite);
    $query->fetch();
    $query->close();

    // Toggle the is_favorite status
    $new_favorite_status = $is_favorite ? 0 : 1;

    // Update the project's is_favorite status
    $stmt = $conn->prepare("UPDATE projects SET is_favorite = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_favorite_status, $project_id);

    if ($stmt->execute()) {
        // Redirect back to the project page after toggling
        header('Location: project.php');
    } else {
        echo "Error updating favorite status.";
    }

    $stmt->close();
}
?>
