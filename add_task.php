<?php
session_start();
include 'config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Variables for handling task data
$task_name = '';
$due_date = '';
$assignee = '';
$project_list = '';

// Handle task addition form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $project_list = $_POST['project_list'];
    $due_date = $_POST['due_date'];
    $assignee = $_POST['assignee'];
    $user_email = $_SESSION['user_email'];

    // Insert the task into the database
    $stmt = $conn->prepare("INSERT INTO tasks (name, description, project_list, due_date, assignee, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $task_name, $description, $project_list, $due_date, $assignee, $user_email);
    
    if ($stmt->execute()) {
        // Success
        header("Location: add_task.php");
    } else {
        // Error
        $error = "Failed to add task!";
    }

    $stmt->close();
}

// Fetch all projects from the database for the logged-in user
$project_query = $conn->prepare("SELECT id, name FROM projects WHERE created_by = ?");
$project_query->bind_param("s", $_SESSION['user_email']);
$project_query->execute();
$project_query->bind_result($project_id, $project_name);
$projects = [];
while ($project_query->fetch()) {
    $projects[] = [
        'id' => $project_id,
        'name' => $project_name
    ];
}
$project_query->close();

// Check if any projects were found
if (empty($projects)) {
    $projects[] = ['id' => '', 'name' => 'No projects found'];
}

// Fetch all users from the database to assign tasks
$user_query = $conn->prepare("SELECT id, name FROM users");
$user_query->execute();
$user_query->bind_result($user_id, $user_name);
$users = [];
while ($user_query->fetch()) {
    $users[] = [
        'id' => $user_id,
        'name' => $user_name
    ];
}
$user_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-6">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <form method="POST" action="add_task.php">
            <!-- Task Name -->
            <div class="mb-4">
                <label for="task_name" class="block text-sm font-medium text-gray-700">Task Name</label>
                <input type="text" name="task_name" id="task_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></textarea>
            </div>

            <!-- Project List -->
            <div class="mb-4">
                <label for="project_list" class="block text-sm font-medium text-gray-700">Project List</label>
                <select name="project_list" id="project_list" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo htmlspecialchars($project['id']); ?>">
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Due Date -->
            <div class="mb-4">
                <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                <input type="date" name="due_date" id="due_date" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
            </div>

            <!-- Assignee -->
            <div class="mb-4">
                <label for="assignee" class="block text-sm font-medium text-gray-700">Assignee</label>
                <select name="assignee" id="assignee" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo htmlspecialchars($user['id']); ?>">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Add Task</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
