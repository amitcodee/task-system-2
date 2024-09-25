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
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : ''; // Get priority filter from the URL

// Fetch project details
$project_query = $conn->prepare("SELECT name FROM projects WHERE id = ?");
$project_query->bind_param("i", $project_id);
$project_query->execute();
$project_query->bind_result($project_name);
$project_query->fetch();
$project_query->close();

// Fetch tasks related to the project, filtering by priority if set
$task_query = $conn->prepare("
    SELECT 
        t.id, t.name, t.description, t.due_date, t.task_priority, t.task_category, t.reminder_time, t.location, t.task_link, t.file_path, t.created_at,
        GROUP_CONCAT(u.name SEPARATOR ', ') AS assigned_users
    FROM tasks t
    LEFT JOIN task_assignees ta ON t.id = ta.task_id
    LEFT JOIN users u ON ta.user_id = u.id
    WHERE t.project_list = ? " . ($priority_filter ? "AND t.task_priority = ?" : "") . "
    GROUP BY t.id
");
if ($priority_filter) {
    $task_query->bind_param("is", $project_id, $priority_filter);
} else {
    $task_query->bind_param("i", $project_id);
}
$task_query->execute();
$task_query->bind_result($task_id, $task_name, $task_description, $due_date, $task_priority, $category, $reminder_time, $location, $task_link, $file_path, $created_at, $assigned_users);

$tasks = [];
while ($task_query->fetch()) {
    $tasks[] = [
        'id' => $task_id,
        'name' => $task_name,
        'description' => $task_description,
        'due_date' => $due_date,
        'task_priority' => $task_priority,
        'category' => $category,
        'reminder_time' => $reminder_time,
        'location' => $location,
        'task_link' => $task_link,
        'file_path' => $file_path,
        'created_at' => $created_at,
        'assigned_users' => $assigned_users ?? 'Unassigned'
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
                        <h1 class="text-3xl font-semibold mb-6 text-gray-800"><?php echo htmlspecialchars($project_name ?? ''); ?> - Tasks</h1>

                        <!-- Priority Tabs -->
                        <div class="mb-6">
                            <nav class="flex space-x-4">
                            <a href="?project_id=<?php echo $project_id; ?>"
                                   class="px-3 py-2 font-medium <?php echo $priority_filter === '' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-200'; ?> rounded-lg">
                                    All Tasks
                                </a>
                                <a href="?project_id=<?php echo $project_id; ?>&priority=Low"
                                   class="px-3 py-2 font-medium <?php echo $priority_filter === 'Low' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-200'; ?> rounded-lg">
                                    Low Priority
                                </a>
                                <a href="?project_id=<?php echo $project_id; ?>&priority=Medium"
                                   class="px-3 py-2 font-medium <?php echo $priority_filter === 'Medium' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-200'; ?> rounded-lg">
                                    Medium Priority
                                </a>
                                <a href="?project_id=<?php echo $project_id; ?>&priority=High"
                                   class="px-3 py-2 font-medium <?php echo $priority_filter === 'High' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-200'; ?> rounded-lg">
                                    High Priority
                                </a>
                               
                            </nav>
                        </div>

                        <!-- Display tasks -->
                        <?php if (empty($tasks)): ?>
                            <p class="text-gray-600">No tasks found for this project.</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="bg-white p-4 rounded-lg shadow-lg hover:shadow-xl transition duration-300">
                                        <h2 class="text-xl font-semibold text-gray-800 mb-2">
                                            <?php echo htmlspecialchars($task['name']); ?>
                                        </h2>
                                        <p class="text-gray-600 mb-2">
                                            <?php echo htmlspecialchars($task['description']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <strong>Due Date:</strong> <?php echo htmlspecialchars($task['due_date']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <strong>Assigned to:</strong> <?php echo htmlspecialchars($task['assigned_users']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <strong>Priority:</strong> <?php echo htmlspecialchars($task['task_priority'] ?? 'Not Set'); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <strong>Category:</strong> <?php echo htmlspecialchars($task['category'] ?? 'Not Set'); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <strong>Created At:</strong> <?php echo htmlspecialchars($task['created_at']); ?>
                                        </p>
                                       <div class="mt-5">
                                       <a href="edit_task.php?task_id=<?php echo $task['id']; ?>" 
                                           class="mt-5 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none transition duration-300">
                                            Edit Task
                                        </a>
                                       </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

    </div>

</body>

</html>
