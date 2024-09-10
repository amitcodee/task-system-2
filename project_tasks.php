<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Check if project_id is provided in the URL
if (!isset($_GET['project_id'])) {
    echo "Project ID is missing.";
    exit;
}

$project_id = $_GET['project_id'];

// Fetch project details
$project_query = $conn->prepare("SELECT name FROM projects WHERE id = ?");
$project_query->bind_param("i", $project_id);
$project_query->execute();
$project_query->bind_result($project_name);
$project_query->fetch();
$project_query->close();

// Fetch tasks related to the project
$task_query = $conn->prepare("SELECT id, name, description, due_date FROM tasks WHERE project_list = ?");
$task_query->bind_param("i", $project_id);
$task_query->execute();
$task_query->bind_result($task_id, $task_name, $task_description, $due_date);

$tasks = [];
while ($task_query->fetch()) {
    $tasks[] = [
        'id' => $task_id,
        'name' => $task_name,
        'description' => $task_description,
        'due_date' => $due_date
    ];
}
$task_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">
    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <main class="flex-1 p-6 bg-gray-100">
        <div class="container mx-auto p-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h1 class="text-2xl font-semibold mb-4"><?php echo htmlspecialchars($project_name ?? ''); ?> - Tasks</h1>

                <!-- Display tasks -->
                <?php if (empty($tasks)): ?>
                    <p>No tasks found for this project.</p>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($tasks as $task): ?>
                            <li class="py-4 cursor-pointer" 
                                onclick="openEditTaskModal(
                                    '<?php echo $task['id']; ?>', 
                                    '<?php echo htmlspecialchars($task['name'] ?? ''); ?>', 
                                    '<?php echo htmlspecialchars($task['description'] ?? ''); ?>', 
                                    '<?php echo htmlspecialchars($task['due_date'] ?? ''); ?>')">
                                <h2 class="text-xl font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($task['name'] ?? ''); ?>
                                </h2>
                                <p class="text-gray-600">
                                    <?php echo htmlspecialchars($task['description'] ?? ''); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Due Date: <?php echo htmlspecialchars($task['due_date'] ?? ''); ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal for editing task -->
<div id="editTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <h2 class="text-xl font-semibold mb-4">Edit Task</h2>
        <form id="editTaskForm" action="update_task.php" method="POST">
            <input type="hidden" name="task_id" id="task_id">
            <div class="mb-4">
                <label for="task_name" class="block text-sm font-medium text-gray-700">Task Name</label>
                <input type="text" name="task_name" id="modal_task_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="modal_description" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required></textarea>
            </div>

            <div class="mb-4">
                <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                <input type="date" name="due_date" id="modal_due_date" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditTaskModal()" class="bg-gray-400 text-white px-4 py-2 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Update Task</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditTaskModal(taskId, taskName, taskDescription, taskDueDate) {
        // Fill modal inputs with task data
        document.getElementById('task_id').value = taskId;
        document.getElementById('modal_task_name').value = taskName;
        document.getElementById('modal_description').value = taskDescription;
        document.getElementById('modal_due_date').value = taskDueDate;

        // Show the modal
        document.getElementById('editTaskModal').classList.remove('hidden');
    }

    function closeEditTaskModal() {
        // Hide the modal
        document.getElementById('editTaskModal').classList.add('hidden');
    }
</script>
