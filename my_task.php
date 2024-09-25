<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Fetch the logged-in user's ID
$user_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
$user_query->bind_param("s", $_SESSION['user_email']);
$user_query->execute();
$user_query->bind_result($user_id);
$user_query->fetch();
$user_query->close();

// Fetch the selected status filter from the query string (default to "All")
$status_filter = $_GET['status'] ?? 'All';

// Fetch tasks assigned to the logged-in user based on the selected status filter
$query = "
    SELECT t.id, t.name, t.description, t.due_date, t.status, p.name AS project_name
    FROM tasks t
    INNER JOIN task_assignees ta ON t.id = ta.task_id
    INNER JOIN projects p ON t.project_list = p.id
    WHERE ta.user_id = ?
";

if ($status_filter !== 'All') {
    $query .= " AND t.status = ?";
}

$task_query = $conn->prepare($query);

if ($status_filter === 'All') {
    $task_query->bind_param("i", $user_id);
} else {
    $task_query->bind_param("is", $user_id, $status_filter);
}

$task_query->execute();
$task_query->bind_result($task_id, $task_name, $task_description, $due_date, $task_status, $project_name);

$tasks = [];
while ($task_query->fetch()) {
    $tasks[] = [
        'id' => $task_id,
        'name' => $task_name,
        'description' => $task_description,
        'due_date' => $due_date,
        'status' => $task_status,
        'project_name' => $project_name
    ];
}
$task_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">
        
        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <!-- My Tasks List -->
        <main class="flex-1 p-6 bg-gray-100">
            <div class="container mx-auto p-6">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-semibold mb-4">My Tasks</h2>

                    <!-- Navigation Tabs for Task Status -->
                    <nav class="flex mb-6">
                        <a href="?status=All" class="px-4 py-2 text-sm font-medium <?php echo $status_filter === 'All' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            All Tasks
                        </a>
                        <a href="?status=Pending" class="px-4 py-2 text-sm font-medium <?php echo $status_filter === 'Pending' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            Pending
                        </a>
                        <a href="?status=In Progress" class="px-4 py-2 text-sm font-medium <?php echo $status_filter === 'In Progress' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            In Progress
                        </a>
                        <a href="?status=Complete" class="px-4 py-2 text-sm font-medium <?php echo $status_filter === 'Complete' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            Complete
                        </a>
                    </nav>

                    <!-- Display tasks based on the selected status -->
                    <?php if (empty($tasks)): ?>
                        <p>No tasks assigned to you.</p>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($tasks as $task): ?>
                                <li class="py-4">
                                    <a href="task_detail.php?task_id=<?php echo $task['id']; ?>" class="block">
                                        <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($task['name']); ?></h3>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($task['description']); ?></p>
                                        <p class="text-sm text-gray-500">Due Date: <?php echo htmlspecialchars($task['due_date']); ?> | Project: <?php echo htmlspecialchars($task['project_name']); ?></p>
                                        <p class="text-sm <?php echo $task['status'] === 'Complete' ? 'text-green-500' : ($task['status'] === 'In Progress' ? 'text-yellow-500' : 'text-red-500'); ?>">
                                            Status: <?php echo htmlspecialchars($task['status']); ?>
                                        </p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
