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

<!-- Sidebar Navigation -->
<aside class="w-64 bg-gray-800 text-gray-100 flex flex-col">
    <div class="p-4 flex items-center justify-center">
        <h1 class="text-lg font-semibold">Spack</h1>
    </div>
    <nav class="flex-1 px-4 space-y-2">
        <a href="dashboard.php" class="block py-2 px-3 rounded hover:bg-gray-700">Dashboard</a>
        <a href="add_task.php" class="block py-2 px-3 rounded hover:bg-gray-700"> Tasks</a>
        <a href="project.php" class="block py-2 px-3 rounded hover:bg-gray-700">Projects</a>
        <a href="member.php" class="block py-2 px-3 rounded hover:bg-gray-700">Members</a>

        <!-- Favorite Projects Section -->
        <div class="text-gray-400 mt-4">Favorite Projects</div>

        <!-- Loop through favorite projects and display them -->
        <?php if (count($fav_projects) > 0): ?>
            <?php foreach ($fav_projects as $project): ?>
                <p class="block py-2 px-3 rounded">
                    <?php echo htmlspecialchars($project['name']); ?>
                </p>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-gray-500 px-3">No favorite projects yet.</div>
        <?php endif; ?>

    </nav>
</aside>

