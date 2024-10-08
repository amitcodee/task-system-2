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

// Fetch the task details from the tasks table
$task_query = $conn->prepare("
    SELECT tasks.name, tasks.description, tasks.due_date, tasks.status, tasks.task_priority, tasks.task_category, tasks.reminder_time, tasks.location, tasks.task_link, tasks.file_path, projects.name as project_name
    FROM tasks
    INNER JOIN projects ON tasks.project_list = projects.id
    WHERE tasks.id = ?
");
$task_query->bind_param("i", $task_id);
$task_query->execute();
$task_query->bind_result($task_name, $task_description, $due_date, $status, $task_priority, $task_category, $reminder_time, $location, $task_link, $task_file_path, $project_name);
$task_query->fetch();
$task_query->close();

// Initialize variables to avoid null issues
$task_name = $task_name ?? '';
$task_description = $task_description ?? '';
$due_date = $due_date ?? '';
$status = $status ?? '';
$project_name = $project_name ?? '';
$task_priority = $task_priority ?? '';
$task_category = $task_category ?? '';
$reminder_time = $reminder_time ?? '';
$location = $location ?? '';
$task_link = $task_link ?? '';
$task_file_path = $task_file_path ?? '';

// Handle comment/file submission or task status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_update'])) {
        $comment = $_POST['comment'] ?? '';
        $output_link = $_POST['output_link'] ?? '';
        $new_status = $_POST['task_status'] ?? $status; // Get the updated task status from dropdown
        $response_file_path = ''; // Initialize an empty variable for response file path

        // Handle file upload for task response (Separate from task file in tasks table)
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
            $upload_dir = 'uploads/';
            $file_name = basename($_FILES['file_upload']['name']);
            $response_file_path = $upload_dir . $file_name;
            if (!move_uploaded_file($_FILES['file_upload']['tmp_name'], $response_file_path)) {
                $response_file_path = ''; // Reset if file upload failed
            }
        }

        // Insert task response into the task_responses table
        $stmt = $conn->prepare("INSERT INTO task_responses (task_id, user_id, comment, file_path, output_link) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $task_id, $assigned_user_id, $comment, $response_file_path, $output_link);
        $stmt->execute();
        $stmt->close();

        // Update task status (if necessary)
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $task_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch task responses from the task_responses table
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
<body class="bg-gray-50">
<div class="flex h-screen">

    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">

        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <main class="flex-1 p-6 bg-gray-50">
            <div class="container mx-auto">
                <div class="bg-white p-8 rounded-lg shadow-lg">
                    <!-- Task Title and Project -->
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-3xl font-semibold text-gray-700"><?php echo htmlspecialchars($task_name); ?></h2>
                    </div>
                    <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($task_description)); ?></p>

                    <!-- Task Meta Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-500"><strong>Due Date:</strong> <?php echo htmlspecialchars($due_date); ?></p>
                            <p class="text-sm text-gray-500"><strong>Project:</strong> <?php echo htmlspecialchars($project_name); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500"><strong>Priority:</strong> <?php echo htmlspecialchars($task_priority); ?></p>
                            <p class="text-sm text-gray-500"><strong>Category:</strong> <?php echo htmlspecialchars($task_category); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500"><strong>Reminder Time:</strong> <?php echo htmlspecialchars($reminder_time); ?></p>
                            <p class="text-sm text-gray-500"><strong>Location:</strong> <?php echo htmlspecialchars($location); ?></p>
                        </div>
                        <div>
                            <?php if (!empty($task_link)): ?>
                                <p class="text-sm text-gray-500"><strong>Task Link:</strong> <a href="<?php echo htmlspecialchars($task_link); ?>" class="text-blue-500 hover:underline" target="_blank">View Link</a></p>
                            <?php endif; ?>
                            <?php if (!empty($task_file_path)): ?>
                                <p class="text-sm text-gray-500"><strong>Attached File:</strong> <a href="uploads/<?php echo htmlspecialchars($task_file_path); ?>" class="text-blue-500 hover:underline" target="_blank">View File</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add Comment and File Upload -->
                    <form method="POST" action="task_detail.php?task_id=<?php echo $task_id; ?>" enctype="multipart/form-data" class="mt-6">
                        <div class="mb-4">
                            <label for="comment" class="block text-sm font-medium text-gray-700">Add Comment</label>
                            <textarea name="comment" id="comment" rows="3" class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="output_link" class="block text-sm font-medium text-gray-700">Output Link (Optional)</label>
                            <input type="url" name="output_link" id="output_link" class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-4">
                            <label for="file_upload" class="block text-sm font-medium text-gray-700">Upload File (Optional)</label>
                            <input type="file" name="file_upload" id="file_upload" class="mt-1 block w-full p-3">
                        </div>

                        <div class="mb-4">
                            <label for="task_status" class="block text-sm font-medium text-gray-700">Task Status</label>
                            <select name="task_status" id="task_status" class="block w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $status == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Complete" <?php echo $status == 'Complete' ? 'selected' : ''; ?>>Complete</option>
                            </select>
                        </div>

                        <?php
// Assuming you have already fetched the task's due date in the `$due_date` variable

// Get the current date
$current_date = date('Y-m-d');

// Check if the task is overdue (i.e., if the current date is past the due date)
$is_overdue = $due_date < $current_date;
?>

<!-- Submit Button -->
<div class="flex justify-end space-x-3">
    <button type="submit" name="submit_update" id="submitUpdateBtn" 
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition">
        Submit Update
    </button>
</div>

<!-- Pass the overdue status to JavaScript -->
<script>
    const isOverdue = <?php echo json_encode($is_overdue); ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        const submitButton = document.getElementById('submitUpdateBtn');
        
        if (isOverdue) {
            submitButton.disabled = true;
            submitButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
            submitButton.classList.add('bg-gray-400', 'cursor-not-allowed');
        }
    });
</script>

                    </form>

                    <!-- Task Updates Section -->
                    <h3 class="text-lg font-semibold mt-6">Updates</h3>
                    <?php if (empty($responses)): ?>
                        <p class="text-gray-500">No updates available.</p>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200 mt-4">
                            <?php foreach ($responses as $response): ?>
                                <li class="py-4 flex items-start space-x-4">
                                    <?php if ($response['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($response['profile_image']); ?>" alt="<?php echo htmlspecialchars($response['username']); ?>" class="w-10 h-10 rounded-full">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-white text-sm"><?php echo strtoupper($response['username'][0]); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($response['username']); ?></p>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($response['comment']); ?></p>
                                        
                                        <!-- Display response file (if uploaded) -->
                                        <?php if (!empty($response['file_path'])): ?>
                                            <p><a href="<?php echo htmlspecialchars($response['file_path']); ?>" class="text-blue-500 hover:underline" target="_blank">View File</a></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($response['output_link'])): ?>
                                            <p><a href="<?php echo htmlspecialchars($response['output_link']); ?>" class="text-blue-500 hover:underline" target="_blank">View Link</a></p>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($response['created_at']); ?></p>
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
