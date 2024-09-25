<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Check if task_id is provided in the URL
if (!isset($_GET['task_id'])) {
    echo "Task ID is missing.";
    exit;
}

$task_id = $_GET['task_id'];

// Fetch task details
$task_query = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$task_query->bind_param("i", $task_id);
$task_query->execute();
$task_result = $task_query->get_result();

if ($task_result->num_rows == 0) {
    echo "Task not found.";
    exit;
}

$task = $task_result->fetch_assoc();
$task_query->close();

// Fetch users assigned to the task
$assigned_users_query = $conn->prepare("
    SELECT u.id, u.name 
    FROM task_assignees ta
    JOIN users u ON ta.user_id = u.id
    WHERE ta.task_id = ?
");
$assigned_users_query->bind_param("i", $task_id);
$assigned_users_query->execute();
$assigned_users_query->bind_result($assigned_user_id, $assigned_user_name);

$assigned_user_ids = [];
$assigned_user_names = [];
while ($assigned_users_query->fetch()) {
    $assigned_user_ids[] = $assigned_user_id;
    $assigned_user_names[] = ['id' => $assigned_user_id, 'name' => $assigned_user_name];
}
$assigned_users_query->close();

// Fetch all users for the dropdown
$all_users = [];
$user_list_query = $conn->query("SELECT id, name FROM users");
while ($row = $user_list_query->fetch_assoc()) {
    $all_users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
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
                        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Edit Task</h1>

                        <form id="editTaskForm" action="update_task.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">

                            <div class="flex flex-wrap -mx-4">
                                <!-- Task Name -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="task_name" class="block text-sm font-medium text-gray-700">Task Name</label>
                                    <input type="text" name="task_name" id="task_name" value="<?php echo htmlspecialchars($task['name']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                                </div>

                                <!-- Task Description -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea name="description" id="description" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required><?php echo htmlspecialchars($task['description']); ?></textarea>
                                </div>

                                <!-- Project List -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="project_list" class="block text-sm font-medium text-gray-700">Project List</label>
                                    <select name="project_list" id="project_list" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                        <!-- Dynamically load project options -->
                                        <?php
                                        $project_list_query = $conn->query("SELECT id, name FROM projects");
                                        while ($row = $project_list_query->fetch_assoc()) {
                                            $selected = ($row['id'] == $task['project_list']) ? 'selected' : '';
                                            echo '<option value="' . $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Due Date -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                                    <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($task['due_date']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                                </div>

                                <!-- Task Priority -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="task_priority" class="block text-sm font-medium text-gray-700">Task Priority</label>
                                    <select name="task_priority" id="task_priority" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                        <option value="High" <?php echo ($task['task_priority'] == 'High') ? 'selected' : ''; ?>>High</option>
                                        <option value="Medium" <?php echo ($task['task_priority'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                        <option value="Low" <?php echo ($task['task_priority'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                    </select>
                                </div>

                                <!-- Task Category -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="category" class="block text-sm font-medium text-gray-700">Task Category</label>
                                    <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($task['task_category']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                                </div>

                                <!-- Reminder Time -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="reminder_time" class="block text-sm font-medium text-gray-700">Reminder Time</label>
                                    <input type="time" name="reminder_time" id="reminder_time" value="<?php echo htmlspecialchars($task['reminder_time']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                </div>

                                <!-- Task Location -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="location" class="block text-sm font-medium text-gray-700">Task Location</label>
                                    <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($task['location']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                </div>

                                <!-- Task Related Link -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="task_link" class="block text-sm font-medium text-gray-700">Task Related Link</label>
                                    <input type="url" name="task_link" id="task_link" value="<?php echo htmlspecialchars($task['task_link']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                </div>

                                <!-- Attach File -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="file_path" class="block text-sm font-medium text-gray-700">Attach File</label>
                                    <input type="file" name="file_path" id="file_path" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                    <?php if (!empty($task['file_path'])): ?>
                                        <p class="mt-2 text-sm text-gray-600">
                                            Current File: <a href="<?php echo htmlspecialchars($task['file_path']); ?>" target="_blank" class="text-blue-500 underline">View File</a>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Assign Users -->
                                <div class="w-full md:w-1/2 px-4 mb-4">
                                    <label for="assigned_user" class="block text-sm font-medium text-gray-700">Assign Users</label>
                                    <p>
                                        <small>Hold down the Ctrl (windows) / Command (Mac) button to select multiple options.</small>
                                    </p>
                                    <!-- Multi-select for assigning users -->
                                    <select name="assigned_user[]" id="assigned_user" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" multiple onchange="displayNewSelectedUsers()">
                                        <?php
                                        foreach ($all_users as $user) {
                                            $selected = in_array($user['id'], $assigned_user_ids) ? 'selected' : '';
                                            echo '<option value="' . $user['id'] . '" ' . $selected . '>' . htmlspecialchars($user['name']) . '</option>';
                                        }
                                        ?>
                                    </select>

                                    <!-- Display currently assigned users (old ones) with a close button to manually remove -->
                                    <p class="mt-4"><strong>Users Assigned to the Task:</strong></p>
                                    <div id="selected_users_list" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-100">
                                        <ul class="list-disc pl-5" id="user_list">
                                            <?php
                                            foreach ($assigned_user_names as $user) {
                                                echo '<li id="old-user-' . $user['id'] . '">' . htmlspecialchars($user['name']) . ' <button type="button" onclick="removeOldUser(\'' . $user['id'] . '\')" class="text-red-500">✖</button></li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>

                                    <!-- Hidden input to store removed old user IDs -->
                                    <input type="hidden" id="removed_old_users" name="removed_old_users[]" />

                                    <p class="mt-4">
                                        <strong>New Users Added to the Task:</strong>
                                    </p>

                                    <!-- Display the new users selected dynamically -->
                                    <div id="new_selected_users_list" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-100">
                                        <ul class="list-disc pl-5" id="new_user_list"></ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit and Cancel Buttons -->
                            <div class="flex justify-end space-x-2 mt-4">
                                <a href="project_task.php?project_id=<?php echo $project_id; ?>" class="bg-gray-400 text-white px-4 py-2 rounded-md">Cancel</a>
                                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Update Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Function to display new selected users without affecting the old ones
    function displayNewSelectedUsers() {
        var select = document.getElementById('assigned_user');
        var selectedUsers = [];
        var oldUserIds = <?php echo json_encode(array_column($assigned_user_names, 'id')); ?>; // Get old user IDs from PHP

        // Loop through selected options to get new users only (exclude old users)
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].selected && !oldUserIds.includes(parseInt(select.options[i].value))) {
                selectedUsers.push({ id: select.options[i].value, name: select.options[i].text });
            }
        }

        // Display the selected new users
        updateNewUserList(selectedUsers);
    }

    // Function to update the new user list
    function updateNewUserList(users) {
        var newUserList = document.getElementById('new_user_list');

        // Remove all new users (do not touch the old ones)
        var newUserElements = document.querySelectorAll("[id^='new-user-']");
        newUserElements.forEach(function(element) {
            element.remove();
        });

        // Loop through all new users and display with a remove button
        users.forEach(function(user) {
            var li = document.createElement('li');
            li.setAttribute('id', 'new-user-' + user.id);
            li.innerHTML = user.name + ' <button type="button" onclick="removeNewUser(\'' + user.id + '\')" class="text-red-500">✖</button>';
            newUserList.appendChild(li);
        });
    }

    // Function to remove a new user from the list and unselect from the dropdown
    function removeNewUser(userId) {
        // Remove the user from the displayed list
        var userElement = document.getElementById('new-user-' + userId);
        if (userElement) {
            userElement.remove();
        }

        // Unselect the user in the dropdown
        var select = document.getElementById('assigned_user');
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value == userId) {
                select.options[i].selected = false; // Unselect the user
                break;
            }
        }
    }

    // Function to remove an old user and track removal
    function removeOldUser(userId) {
        // Remove the old user from the list
        var userElement = document.getElementById('old-user-' + userId);
        if (userElement) {
            userElement.remove();
        }

        // Unselect the user in the dropdown
        var select = document.getElementById('assigned_user');
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value == userId) {
                select.options[i].selected = false; // Unselect the user
                break;
            }
        }

        // Add the removed user ID to the hidden input
        var removedOldUsersInput = document.getElementById('removed_old_users');
        var currentRemovedUsers = removedOldUsersInput.value ? JSON.parse(removedOldUsersInput.value) : [];
        currentRemovedUsers.push(userId);
        removedOldUsersInput.value = JSON.stringify(currentRemovedUsers);
    }
    </script>

</body>

</html>
