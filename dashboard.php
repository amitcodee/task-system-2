<?php
session_start();
include 'config.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Fetch the logged-in user's role and ID
$user_email = $_SESSION['user_email'];
$user_query = $conn->prepare("SELECT id, role FROM users WHERE email = ?");
$user_query->bind_param("s", $user_email);
$user_query->execute();
$user_query->bind_result($user_id, $user_role);
$user_query->fetch();
$user_query->close();

// Conditionally set the query filter for non-admin users
$query_condition = "";
if ($user_role !== 'Admin') {
    $query_condition = " AND task_assignees.user_id = $user_id";
}

// Fetch card data for all-time stats

// Total tasks (For Admin: all tasks, For other users: only their tasks)
$total_tasks_query = "
    SELECT COUNT(DISTINCT tasks.id) as total_tasks 
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE 1=1 $query_condition
";
$total_tasks_result = $conn->query($total_tasks_query);
$total_tasks_count = $total_tasks_result->fetch_assoc()['total_tasks'];

// Pending tasks
$pending_tasks_query = "
    SELECT COUNT(DISTINCT tasks.id) as pending_tasks 
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE tasks.status = 'Pending' $query_condition
";
$pending_tasks_result = $conn->query($pending_tasks_query);
$pending_tasks_count = $pending_tasks_result->fetch_assoc()['pending_tasks'];

// In-progress tasks
$in_progress_tasks_query = "
    SELECT COUNT(DISTINCT tasks.id) as in_progress_tasks 
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE tasks.status = 'In Progress' $query_condition
";
$in_progress_tasks_result = $conn->query($in_progress_tasks_query);
$in_progress_tasks_count = $in_progress_tasks_result->fetch_assoc()['in_progress_tasks'];

// Completed tasks
$completed_tasks_query = "
    SELECT COUNT(DISTINCT tasks.id) as completed_tasks 
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE tasks.status = 'Complete' $query_condition
";
$completed_tasks_result = $conn->query($completed_tasks_query);
$completed_tasks_count = $completed_tasks_result->fetch_assoc()['completed_tasks'];

// Today's assigned tasks
$today_date = date('Y-m-d');
$today_tasks_query = "
    SELECT COUNT(DISTINCT tasks.id) as today_tasks 
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE DATE(tasks.created_at) = '$today_date' $query_condition
";
$today_tasks_result = $conn->query($today_tasks_query);
$today_tasks_count = $today_tasks_result->fetch_assoc()['today_tasks'];

// This week's assigned tasks
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$week_tasks_query = "
    SELECT COUNT(DISTINCT tasks.id) as week_tasks 
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE DATE(tasks.created_at) >= '$this_week_start' $query_condition
";
$week_tasks_result = $conn->query($week_tasks_query);
$week_tasks_count = $week_tasks_result->fetch_assoc()['week_tasks'];

