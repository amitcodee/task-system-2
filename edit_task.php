<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    // Update the task in the database
    $stmt = $conn->prepare("UPDATE tasks SET name = ?, description = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("sssi", $task_name, $description, $due_date, $task_id);

    if ($stmt->execute()) {
        header("Location: project_tasks.php?project_id=" . $_GET['project_id'] . "&success=1");
    } else {
        echo "Error updating task: " . $stmt->error;
    }

    $stmt->close();
}
?>
