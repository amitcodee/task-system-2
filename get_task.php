<?php
include 'config.php';

if (!isset($_GET['task_id'])) {
    echo json_encode(['error' => 'Task ID is missing']);
    exit;
}

$task_id = $_GET['task_id'];

// Fetch the task from the database
$query = $conn->prepare("SELECT id, name, description, due_date FROM tasks WHERE id = ?");
$query->bind_param("i", $task_id);
$query->execute();
$query->bind_result($id, $name, $description, $due_date);
$query->fetch();
$query->close();

echo json_encode([
    'id' => $id,
    'name' => $name,
    'description' => $description,
    'due_date' => $due_date
]);
?>
