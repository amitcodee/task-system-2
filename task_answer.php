<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_email']) ) {
    header('Location: login.php');
    exit;
}

// Get the current date filter and task status filter from query parameters (default to 'All')
$date_filter = $_GET['filter'] ?? 'All';
$status_filter = $_GET['status'] ?? 'All';  // Default status filter is 'All'

// Define the date ranges based on the filter
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_month_start = date('Y-m-01');

// Prepare SQL conditions based on the date filter
switch ($date_filter) {
    case 'Today':
        $date_condition = "tr.created_at >= '$today 00:00:00' AND tr.created_at <= '$today 23:59:59'";
        break;
    case 'Yesterday':
        $date_condition = "tr.created_at >= '$yesterday 00:00:00' AND tr.created_at <= '$yesterday 23:59:59'";
        break;
    case 'This Week':
        $date_condition = "tr.created_at >= '$this_week_start 00:00:00'";
        break;
    case 'This Month':
        $date_condition = "tr.created_at >= '$this_month_start 00:00:00'";
        break;
    default:
        $date_condition = "1"; // No date filter for 'All'
}

// Dynamically set the status condition based on the filter
switch ($status_filter) {
    case 'In Progress':
        $status_condition = "t.status = 'In Progress'";
        break;
    case 'Complete':
        $status_condition = "t.status = 'Complete'";
        break;
    default:
        $status_condition = "t.status IN ('In Progress', 'Complete')"; // Default is to show both
}

// Fetch the latest update per task based on both date and status filters
$query = "
    SELECT t.id AS task_id, t.name AS task_name, MAX(tr.created_at) AS latest_update, tr.comment, tr.file_path, tr.output_link, u.name AS user_name, p.name AS project_name
    FROM task_responses tr
    INNER JOIN tasks t ON tr.task_id = t.id
    INNER JOIN users u ON tr.user_id = u.id
    INNER JOIN projects p ON t.project_list = p.id
    WHERE $date_condition AND $status_condition
    GROUP BY t.id
    ORDER BY latest_update DESC
";

$result = $conn->query($query);
$task_updates = [];
while ($row = $result->fetch_assoc()) {
    $task_updates[] = $row;
}

// Only fetch assigned users if there are tasks
if (!empty($task_updates)) {
    // Fetch all assigned users for the tasks
    $task_ids = implode(',', array_column($task_updates, 'task_id'));
    
    if (!empty($task_ids)) {
        $assigned_users_query = "
            SELECT ta.task_id, u.name AS assigned_user
            FROM task_assignees ta
            INNER JOIN users u ON ta.user_id = u.id
            WHERE ta.task_id IN ($task_ids)
        ";

        $assigned_users_result = $conn->query($assigned_users_query);

        // Group assigned users by task_id
        $assigned_users = [];
        while ($row = $assigned_users_result->fetch_assoc()) {
            $assigned_users[$row['task_id']][] = $row['assigned_user'];
        }
    } else {
        $assigned_users = [];
    }
} else {
    $assigned_users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Updates</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="flex h-screen">

    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">

        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <div class="flex flex-1">
            <!-- Side Filter -->
            <div class="w-1/4 bg-white p-6 shadow-lg">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Task Status Filter</h3>
                <form action="task_answer.php" method="GET">
                    <!-- Status Dropdown Filter (Exclude 'Pending') -->
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Task Status</label>
                    <select id="status" name="status" class="block w-full p-3 mb-4 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="All" <?php echo $status_filter === 'All' ? 'selected' : ''; ?>>All</option>
                        <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Complete" <?php echo $status_filter === 'Complete' ? 'selected' : ''; ?>>Complete</option>
                    </select>

                    <!-- Date Filter Passed through URL -->
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($date_filter); ?>" />

                    <!-- Submit Button -->
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition">Apply Filter</button>
                </form>
            </div>

            <!-- Main Content Area -->
            <main class="flex-1 p-6 bg-gray-100">
                <div class="container mx-auto p-6">
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Task Updates</h2>

                        <!-- Navigation Tabs for Date Filters -->
                        <nav class="flex mb-6">
                            <a href="?filter=All&status=<?php echo htmlspecialchars($status_filter); ?>" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'All' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                                All
                            </a>
                            <a href="?filter=Today&status=<?php echo htmlspecialchars($status_filter); ?>" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'Today' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                                Today
                            </a>
                            <a href="?filter=Yesterday&status=<?php echo htmlspecialchars($status_filter); ?>" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'Yesterday' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                                Yesterday
                            </a>
                            <a href="?filter=This Week&status=<?php echo htmlspecialchars($status_filter); ?>" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'This Week' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                                This Week
                            </a>
                            <a href="?filter=This Month&status=<?php echo htmlspecialchars($status_filter); ?>" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'This Month' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                                This Month
                            </a>
                        </nav>

                        <!-- Display task updates based on the selected date and status filters -->
                        <?php if (empty($task_updates)): ?>
                            <p class="text-gray-500">No task updates found.</p>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($task_updates as $update): ?>
                                    <li class="py-4">
                                        <a href="view_answer.php?task_id=<?php echo $update['task_id']; ?>" class="block hover:bg-gray-50 p-4 rounded-md transition">
                                            <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($update['task_name']); ?></h3>
                                            <p class="text-sm text-gray-500">Latest update by: <?php echo htmlspecialchars($update['user_name']); ?> | Project: <?php echo htmlspecialchars($update['project_name']); ?></p>

                                            <!-- Display all assigned users -->
                                            <p class="text-sm text-gray-500">Assigned to: 
                                                <?php if (!empty($assigned_users[$update['task_id']])): ?>
                                                    <?php echo implode(', ', $assigned_users[$update['task_id']]); ?>
                                                <?php else: ?>
                                                    No users assigned
                                                <?php endif; ?>
                                            </p>

                                            <p class="text-sm text-gray-500">Date: <?php echo htmlspecialchars($update['latest_update']); ?></p>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($update['comment']); ?></p>
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
</div>

</body>
</html>
