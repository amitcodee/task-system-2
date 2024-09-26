<?php
// Enable error reporting to catch issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
include 'config.php';

// Define a list of authorized users based on email
$authorized_users = ['admin@example.com', 'manager@example.com'];

// Check if the logged-in user's email is in the authorized list
$is_authorized = in_array($_SESSION['user_email'], $authorized_users);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> <!-- Font Awesome Icons -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-expanded .sidebar-text {
            display: inline;
        }

        .sidebar-collapsed .sidebar-text {
            display: none;
        }

        .sidebar-collapsed .sidebar-icons-only {
            justify-content: center;
        }

        .sidebar-expanded {
            width: 16rem;
        }

        .sidebar-collapsed {
            width: 5rem;
        }

        /* Toggle button inside sidebar */
        .toggle-sidebar-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
          
            background-color: #374151;
            color: white;
            border-radius: 50%;
            cursor: pointer;
        }

        /* Main content adjusts based on sidebar size */
        .content-expanded {
            margin-left: 16rem;
            transition: margin-left 0.3s;
        }

        .content-collapsed {
            margin-left: 4rem;
            transition: margin-left 0.3s;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="bg-gray-800 text-gray-100 flex flex-col transition-all duration-300 sidebar-expanded">
            <!-- <div class="p-4 flex items-center justify-between">
                <img src="https://techcadd.com/assets/img/logo1.png" alt="Logo" class="h-8 d-block sidebar-text">
                <h1 class="text-lg font-semibold sidebar-text">Techcadd</h1>
            </div> -->
            <img src="https://techcadd.com/assets/img/logo1.png" alt="Logo" class=" d-block sidebar-text p-3">
            <!-- Sidebar Toggle Button (inside sidebar) -->
            <div class="flex  items-center justify-center">
            <div id="toggleSidebar" class="toggle-sidebar-btn">
                <i class="fas fa-bars"></i>
               
            </div>
            <div class="p-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold sidebar-text">Techcadd</h1>
            </div>
            </div>

            <nav class="flex-1 px-4 space-y-2">
                <a href="dashboard.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="ml-3 sidebar-text">Dashboard</span>
                </a>
                <a href="my_task.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                    <i class="fas fa-user"></i>
                    <span class="ml-3 sidebar-text">My Tasks</span>
                </a>
                <?php if ($is_authorized): ?>
                    <a href="project.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-folder"></i>
                        <span class="ml-3 sidebar-text">Projects</span>
                    </a>
                    <a href="member.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-users"></i>
                        <span class="ml-3 sidebar-text">Members</span>
                    </a>
                    <a href="add_task.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-plus"></i>
                        <span class="ml-3 sidebar-text">Add Tasks</span>
                    </a>
                    <a href="all_task.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-tasks"></i>
                        <span class="ml-3 sidebar-text">All Tasks</span>
                    </a>
                    <a href="task_answer.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-comments"></i>
                        <span class="ml-3 sidebar-text">View Answers</span>
                    </a>
                    <a href="user_login_date.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="ml-3 sidebar-text">Login History</span>
                    </a>
                <?php endif; ?>
            </nav>
        </aside>

       
    </div>

    <!-- JavaScript to toggle the sidebar -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleSidebar = document.getElementById('toggleSidebar');

        toggleSidebar.addEventListener('click', function() {
            // Toggle the sidebar class
            sidebar.classList.toggle('sidebar-expanded');
            sidebar.classList.toggle('sidebar-collapsed');

            // Adjust main content margin
            mainContent.classList.toggle('content-expanded');
            mainContent.classList.toggle('content-collapsed');
        });
    </script>
</body>

</html>