<?php
session_start();
include 'config.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Retrieve date filter from URL or default to 'All'
$date_filter = $_GET['date_filter'] ?? 'All';

// Define date ranges for filtering
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_month_start = date('Y-m-01');

// Prepare date condition for SQL query
switch ($date_filter) {
    case 'Today':
        $date_condition = "AND p.created_at >= '$today 00:00:00'";
        break;
    case 'Yesterday':
        $date_condition = "AND p.created_at >= '$yesterday 00:00:00' AND p.created_at <= '$yesterday 23:59:59'";
        break;
    case 'This Week':
        $date_condition = "AND p.created_at >= '$this_week_start 00:00:00'";
        break;
    case 'This Month':
        $date_condition = "AND p.created_at >= '$this_month_start 00:00:00'";
        break;
    default:
        $date_condition = ""; // No date filter
}

// Retrieve projects and task count from the database
$query = $conn->prepare("
    SELECT p.id, p.name, p.color, p.is_favorite, p.is_archived, p.created_at, 
    (SELECT COUNT(*) FROM tasks t WHERE t.project_list = p.id) AS total_tasks,
    (SELECT COUNT(*) FROM tasks t WHERE t.project_list = p.id AND t.status = 'Complete') AS completed_tasks
    FROM projects p
    WHERE p.created_by = ? $date_condition
    ORDER BY p.is_archived ASC, p.created_at DESC
");
$query->bind_param("s", $_SESSION['user_email']);
$query->execute();
$query->bind_result($project_id, $project_name, $project_color, $is_favorite, $is_archived, $created_at, $total_tasks, $completed_tasks);
$projects = [];
while ($query->fetch()) {
    $projects[] = [
        'id' => $project_id,
        'name' => $project_name,
        'color' => $project_color,
        'is_favorite' => $is_favorite,
        'is_archived' => $is_archived,
        'created_at' => $created_at,
        'total_tasks' => $total_tasks,
        'completed_tasks' => $completed_tasks
    ];
}
$query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">
        
        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <!-- Projects List -->
        <main class="flex-1 p-6 bg-gray-100">
            <div class="container mx-auto p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold">Projects</h2>
                    <div class="flex space-x-2">
                        <!-- Active and Archived Toggle Buttons -->
                        <button id="activeButton" onclick="showActive()" class="bg-white border px-4 py-2 rounded-md shadow hover:bg-gray-50 focus:outline-none">Active</button>
                        <button id="archiveButton" onclick="showArchived()" class="bg-white border px-4 py-2 rounded-md shadow hover:bg-gray-50 focus:outline-none">Archived</button>
                        <!-- Create Project Button -->
                        <button onclick="openModal()" class="bg-blue-500 text-white px-4 py-2 rounded-md shadow hover:bg-blue-600">Create Project</button>
                    </div>
                </div>

                <!-- Navigation Tabs for Date Filters -->
                <nav class="flex space-x-4 mb-6">
                    <a href="?date_filter=All" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'All' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">All</a>
                    <a href="?date_filter=Today" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'Today' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">Today</a>
                    <a href="?date_filter=Yesterday" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'Yesterday' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">Yesterday</a>
                    <a href="?date_filter=This Week" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'This Week' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">This Week</a>
                    <a href="?date_filter=This Month" class="px-4 py-2 text-sm font-medium <?php echo $date_filter === 'This Month' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">This Month</a>
                </nav>

                <!-- Active Projects -->
                <div id="activeProjects" class="mt-6 bg-white rounded-lg shadow-md">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($projects as $project): ?>
                            <?php if (!$project['is_archived']): // Only show active projects ?>
                                <li class="flex flex-col px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <!-- Project Color Dot -->
                                            <span class="inline-block w-3 h-3 rounded-full" style="background-color: <?php echo htmlspecialchars($project['color']); ?>"></span>
                                            <!-- Project Name -->
                                            <a href="project_tasks.php?project_id=<?php echo $project['id']; ?>" class="text-blue-800 hover:underline text-xl font-bold">
                                                <?php echo htmlspecialchars($project['name']); ?>
                                            </a>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <!-- Favorite Star -->
                                            <form method="POST" action="toggle_favorite.php">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                <input type="hidden" name="is_favorite" value="<?php echo $project['is_favorite'] ? '0' : '1'; ?>">
                                                <button type="submit" class="focus:outline-none">
                                                    <?php if ($project['is_favorite']): ?>
                                                        <svg class="h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                                                        </svg>
                                                    <?php endif; ?>
                                                </button>
                                            </form>

                                            <!-- Archive Button -->
                                            <form method="POST" action="archive_project.php">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                <button type="submit" class="focus:outline-none">
                                                <i class="fa-solid fa-box-archive fa-lg" style="color: #5b5d62;"></i>
                                                </button>
                                            </form>

                                            <!-- Delete Button -->
                                            <form method="POST" action="delete_project.php">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                <button type="submit" class="focus:outline-none">
                                                    <svg class="h-6 w-6 text-gray-400 hover:text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M19 6h-2.5l-1-1h-5l-1 1H5v2h14V6zM7 9v10h10V9H7zM9 11h2v6H9v-6zM13 11h2v6h-2v-6z"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Task Completion Progress -->
                                    <div class="mt-2">
                                        <span class="text-sm text-gray-500">Tasks: <?php echo $project['completed_tasks']; ?> / <?php echo $project['total_tasks']; ?></span>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?php echo ($project['total_tasks'] > 0) ? ($project['completed_tasks'] / $project['total_tasks']) * 100 : 0; ?>%"></div>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Archived Projects (Initially Hidden) -->
                <div id="archivedProjects" class="mt-6 bg-white rounded-lg shadow-md hidden">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($projects as $project): ?>
                            <?php if ($project['is_archived']): // Only show archived projects ?>
                                <li class="flex items-center justify-between px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <!-- Project Color Dot -->
                                        <span class="inline-block w-3 h-3 rounded-full" style="background-color: <?php echo htmlspecialchars($project['color']); ?>"></span>
                                        <!-- Project Name -->
                                        <span class="text-gray-700"><?php echo htmlspecialchars($project['name']); ?></span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <!-- Unarchive Button -->
                                        <form method="POST" action="unarchive_project.php">
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <button type="submit" class="focus:outline-none">
                                            <i class="fa-solid fa-boxes-packing fa-lg" style="color: #76797f;"></i>
                                            </button>
                                        </form>

                                        <!-- Delete Button -->
                                        <form method="POST" action="delete_project.php">
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <button type="submit" class="focus:outline-none">
                                                <svg class="h-6 w-6 text-gray-400 hover:text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 6h-2.5l-1-1h-5l-1 1H5v2h14V6zM7 9v10h10V9H7zM9 11h2v6H9v-6zM13 11h2v6h-2v-6z"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>

    </div>
</div>

<!-- Modal for Create Project -->
<div id="createProjectModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h2 class="text-xl font-semibold">Create Project</h2>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Create Project Form -->
        <form method="POST" action="create_project.php">
            <!-- Project Name -->
            <div class="mb-4">
                <label for="projectName" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="projectName" name="project_name" placeholder="Name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <!-- Project Color -->
            <div class="mb-6">
    <label class="block text-sm font-medium text-gray-700">Color</label>
    <div class="flex space-x-2 mt-2">
        <!-- Predefined Color Options -->
        <label>
            <input type="radio" name="color" value="#A9A9A9" class="sr-only" required>
            <span class="block w-8 h-8 rounded-full bg-gray-500 border-2 border-gray-300 cursor-pointer" onclick="selectColor(this)"></span>
        </label>
        <label>
            <input type="radio" name="color" value="#FF4500" class="sr-only" required>
            <span class="block w-8 h-8 rounded-full bg-orange-500 border-2 border-gray-300 cursor-pointer" onclick="selectColor(this)"></span>
        </label>
        <label>
            <input type="radio" name="color" value="#FFD700" class="sr-only" required>
            <span class="block w-8 h-8 rounded-full bg-yellow-500 border-2 border-gray-300 cursor-pointer" onclick="selectColor(this)"></span>
        </label>
        <label>
            <input type="radio" name="color" value="#32CD32" class="sr-only" required>
            <span class="block w-8 h-8 rounded-full bg-green-500 border-2 border-gray-300 cursor-pointer" onclick="selectColor(this)"></span>
        </label>
        <label>
            <input type="radio" name="color" value="#00BFFF" class="sr-only" required>
            <span class="block w-8 h-8 rounded-full bg-blue-500 border-2 border-gray-300 cursor-pointer" onclick="selectColor(this)"></span>
        </label>
        <label>
            <input type="radio" name="color" value="#6A5ACD" class="sr-only" required>
            <span class="block w-8 h-8 rounded-full bg-purple-500 border-2 border-gray-300 cursor-pointer" onclick="selectColor(this)"></span>
        </label>
        <label>
            <input type="radio" name="color" value="#FF69B4" class="sr-only" required>
            <span class="block w-8 h-8 rounded-full bg-pink-500 border-2 border-gray-300 cursor-pointer" onclick="selectColor(this)"></span>
        </label>

        <!-- Custom Color Picker -->
        <label class="relative">
            <input type="radio" name="color" id="customColorRadio" value="" class="sr-only">
            <span class="block w-8 h-8 rounded-full bg-transparent border-2 border-gray-300 cursor-pointer" id="customColorDisplay"></span>
            <input type="color" id="customColorPicker" class="absolute inset-0 opacity-0 cursor-pointer" onchange="selectCustomColor(this)">
        </label>
    </div>
</div>

<script>
    function selectColor(el) {
        // Deselect custom color if another color is chosen
        document.getElementById('customColorRadio').checked = false;
    }

    function selectCustomColor(input) {
        const customColor = input.value;
        const customColorDisplay = document.getElementById('customColorDisplay');
        const customColorRadio = document.getElementById('customColorRadio');
        
        // Set the color picker as the chosen color
        customColorDisplay.style.backgroundColor = customColor;
        customColorRadio.value = customColor;
        customColorRadio.checked = true;
    }
</script>


            <!-- Buttons -->
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="bg-white text-gray-700 px-4 py-2 border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md shadow hover:bg-blue-600">Create Project</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Function to open the modal
    function openModal() {
        document.getElementById('createProjectModal').classList.remove('hidden');
    }

    // Function to close the modal
    function closeModal() {
        document.getElementById('createProjectModal').classList.add('hidden');
    }

    // Function to select color and apply outline
    function selectColor(element) {
        const colorOptions = document.querySelectorAll('.cursor-pointer');
        colorOptions.forEach(option => {
            option.classList.remove('ring', 'ring-4', 'ring-blue-500');
        });
        element.classList.add('ring', 'ring-4', 'ring-blue-500');
    }

    // Function to show active projects
    function showActive() {
        document.getElementById('activeProjects').classList.remove('hidden');
        document.getElementById('archivedProjects').classList.add('hidden');
    }

    // Function to show archived projects
    function showArchived() {
        document.getElementById('archivedProjects').classList.remove('hidden');
        document.getElementById('activeProjects').classList.add('hidden');
    }
</script>
<script src="https://kit.fontawesome.com/0fffda5efb.js" crossorigin="anonymous"></script>
</body>
</html>
