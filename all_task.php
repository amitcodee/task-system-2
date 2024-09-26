<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Get status from URL parameters (default to "All")
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';

// Modify query to filter tasks based on status
$query = "
    SELECT 
        t.name AS task_name,  /* Assuming the column name is 'name' in the tasks table */
        t.due_date, 
        t.status, 
        GROUP_CONCAT(u.name SEPARATOR ', ') as assigned_to
    FROM tasks t
    LEFT JOIN task_assignees ta ON t.id = ta.task_id
    LEFT JOIN users u ON ta.user_id = u.id
    WHERE t.status LIKE ?
    GROUP BY t.id
";

// Adjust status filter for the SQL query
$status_filter_sql = ($status_filter === 'All') ? '%' : $status_filter;

// Prepare and execute the query with the status filter
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $status_filter_sql);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
if ($result->num_rows > 0) {
    // Fetch all tasks and their assigned users
    while ($row = $result->fetch_assoc()) {
        $tasks[] = [
            'task_name' => $row['task_name'] ?? '', // Default to empty string if null
            'assigned_to' => $row['assigned_to'] ?? 'No users assigned', // Default message if null
            'status' => $row['status'] ?? 'No status',
            'due_date' => $row['due_date'] ?? 'No due date'
        ];
    }
} else {
    // echo "No tasks found.";
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .nav-tab {
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.3s;
        }
        .nav-tab-active {
            color: #2563eb; /* Tailwind's blue-600 */
            border-bottom: 2px solid #2563eb; /* Blue underline */
        }
        .nav-tab-inactive {
            color: #6b7280; /* Tailwind's gray-500 */
            border-bottom: 2px solid transparent;
        }
        .nav-tab:hover {
            color: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex h-screen">
    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">
        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 bg-gray-100">
            <div class="container mx-auto">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">All Tasks</h2>

                    <!-- Navigation Tabs for Task Status -->
                    <nav class="flex space-x-4 mb-6">
                        <a href="?status=All" class="nav-tab <?php echo ($status_filter === 'All') ? 'nav-tab-active' : 'nav-tab-inactive'; ?>">All</a>
                        <a href="?status=In Progress" class="nav-tab <?php echo ($status_filter === 'In Progress') ? 'nav-tab-active' : 'nav-tab-inactive'; ?>">In Progress</a>
                        <a href="?status=Complete" class="nav-tab <?php echo ($status_filter === 'Complete') ? 'nav-tab-active' : 'nav-tab-inactive'; ?>">Complete</a>
                        <a href="?status=Pending" class="nav-tab <?php echo ($status_filter === 'Pending') ? 'nav-tab-active' : 'nav-tab-inactive'; ?>">Pending</a>
                        <a href="?status=Overdue" class="nav-tab <?php echo ($status_filter === 'Overdue') ? 'nav-tab-active' : 'nav-tab-inactive'; ?>">Overdue</a>
                    </nav>

                    <!-- Display task updates based on the selected status -->
                    <?php if (empty($tasks)): ?>
                        <p class="text-gray-500">No task updates found.</p>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($tasks as $task): ?>
                                <li class="py-4">
                                    <div class="flex justify-between">
                                        <div>
                                            <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($task['task_name']); ?></h3>
                                            <p class="text-sm text-gray-500">Assigned to: <?php echo htmlspecialchars($task['assigned_to']); ?></p>
                                            <p class="text-sm text-gray-500">Due Date: <?php echo htmlspecialchars($task['due_date']); ?></p>
                                        </div>
                                        <div>
                                            <span class="inline-block bg-<?php echo ($task['status'] === 'Complete') ? 'green' : ($task['status'] === 'Pending' ? 'yellow' : 'red'); ?>-200 text-<?php echo ($task['status'] === 'Complete') ? 'green' : ($task['status'] === 'Pending' ? 'yellow' : 'red'); ?>-700 px-3 py-1 rounded-full text-sm font-semibold">
                                                <?php echo htmlspecialchars($task['status']); ?>
                                            </span>
                                        </div>
                                    </div>
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
