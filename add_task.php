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
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            width: 100%;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 6px;
            max-height: 150px;
            overflow-y: auto;
        }

        .dropdown-content label {
            padding: 8px 16px;
            display: flex;
            align-items: center;
            cursor: pointer;
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
                <form method="POST" action="add_task.php">
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
    // Toggle dropdown visibility
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

        // Clone the avatar or profile picture
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
