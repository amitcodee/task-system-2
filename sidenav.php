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

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo "Session not found. Redirecting to login page.";
    header('Location: login.php');
    exit;
}

// Define a list of authorized users based on email
$authorized_users = ['admin@example.com', 'manager@example.com'];  // Add more emails if needed

// Check if the logged-in user's email is in the authorized list
$is_authorized = in_array($_SESSION['user_email'], $authorized_users);

// Fetch favorite projects from the database
try {
    $query = $conn->prepare("SELECT id, name FROM projects WHERE user_email = ? AND is_favorite = 1");
    
    if (!$query) {
        throw new Exception("Failed to prepare SQL query: " . $conn->error);
    }

    // Bind the email parameter
    $query->bind_param("s", $_SESSION['user_email']);

    // Execute the query
    $query->execute();

    // Bind the result fields
    $query->bind_result($project_id, $project_name);

    // Fetch the favorite projects
    $fav_projects = [];
    while ($query->fetch()) {
        $fav_projects[] = [
            'id' => $project_id,
            'name' => $project_name
        ];
    }

    // Close the statement
    $query->close();
} catch (Exception $e) {
    // If there's an error, display it
    echo "Error fetching favorite projects: " . $e->getMessage();
    exit;
}
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
        .custom-table th, .custom-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .custom-table th {
            background-color: #f3f4f6;
        }
        .custom-table tbody tr:hover {
            background-color: #f9fafb;
        }
        /* Custom Sidebar Styles */
        .sidebar-expanded .sidebar-text {
            display: inline;
        }
        .sidebar-collapsed .sidebar-text {
            display: none;
        }
        .sidebar-collapsed .sidebar-icons-only {
            justify-content: center;
        }
        /* Hide collapse button on large screens */
        @media (min-width: 1024px) {
            .toggle-sidebar-btn {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-full">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-gray-800 text-gray-100 flex flex-col transition-all duration-300 sidebar-expanded lg:w-64">
            <div class="p-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold sidebar-text">Techcadd</h1>
                <button id="toggleSidebar" class="text-gray-100 focus:outline-none lg:hidden toggle-sidebar-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav class="flex-1 px-4 space-y-2">
                <a href="dashboard.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="ml-3 sidebar-text">Dashboard</span>
                </a>
                <?php if ($is_authorized): ?>
                <a href="add_task.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                    <i class="fas fa-tasks"></i>
                    <span class="ml-3 sidebar-text">Tasks</span>
                </a>
                <?php endif; ?>
                <a href="my_task.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                    <i class="fas fa-user"></i>
                    <span class="ml-3 sidebar-text">My Tasks</span>
                </a>
               

                <!-- Only show this section if the user is authorized (e.g., admin or manager) -->
                <?php if ($is_authorized): ?>
                <a href="project.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                    <i class="fas fa-folder"></i>
                    <span class="ml-3 sidebar-text">Projects</span>
                </a>
                <a href="member.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                    <i class="fas fa-users"></i>
                    <span class="ml-3 sidebar-text">Members</span>
                </a>
                    <a href="task_answer.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-comments"></i>
                        <span class="ml-3 sidebar-text">View Answers</span>
                    </a>
                    <a href="user_login_date.php" class="flex items-center py-2 px-3 rounded hover:bg-gray-700">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="ml-3 sidebar-text">Login Details</span>
                    </a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Main content here -->
        </div>
    </div>

    <!-- JavaScript to toggle the sidebar -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');

        toggleSidebar.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-expanded');
            sidebar.classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>
