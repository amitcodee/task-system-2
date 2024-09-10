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

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE tasks SET name = ?, description = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("sssi", $task_name, $description, $due_date, $task_id);

    if ($stmt->execute()) {
        echo" <script> alert('Task Updated Successfully'); window.location.href='project.php'; </script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
