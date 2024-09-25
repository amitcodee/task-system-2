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

// Fetch project details
$project_query = $conn->prepare("SELECT name FROM projects WHERE id = ?");
$project_query->bind_param("i", $project_id);
$project_query->execute();
$project_query->bind_result($project_name);
$project_query->fetch();
$project_query->close();

// Fetch tasks related to the project, including assignees and task details
$task_query = $conn->prepare("
    SELECT 
        t.id, t.name, t.description, t.due_date, t.task_priority, t.task_category, t.reminder_time, t.location, t.task_link, t.file_path, t.created_at,
        GROUP_CONCAT(u.name SEPARATOR ', ') AS assigned_users
    FROM tasks t
    LEFT JOIN task_assignees ta ON t.id = ta.task_id
    LEFT JOIN users u ON ta.user_id = u.id
    WHERE t.project_list = ?
    GROUP BY t.id
");
$task_query->bind_param("i", $project_id);
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
                                        <button onclick="openEditTaskModal(
                                            '<?php echo $task['id']; ?>', 
                                            '<?php echo htmlspecialchars($task['name'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['description'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['due_date'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['task_priority'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['category'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['reminder_time'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['location'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['task_link'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars($task['file_path'] ?? ''); ?>')"

                                            class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none transition duration-300">
                                            Edit Task
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

        <!-- Modal for editing task -->
        <div id="editTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-5xl p-6">
                <h2 class="text-xl font-semibold mb-4">Edit Task</h2>
                <form id="editTaskForm" action="update_task.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="task_id" id="task_id">

                    <div class="flex flex-wrap -mx-4">
                        <!-- Task Name -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="task_name" class="block text-sm font-medium text-gray-700">Task Name</label>
                            <input type="text" name="task_name" id="modal_task_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                        </div>

                        <!-- Task Description -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="modal_description" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required></textarea>
                        </div>

                        <!-- Project List -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="project_list" class="block text-sm font-medium text-gray-700">Project List</label>
                            <select name="project_list" id="modal_project_list" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                <!-- Dynamically load project options -->
                                <?php
                                // Fetch all projects for the dropdown
                                $project_list_query = $conn->query("SELECT id, name FROM projects");
                                while ($row = $project_list_query->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Due Date -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" name="due_date" id="modal_due_date" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                        </div>

                        <!-- Task Priority -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="task_priority" class="block text-sm font-medium text-gray-700">Task Priority</label>
                            <select name="task_priority" id="modal_task_priority" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>

                        <!-- Task Category -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="category" class="block text-sm font-medium text-gray-700">Task Category</label>
                            <input type="text" name="category" id="modal_category" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                        </div>

                        <!-- Reminder Time -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="reminder_time" class="block text-sm font-medium text-gray-700">Reminder Time</label>
                            <input type="time" name="reminder_time" id="modal_reminder_time" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        </div>

                        <!-- Task Location -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="location" class="block text-sm font-medium text-gray-700">Task Location</label>
                            <input type="text" name="location" id="modal_location" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        </div>

                        <!-- Task Related Link -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="task_link" class="block text-sm font-medium text-gray-700">Task Related Link</label>
                            <input type="url" name="task_link" id="modal_task_link" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        </div>

                        <!-- Attach File -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
                            <label for="file_path" class="block text-sm font-medium text-gray-700">Attach File</label>
                            <input type="file" name="file_path" id="modal_file_path" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        </div>

                        <!-- Assign Users -->
                        <!-- Assign Users -->
                        <div class="w-full md:w-1/2 px-4 mb-4">
    <label for="assigned_user" class="block text-sm font-medium text-gray-700">Assign Users</label>

    <?php
    // Fetch all users assigned to the task
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
        $assigned_user_ids[] = $assigned_user_id;  // Collect all assigned user IDs
        $assigned_user_names[] = ['id' => $assigned_user_id, 'name' => $assigned_user_name]; // Collect names and ids of assigned users
    }
    $assigned_users_query->close();
    ?>

    <p>
        <small>Hold down the Ctrl (windows) / Command (Mac) button to select multiple options.</small>
    </p>

    <!-- Multi-select for assigning users -->
    <select name="assigned_user[]" id="modal_assigned_user" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" multiple onchange="displayNewSelectedUsers()">
        <?php
        // Fetch all users for the dropdown
        $user_list_query = $conn->query("SELECT id, name FROM users");
        while ($row = $user_list_query->fetch_assoc()) {
            // Check if the user is already assigned
            $selected = in_array($row['id'], $assigned_user_ids) ? 'selected' : '';

            // Output the option element, pre-selecting assigned users
            echo '<option value="' . $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
        }
        ?>
    </select>

    <!-- Display currently assigned users (old ones) with a close button to manually remove -->
    <p class="mt-4"><strong>Users Assigned to the Task:</strong></p>
    <div id="selected_users_list" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-100">
        <ul class="list-disc pl-5" id="user_list">
            <?php
            // Display old users with a close button for removal
            foreach ($assigned_user_names as $user) {
                echo '<li id="old-user-' . $user['id'] . '">' . htmlspecialchars($user['name']) . ' <button type="button" onclick="removeOldUser(\'' . $user['id'] . '\')">✖</button></li>';
            }
            
            ?>
        </ul>
    </div>

    <p class="mt-4">
        <strong>New Users Added to the Task:</strong>
    </p>

    <!-- Display the new users selected dynamically -->
    <div id="new_selected_users_list" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-100">
        <ul class="list-disc pl-5" id="new_user_list"></ul>
    </div>
    <p>All Users Assigned to the Task:</p>