// Task breakdown for the current month
$this_month_start = date('Y-m-01');
$month_task_status_query = "
    SELECT 
        SUM(CASE WHEN tasks.status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN tasks.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN tasks.status = 'Complete' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE tasks.created_at >= '$this_month_start' $query_condition
";
$month_task_status_result = $conn->query($month_task_status_query);
$month_task_status_data = $month_task_status_result->fetch_assoc();

// Task breakdown for the current week
$week_task_status_query = "
    SELECT 
        SUM(CASE WHEN tasks.status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN tasks.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN tasks.status = 'Complete' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE tasks.created_at >= '$this_week_start' $query_condition
";
$week_task_status_result = $conn->query($week_task_status_query);
$week_task_status_data = $week_task_status_result->fetch_assoc();

// Task breakdown for all time
$all_time_task_status_query = "
    SELECT 
        SUM(CASE WHEN tasks.status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN tasks.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN tasks.status = 'Complete' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks 
    LEFT JOIN task_assignees ON tasks.id = task_assignees.task_id
    WHERE 1=1 $query_condition
";
$all_time_task_status_result = $conn->query($all_time_task_status_query);
$all_time_task_status_data = $all_time_task_status_result->fetch_assoc();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Include Sidenav -->
        <?php include 'sidenav.php'; ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Include Header -->
            <?php include 'header.php'; ?>

            <!-- Dashboard Content -->
            <main class="flex-1 p-6 bg-gray-100">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h4 class="text-lg font-semibold">Total Tasks</h4>
                        <p class="text-gray-600 text-sm">
                            <?php echo $total_tasks_count; ?> 
                        </p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <h4 class="text-lg font-semibold">Pending Tasks</h4>
                        <p class="text-gray-600 text-sm">
                            <?php echo $pending_tasks_count; ?> 
                        </p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <h4 class="text-lg font-semibold">In-Progress Tasks</h4>
                        <p class="text-gray-600 text-sm">
                            <?php echo $in_progress_tasks_count; ?> 
                        </p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <h4 class="text-lg font-semibold">Completed Tasks</h4>
                        <p class="text-gray-600 text-sm">
                            <?php echo $completed_tasks_count; ?> 
                        </p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <h4 class="text-lg font-semibold">Today's Assigned Tasks</h4>
                        <p class="text-gray-600 text-sm">
                            <?php echo $today_tasks_count; ?> 
                        </p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <h4 class="text-lg font-semibold">This Week's Assigned Tasks</h4>
                        <p class="text-gray-600 text-sm">
                            <?php echo $week_tasks_count; ?> 
                        </p>
                    </div>
                </div>

                <!-- Task Status Pie Charts for Month, Week, All-Time -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Month-Based Task Breakdown -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-lg font-semibold mb-4">Task Breakdown (Month)</h4>
                        <canvas id="monthTaskStatusChart"></canvas>
                    </div>

                    <!-- Week-Based Task Breakdown -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-lg font-semibold mb-4">Task Breakdown (Week)</h4>
                        <canvas id="weekTaskStatusChart"></canvas>
                    </div>

                    <!-- All-Time Task Breakdown -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-lg font-semibold mb-4">Task Breakdown (All Time)</h4>
                        <canvas id="allTimeTaskStatusChart"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Pie Charts for Task Status -->
    <script>
        // Monthly Task Status Chart
        const ctxMonth = document.getElementById('monthTaskStatusChart').getContext('2d');
        const monthTaskStatusChart = new Chart(ctxMonth, {
            type: 'pie',
            data: {
                labels: ['Pending', 'In Progress', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $month_task_status_data['pending_tasks']; ?>,
                        <?php echo $month_task_status_data['in_progress_tasks']; ?>,
                        <?php echo $month_task_status_data['completed_tasks']; ?>
                    ],
                    backgroundColor: ['#fbbf24', '#3b82f6', '#10b981'],
                    borderColor: ['#f59e0b', '#2563eb', '#047857'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
            }
        });

        // Weekly Task Status Chart
        const ctxWeek = document.getElementById('weekTaskStatusChart').getContext('2d');
        const weekTaskStatusChart = new Chart(ctxWeek, {
            type: 'pie',
            data: {
                labels: ['Pending', 'In Progress', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $week_task_status_data['pending_tasks']; ?>,
                        <?php echo $week_task_status_data['in_progress_tasks']; ?>,
                        <?php echo $week_task_status_data['completed_tasks']; ?>
                    ],
                    backgroundColor: ['#fbbf24', '#3b82f6', '#10b981'],
                    borderColor: ['#f59e0b', '#2563eb', '#047857'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
            }
        });

        // All-Time Task Status Chart
        const ctxAllTime = document.getElementById('allTimeTaskStatusChart').getContext('2d');
        const allTimeTaskStatusChart = new Chart(ctxAllTime, {
            type: 'pie',
            data: {
                labels: ['Pending', 'In Progress', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $all_time_task_status_data['pending_tasks']; ?>,
                        <?php echo $all_time_task_status_data['in_progress_tasks']; ?>,
                        <?php echo $all_time_task_status_data['completed_tasks']; ?>
                    ],
                    backgroundColor: ['#fbbf24', '#3b82f6', '#10b981'],
                    borderColor: ['#f59e0b', '#2563eb', '#047857'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
            }
        });
    </script>
</body>
</html>
