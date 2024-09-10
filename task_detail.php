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

if (empty($user_id)) {
    die('User ID not found.');
}

// Fetch the task ID from the query string
$task_id = $_GET['task_id'];

// Check if the user is assigned to the task in task_assignees
$assigned_query = $conn->prepare("SELECT user_id FROM task_assignees WHERE task_id = ? AND user_id = ?");
$assigned_query->bind_param("ii", $task_id, $user_id);
$assigned_query->execute();
$assigned_query->bind_result($assigned_user_id);
$assigned_query->fetch();
$assigned_query->close();

if (empty($assigned_user_id)) {
    die('You are not assigned to this task.');
}

// Fetch the task details
$task_query = $conn->prepare("
    SELECT tasks.name, tasks.description, tasks.due_date, tasks.status, projects.name as project_name
    FROM tasks
    INNER JOIN projects ON tasks.project_list = projects.id
    WHERE tasks.id = ?
");
$task_query->bind_param("i", $task_id);
$task_query->execute();
$task_query->bind_result($task_name, $task_description, $due_date, $status, $project_name);
$task_query->fetch();
$task_query->close();

// Handle comment/file submission or task completion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_update'])) {
        $comment = $_POST['comment'];
        $output_link = $_POST['output_link'];

        // Handle file upload
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
            $upload_dir = 'uploads/';
            $file_name = basename($_FILES['file_upload']['name']);
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO task_responses (task_id, user_id, comment, file_path, output_link) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $task_id, $assigned_user_id, $comment, $target_file, $output_link);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // Store only comment and link in the database
            $stmt = $conn->prepare("INSERT INTO task_responses (task_id, user_id, comment, output_link) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $task_id, $assigned_user_id, $comment, $output_link);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['complete_task'])) {
        // Mark task as complete
        $stmt = $conn->prepare("UPDATE tasks SET status = 'Complete' WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch task responses
$response_query = $conn->prepare("
    SELECT tr.comment, tr.file_path, tr.output_link, tr.created_at, u.name, u.profile_image 
    FROM task_responses tr 
    JOIN users u ON tr.user_id = u.id 
    WHERE tr.task_id = ?
");
$response_query->bind_param("i", $task_id);
$response_query->execute();
$response_query->bind_result($comment, $file_path, $output_link, $created_at, $username, $profile_image);

$responses = [];
while ($response_query->fetch()) {
    $responses[] = [
        'comment' => $comment,
        'file_path' => $file_path,
        'output_link' => $output_link,
        'created_at' => $created_at,
        'username' => $username,
        'profile_image' => $profile_image
    ];
}
$response_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Details</title>
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
                    <h2 class="text-2xl font-semibold mb-4"><?php echo htmlspecialchars($task_name); ?> - Task Details</h2>
                    <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($task_description); ?></p>
                    <p class="text-sm text-gray-500">Due Date: <?php echo htmlspecialchars($due_date); ?> | Project: <?php echo htmlspecialchars($project_name); ?></p>
                    <p class="text-sm text-gray-500">Status: <span class="<?php echo $status === 'Pending' ? 'text-orange-500' : 'text-green-500'; ?>"><?php echo htmlspecialchars($status); ?></span></p>

                    <!-- Add Comment -->
                    <form method="POST" action="task_detail.php?task_id=<?php echo $task_id; ?>" enctype="multipart/form-data" class="mt-6">
                        <div class="mb-4">
                            <label for="comment" class="block text-sm font-medium text-gray-700">Add Comment</label>
                            <textarea name="comment" id="comment" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="output_link" class="block text-sm font-medium text-gray-700">Output Link (Optional)</label>
                            <input type="url" name="output_link" id="output_link" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        </div>

                        <div class="mb-4">
                            <label for="file_upload" class="block text-sm font-medium text-gray-700">Upload File (Optional)</label>
                            <input type="file" name="file_upload" id="file_upload" class="mt-1 block w-full">
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button type="submit" name="submit_update" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit Update</button>
                            <?php if ($status === 'Pending'): ?>
                                <button type="submit" name="complete_task" class="bg-green-500 text-white px-4 py-2 rounded-md">Complete Task</button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Show Task Updates -->
                    <h3 class="text-lg font-semibold mt-6">Updates</h3>
                    <?php if (empty($responses)): ?>
                        <p class="text-gray-500">No updates available.</p>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200 mt-4">
                            <?php foreach ($responses as $response): ?>
                                <li class="py-4">
                                    <div class="flex items-start space-x-3">
                                        <?php if ($response['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($response['profile_image']); ?>" alt="<?php echo htmlspecialchars($response['username']); ?>" class="w-10 h-10 rounded-full">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-white text-sm"><?php echo strtoupper($response['username'][0]); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium"><?php echo htmlspecialchars($response['username']); ?></p>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($response['comment']); ?></p>
                                            <?php if ($response['file_path']): ?>
                                                <p><a href="<?php echo htmlspecialchars($response['file_path']); ?>" class="text-blue-500" target="_blank">View File</a></p>
                                            <?php endif; ?>
                                            <?php if ($response['output_link']): ?>
                                                <p><a href="<?php echo htmlspecialchars($response['output_link']); ?>" class="text-blue-500" target="_blank">View Link</a></p>
                                            <?php endif; ?>
                                            <p class="text-xs text-gray-400"><?php echo htmlspecialchars($response['created_at']); ?></p>
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