<div id="all_users_list" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-100">
    <ul class="list-disc pl-5">
        <?php
        // Fetch all users assigned to the task from task_assignees table
        $assigned_users_query = $conn->prepare("
            SELECT u.id, u.name 
            FROM task_assignees ta
            JOIN users u ON ta.user_id = u.id
            WHERE ta.task_id = ?
        ");
        $assigned_users_query->bind_param("i", $task_id);
        $assigned_users_query->execute();

        // Fetch result set
        $result = $assigned_users_query->get_result();

        // Display all assigned users
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<li>' . htmlspecialchars($row['name']) . ' (User ID: ' . htmlspecialchars($row['id']) . ')</li>';
            }
        } else {
            echo '<li>No users assigned to this task.</li>';
        }

        $assigned_users_query->close();
        ?>
    </ul>
</div>

    
</div>

<script>
// Function to display new selected users without affecting the old ones
function displayNewSelectedUsers() {
    var select = document.getElementById('modal_assigned_user');
    var selectedUsers = [];
    var oldUserIds = <?php echo json_encode(array_column($assigned_user_names, 'id')); ?>; // Get old user IDs from PHP

    // Loop through selected options to get new users only (exclude old users)
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].selected && !oldUserIds.includes(select.options[i].value)) {
            selectedUsers.push({id: select.options[i].value, name: select.options[i].text});
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
    newUserElements.forEach(function (element) {
        element.remove();
    });

    // Loop through all new users and display with a remove button
    users.forEach(function(user) {
        var li = document.createElement('li');
        li.setAttribute('id', 'new-user-' + user.id);
        li.innerHTML = user.name + ' <button type="button" onclick="removeNewUser(\'' + user.id + '\')">✖</button>';
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
    var select = document.getElementById('modal_assigned_user');
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value == userId) {
            select.options[i].selected = false; // Unselect the user
        }
    }
}

// Function to remove an old user
function removeOldUser(userId) {
    // Remove the old user from the list
    var userElement = document.getElementById('old-user-' + userId);
    if (userElement) {
        userElement.remove();
    }

    // Unselect the user in the dropdown
    var select = document.getElementById('modal_assigned_user');
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value == userId) {
            select.options[i].selected = false; // Unselect the user
        }
    }
}
</script>


<script>
// Function to display new selected users without affecting the old ones
function displayNewSelectedUsers() {
    var select = document.getElementById('modal_assigned_user');
    var selectedUsers = [];
    var oldUserIds = <?php echo json_encode(array_column($assigned_user_names, 'id')); ?>; // Get old user IDs from PHP

    // Loop through selected options to get new users only (exclude old users)
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].selected && !oldUserIds.includes(select.options[i].value)) {
            selectedUsers.push({id: select.options[i].value, name: select.options[i].text});
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
    newUserElements.forEach(function (element) {
        element.remove();
    });

    // Loop through all new users and display with a remove button
    users.forEach(function(user) {
        var li = document.createElement('li');
        li.setAttribute('id', 'new-user-' + user.id);
        li.innerHTML = user.name + ' <button type="button" onclick="removeNewUser(\'' + user.id + '\')">✖</button>';
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
    var select = document.getElementById('modal_assigned_user');
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value == userId) {
            select.options[i].selected = false; // Unselect the user
        }
    }
}

// Function to remove an old user
function removeOldUser(userId) {
    // Remove the old user from the list
    var userElement = document.getElementById('old-user-' + userId);
    if (userElement) {
        userElement.remove();
    }

    // Unselect the user in the dropdown
    var select = document.getElementById('modal_assigned_user');
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value == userId) {
            select.options[i].selected = false; // Unselect the user
        }
    }

    // Optionally: Perform additional actions to mark this user as removed (e.g., update in the database)
}
</script>


                    </div>

                    <div class="flex justify-end space-x-2 mt-4">
                        <button type="button" onclick="closeEditTaskModal()" class="bg-gray-400 text-white px-4 py-2 rounded-md">Cancel</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Update Task</button>
                    </div>
                </form>

            </div>
        </div>

        <script>
            function openEditTaskModal(taskId, taskName, taskDescription, dueDate, taskPriority, category, reminderTime, location, taskLink, filePath) {
                // Fill modal inputs with task data
                document.getElementById('task_id').value = taskId;
                document.getElementById('modal_task_name').value = taskName;
                document.getElementById('modal_description').value = taskDescription;
                document.getElementById('modal_due_date').value = dueDate;
                document.getElementById('modal_task_priority').value = taskPriority;
                document.getElementById('modal_category').value = category;
                document.getElementById('modal_reminder_time').value = reminderTime;
                document.getElementById('modal_location').value = location;
                document.getElementById('modal_task_link').value = taskLink;
                document.getElementById('modal_file_path').value = filePath;

                // Show the modal
                document.getElementById('editTaskModal').classList.remove('hidden');
            }

            function closeEditTaskModal() {
                // Hide the modal
                document.getElementById('editTaskModal').classList.add('hidden');
            }
        </script>
    </div>

</body>

</html>