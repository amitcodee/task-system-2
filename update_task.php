<?php
session_start();
include 'config.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the form data
    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $task_priority = $_POST['task_priority'];
    $task_category = $_POST['category'];
    $reminder_time = $_POST['reminder_time'];
    $location = $_POST['location'];
    $task_link = $_POST['task_link'];
    $file_path = $_FILES['file_path']['name'];
    $assigned_user_ids = isset($_POST['assigned_user']) ? $_POST['assigned_user'] : [];
    $removed_old_user_ids = isset($_POST['removed_old_users']) ? $_POST['removed_old_users'] : []; // Get manually removed old users

    // Handle file upload if a file is provided
    if (!empty($_FILES['file_path']['name'])) {
        $target_dir = "uploads/"; // Change to your target directory
        $target_file = $target_dir . basename($_FILES["file_path"]["name"]);
        move_uploaded_file($_FILES["file_path"]["tmp_name"], $target_file);
    }

    // Update the task details
    $stmt = $conn->prepare("
        UPDATE tasks 
        SET name = ?, description = ?, due_date = ?, task_priority = ?, task_category = ?, reminder_time = ?, location = ?, task_link = ?, file_path = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssssssi", $task_name, $description, $due_date, $task_priority, $task_category, $reminder_time, $location, $task_link, $file_path, $task_id);

    if ($stmt->execute()) {
        // First, remove only the users that were explicitly removed by the user
        if (!empty($removed_old_user_ids)) {
            $delete_stmt = $conn->prepare("DELETE FROM task_assignees WHERE task_id = ? AND user_id = ?");
            foreach ($removed_old_user_ids as $removed_user_id) {
                $delete_stmt->bind_param("ii", $task_id, $removed_user_id);
                $delete_stmt->execute();
            }
            $delete_stmt->close();
        }

        // Now, insert the new assignees (new users who have been added or re-added)
        if (!empty($assigned_user_ids)) {
            $insert_stmt = $conn->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
            foreach ($assigned_user_ids as $user_id) {
                // Check if the user is already assigned; if not, insert
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM task_assignees WHERE task_id = ? AND user_id = ?");
                $check_stmt->bind_param("ii", $task_id, $user_id);
                $check_stmt->execute();
                $check_stmt->bind_result($count);
                $check_stmt->fetch();
                $check_stmt->close();

                // Insert the new user if not already assigned
                if ($count == 0) {
                    $insert_stmt->bind_param("ii", $task_id, $user_id);
                    $insert_stmt->execute();
                }
            }
            $insert_stmt->close();
        }

        echo "<script>alert('Task Updated Successfully'); window.location.href='project.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
