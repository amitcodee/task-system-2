<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_email']) ) {
    header('Location: login.php');
    exit;
}

// Fetch the admin's user ID from the database using their email
$admin_email = $_SESSION['user_email'];
$admin_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
$admin_query->bind_param("s", $admin_email);
$admin_query->execute();
$admin_query->bind_result($admin_id);
$admin_query->fetch();
$admin_query->close();

if (!$admin_id) {
    die('Admin user ID not found.');
}

// Fetch the task ID from the query string
$task_id = $_GET['task_id'] ?? null;

if (!$task_id) {
    die('Task ID not specified.');
}

// Set the default limit for the number of updates to display (5 by default)
$update_limit = $_GET['limit'] ?? 5;

// Handle form submission to update task status and add an admin comment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'] ?? null;
    $admin_comment = $_POST['admin_comment'] ?? '';

    // Update task status
    if ($new_status) {
        $update_task_status_query = "UPDATE tasks SET status = ? WHERE id = ?";
        $update_task_status_stmt = $conn->prepare($update_task_status_query);
        $update_task_status_stmt->bind_param("si", $new_status, $task_id);
        $update_task_status_stmt->execute();
        $update_task_status_stmt->close();
    }

    // Add admin comment
    if ($admin_comment) {
        $add_comment_query = "INSERT INTO task_responses (task_id, user_id, comment) VALUES (?, ?, ?)";
        $add_comment_stmt = $conn->prepare($add_comment_query);
        $add_comment_stmt->bind_param("iis", $task_id, $admin_id, $admin_comment);
        $add_comment_stmt->execute();
        $add_comment_stmt->close();
    }

    // Refresh the page after updating
    header("Location: view_answer.php?task_id=$task_id&limit=$update_limit");
    exit;
}

// Fetch task details
$task_query = "
    SELECT t.name AS task_name, t.description, t.due_date, t.status, p.name AS project_name
    FROM tasks t
    INNER JOIN projects p ON t.project_list = p.id
    WHERE t.id = ?
";
$task_stmt = $conn->prepare($task_query);
$task_stmt->bind_param("i", $task_id);
$task_stmt->execute();
$task_stmt->bind_result($task_name, $task_description, $due_date, $status, $project_name);
$task_stmt->fetch();
$task_stmt->close();

// Fetch the latest updates for the specified task (limit based on user selection)
$update_query = "
    SELECT tr.comment, tr.file_path, tr.output_link, tr.created_at, u.name AS user_name
    FROM task_responses tr
    INNER JOIN users u ON tr.user_id = u.id
    WHERE tr.task_id = ?
    ORDER BY tr.created_at DESC
    LIMIT ?
";

$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ii", $task_id, $update_limit);
$update_stmt->execute();
$update_result = $update_stmt->get_result();

$task_updates = [];
while ($row = $update_result->fetch_assoc()) {
    $task_updates[] = $row;
}
$update_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Details and Updates</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="flex h-screen">

    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">

        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <!-- Task Details and Updates -->
        <main class="flex-1 p-6 bg-gray-100">
            <div class="container mx-auto p-6">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    
                    <!-- Task Details Card -->
                    <div class="bg-blue-100 p-6 rounded-lg shadow-md mb-6">
                        <h2 class="text-3xl font-semibold mb-4 text-blue-700"><?php echo htmlspecialchars($task_name); ?></h2>
                        <p class="text-gray-700 mb-2"><strong>Description:</strong> <?php echo htmlspecialchars($task_description); ?></p>
                        <p class="text-gray-700 mb-2"><strong>Due Date:</strong> <?php echo htmlspecialchars($due_date); ?></p>
                        <p class="text-gray-700 mb-2"><strong>Project:</strong> <?php echo htmlspecialchars($project_name); ?></p>

                        <!-- Editable Status Dropdown -->
                        <form method="POST" action="view_answer.php?task_id=<?php echo $task_id; ?>&limit=<?php echo $update_limit; ?>" class="mb-6">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status:</label>
                            <select id="status" name="status" class="block w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Complete" <?php echo $status === 'Complete' ? 'selected' : ''; ?>>Complete</option>
                            </select>

                            <!-- Admin Comment Field -->
                            <div class="mt-4">
                                <label for="admin_comment" class="block text-sm font-medium text-gray-700 mb-2">Admin Comment:</label>
                                <textarea id="admin_comment" name="admin_comment" rows="3" class="block w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Leave a comment if the task updates are not satisfactory"></textarea>
                            </div>

                            <button type="submit" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition">Save Changes</button>
                        </form>
                    </div>

                    <!-- Dropdown for selecting the number of updates to show -->
                    <div class="mb-6">
                        <label for="update-limit" class="block text-sm font-medium text-gray-700 mb-2">Show Updates:</label>
                        <select id="update-limit" name="limit" onchange="window.location.href='?task_id=<?php echo $task_id; ?>&limit=' + this.value" class="block w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="5" <?php echo $update_limit == 5 ? 'selected' : ''; ?>>5</option>
                            <option value="10" <?php echo $update_limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="15" <?php echo $update_limit == 15 ? 'selected' : ''; ?>>15</option>
                            <option value="20" <?php echo $update_limit == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?php echo $update_limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $update_limit == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>

                    <!-- Task Updates -->
                    <h3 class="text-2xl font-semibold mb-4">Task Updates</h3>
                    <?php if (empty($task_updates)): ?>
                        <p>No updates found for this task.</p>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($task_updates as $update): ?>
                                <li class="py-4">
                                    <div class="block">
                                        <p class="text-sm text-gray-500">Updated by: <?php echo htmlspecialchars($update['user_name']); ?></p>
                                        <p class="text-sm text-gray-500">Date: <?php echo htmlspecialchars($update['created_at']); ?></p>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($update['comment']); ?></p>
                                        <?php if (!empty($update['file_path'])): ?>
                                            <p><a href="<?php echo htmlspecialchars($update['file_path']); ?>" class="text-blue-500 hover:underline" target="_blank">View File</a></p>
                                        <?php endif; ?>
                                        <?php if (!empty($update['output_link'])): ?>
                                            <p><a href="<?php echo htmlspecialchars($update['output_link']); ?>" class="text-blue-500 hover:underline" target="_blank">View Link</a></p>
                                        <?php endif; ?>
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
