<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Fetch the logged-in user's role to determine permissions
$user_role_query = $conn->prepare("SELECT role FROM users WHERE email = ?");
$user_role_query->bind_param("s", $_SESSION['user_email']);
$user_role_query->execute();
$user_role_query->bind_result($user_role);
$user_role_query->fetch();
$user_role_query->close();

// Allow task assignment only for Admin or Manager
if (!in_array($user_role, ['Admin', 'Manager'])) {
    echo "You do not have permission to assign tasks.";
    exit;
}

// Fetch projects from the database
$projects = [];
$project_query = $conn->prepare("SELECT id, name FROM projects");
$project_query->execute();
$project_query->bind_result($project_id, $project_name);
while ($project_query->fetch()) {
    $projects[] = [
        'id' => $project_id,
        'name' => $project_name
    ];
}
$project_query->close();

// Fetch all users for assigning tasks
$users = [];
$user_query = $conn->prepare("SELECT id, name, profile_image FROM users");
$user_query->execute();
$user_query->bind_result($user_id, $user_name, $user_profile_image);
while ($user_query->fetch()) {
    $users[] = [
        'id' => $user_id,
        'name' => $user_name,
        'profile_image' => $user_profile_image
    ];
}
$user_query->close();

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $project_list = $_POST['project_list'];
    $due_date = $_POST['due_date'];
    $task_priority = $_POST['task_priority'];
    $task_category = $_POST['task_category'] == 'custom' ? $_POST['custom_task_category'] : $_POST['task_category'];
    $reminder_time = $_POST['reminder_time'];
    $location = $_POST['location'];
    $task_link = $_POST['task_link'];
    $assignees = $_POST['assignees'];
    $file = $_FILES['task_file'];

    // File upload handling
    $file_path = "";
    if (!empty($file['name'])) {
        $file_name = basename($file['name']);
        $file_path = "uploads/" . $file_name;
        move_uploaded_file($file['tmp_name'], $file_path);
    }

    // Insert task into the tasks table
    $task_insert_query = $conn->prepare("INSERT INTO tasks (name, description, project_list, due_date, task_priority, task_category, reminder_time, location, task_link, status, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
    $task_insert_query->bind_param("ssssssssss", $task_name, $description, $project_list, $due_date, $task_priority, $task_category, $reminder_time, $location, $task_link, $file_path);
    $task_insert_query->execute();
    $task_id = $conn->insert_id; // Get the inserted task ID
    $task_insert_query->close();

    // Insert the assignees into the task_assignees table
    foreach ($assignees as $assignee_id) {
        $assign_task_query = $conn->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
        $assign_task_query->bind_param("ii", $task_id, $assignee_id);
        $assign_task_query->execute();
        $assign_task_query->close();
    }

    // Redirect to tasks list or success page
    header("Location: my_task.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Styles for dropdown and avatar */
        .custom-dropdown {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .dropdown-btn {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 10px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 6px;
            cursor: pointer;
            background-color: white;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            width: 100%;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 6px;
            max-height: 200px;
            overflow-y: auto;
        }

        .dropdown-content label {
            padding: 8px 16px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .dropdown-content label:hover {
            background-color: #f0f0f0;
        }

        .dropdown-content label.active {
            background-color: #e2f2ff;
        }

        .dropdown-content label.active::before {
            content: "✔";
            padding-right: 10px;
            color: #2563eb;
        }

        .show {
            display: block;
        }

        .selected-user {
            display: flex;
            align-items: center;
            margin-right: 8px;
            margin-left: 3px;
            color: white;
            background-color: #2563eb;
            padding: 4px 8px;
            border-radius: 20px;
        }

        .avatar {
            width: 30px;
            height: 30px;
            background-color: #2563eb;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            color: white;
            margin-right: 10px;
        }

        .profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .dropdown-btn span {
            margin-left: 10px;
        }

        .check {
            margin-right: 10px;
            margin-left: 10px;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <!-- Include Sidenav -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">
        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <div class="container mx-auto p-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <form method="POST" action="add_task.php" enctype="multipart/form-data">
                    <!-- Task Name -->
                    <div class="mb-4">
                        <label for="task_name" class="block text-sm font-medium text-gray-700">Task Name</label>
                        <input type="text" name="task_name" id="task_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></textarea>
                    </div>

                    <!-- Project List -->
                    <div class="mb-4">
                        <label for="project_list" class="block text-sm font-medium text-gray-700">Project List</label>
                        <select name="project_list" id="project_list" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo htmlspecialchars($project['id']); ?>">
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Due Date -->
                    <div class="mb-4">
                        <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" name="due_date" id="due_date" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>

                    <!-- Task Priority -->
                    <div class="mb-4">
                        <label for="task_priority" class="block text-sm font-medium text-gray-700">Task Priority</label>
                        <select name="task_priority" id="task_priority" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <!-- Task Category -->
                    <div class="mb-4">
                        <label for="task_category" class="block text-sm font-medium text-gray-700">Task Category</label>
                        <select name="task_category" id="task_category" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" onchange="toggleCustomCategory(this)">
                            <option value="Development">Development</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Design">Design</option>
                            <option value="custom">Custom</option>
                        </select>
                        <div id="custom_category_div" class="mt-2 hidden">
                            <input type="text" name="custom_task_category" id="custom_task_category" placeholder="Enter custom category" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Task Reminder -->
                    <div class="mb-4">
                        <label for="reminder_time" class="block text-sm font-medium text-gray-700">Reminder Time</label>
                        <input type="time" name="reminder_time" id="reminder_time" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>

                    <!-- Task Location -->
                    <div class="mb-4">
                        <label for="location" class="block text-sm font-medium text-gray-700">Task Location</label>
                        <input type="text" name="location" id="location" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>

                    <!-- Task Link -->
                    <div class="mb-4">
                        <label for="task_link" class="block text-sm font-medium text-gray-700">Task Related Link</label>
                        <input type="url" name="task_link" id="task_link" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="https://example.com">
                    </div>

                    <!-- File Upload -->
                    <div class="mb-4">
                        <label for="task_file" class="block text-sm font-medium text-gray-700">Attach File</label>
                        <input type="file" name="task_file" id="task_file" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>

                    <!-- Assignees Selection -->
                    <div class="mb-4">
                        <label for="assignees" class="block text-sm font-medium text-gray-700">Assign Users</label>
                        <div class="custom-dropdown">
                            <div id="dropdown-btn" class="dropdown-btn">
                                <span>Select Users</span>
                            </div>
                            <div id="dropdown-content" class="dropdown-content">
                                <?php foreach ($users as $user): ?>
                                    <label>
                                        <!-- Check if profile pic is available, otherwise show initials -->
                                        <?php if ($user['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="profile-pic">
                                        <?php else: ?>
                                            <div class="avatar">
                                                <?php
                                                    $nameParts = explode(' ', $user['name']);
                                                    $initials = strtoupper($nameParts[0][0]) . (isset($nameParts[1]) ? strtoupper($nameParts[1][0]) : '');
                                                    echo $initials;
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        <input type="checkbox" name="assignees[]" value="<?php echo htmlspecialchars($user['id']); ?>" class="check" />
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Add Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle custom category field visibility
    function toggleCustomCategory(select) {
        const customCategoryDiv = document.getElementById('custom_category_div');
        if (select.value === 'custom') {
            customCategoryDiv.classList.remove('hidden');
        } else {
            customCategoryDiv.classList.add('hidden');
        }
    }

    // Toggle dropdown visibility for user assignment
    const dropdownBtn = document.getElementById('dropdown-btn');
    const dropdownContent = document.getElementById('dropdown-content');
    const selectedUsersDiv = dropdownBtn;

    dropdownBtn.addEventListener('click', () => {
        dropdownContent.classList.toggle('show');
    });

    // Update selected users display
    const checkboxes = dropdownContent.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.parentElement;
            const avatar = label.querySelector('.avatar') || label.querySelector('.profile-pic');
            const userName = label.textContent.trim();

            if (this.checked) {
                label.classList.add('active');
                addSelectedUser(userName, avatar);
            } else {
                label.classList.remove('active');
                removeSelectedUser(userName);
            }
        });
    });

    function addSelectedUser(name, avatarElement) {
        const userDiv = document.createElement('div');
        userDiv.classList.add('selected-user');

        const avatarClone = avatarElement.cloneNode(true);
        userDiv.appendChild(avatarClone);

        const nameSpan = document.createElement('span');
        nameSpan.textContent = name;
        userDiv.appendChild(nameSpan);

        selectedUsersDiv.appendChild(userDiv);
    }

    function removeSelectedUser(name) {
        const selectedUserDivs = selectedUsersDiv.querySelectorAll('.selected-user');
        selectedUserDivs.forEach(div => {
            if (div.textContent.includes(name)) {
                selectedUsersDiv.removeChild(div);
            }
        });
    }
</script>

</body>
</html>
