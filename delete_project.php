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

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete all tasks related to the project
        $delete_tasks_stmt = $conn->prepare("DELETE FROM tasks WHERE project_list = ?");
        $delete_tasks_stmt->bind_param("i", $project_id);
        $delete_tasks_stmt->execute();
        $delete_tasks_stmt->close();

        // Delete the project itself
        $delete_project_stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $delete_project_stmt->bind_param("i", $project_id);
        $delete_project_stmt->execute();
        $delete_project_stmt->close();

        // Commit the transaction
        $conn->commit();

        // Set a success message in the session
        $_SESSION['message'] = "Project and its tasks were successfully deleted.";

        // Redirect back to the project page after deletion
        header('Location: project.php');
        exit;
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $conn->rollback();

        // Set an error message in the session
        $_SESSION['error'] = "Error deleting project and its tasks: " . $e->getMessage();
        
        // Redirect back to the project page with an error
        header('Location: project.php');
        exit;
    }
}
