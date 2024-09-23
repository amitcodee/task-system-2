<?php
session_start();
include 'config.php'; // Include your database connection

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if the required POST parameters are set
if (isset($_POST['task_id']) && isset($_POST['new_status'])) {
    $task_id = intval($_POST['task_id']);
    $new_status = $_POST['new_status'];

    // Validate the new status
    $valid_statuses = ['Pending', 'In Progress', 'Complete'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status provided']);
        exit;
    }

    // Fetch the logged-in user's ID
    $user_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $user_query->bind_param("s", $_SESSION['user_email']);
    $user_query->execute();
    $user_query->bind_result($user_id);
    $user_query->fetch();
    $user_query->close();

    if (empty($user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'User ID not found']);
        exit;
    }

    // Check if the user is assigned to the task
    $assigned_query = $conn->prepare("SELECT user_id FROM task_assignees WHERE task_id = ? AND user_id = ?");
    $assigned_query->bind_param("ii", $task_id, $user_id);
    $assigned_query->execute();
    $assigned_query->bind_result($assigned_user_id);
    $assigned_query->fetch();
    $assigned_query->close();

    if (empty($assigned_user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'You are not assigned to this task']);
        exit;
    }

    // Update the task status
    $update_query = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $update_query->bind_param("si", $new_status, $task_id);
    if ($update_query->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Task status updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update task status']);
    }
    $update_query->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
}
?>
