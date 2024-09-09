<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user_email'];

// Include the database connection
include 'config.php';

// Fetch user data (email, profile image, role)
$query = $conn->prepare("SELECT name, role, profile_image FROM users WHERE email = ?");
$query->bind_param("s", $user_email);
$query->execute();
$query->bind_result($user_name, $user_role, $profile_image);
$query->fetch();
$query->close();

// Use a default image if no profile image is set
$profile_image_src = $profile_image ? $profile_image : 'https://via.placeholder.com/150';
?>

<header class="flex items-center justify-between bg-white p-4 shadow">
    <h2 class="text-xl font-semibold">Dashboard</h2>
    <div class="flex items-center">
        <!-- Notification Icon -->
        <button class="text-gray-600 hover:text-gray-800 focus:outline-none">
            <span class="sr-only">Notifications</span>
            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2a6 6 0 00-6 6v5c0 .55-.45 1-1 1s-1-.45-1-1V8a8 8 0 1116 0v5c0 .55-.45 1-1 1s-1-.45-1-1V8a6 6 0 00-6-6zm-2 18h4a2 2 0 01-4 0z"></path>
            </svg>
        </button>

        <!-- Profile Dropdown -->
        <div class="relative ml-4">
            <button class="flex items-center text-sm focus:outline-none" id="user-menu-button" aria-expanded="false" aria-haspopup="true" onclick="toggleDropdown()">
                <img class="h-8 w-8 rounded-full" src="<?php echo htmlspecialchars($profile_image_src); ?>" alt="User Avatar">
                <span class="ml-2 text-gray-600"><?php echo htmlspecialchars($user_name); ?></span>
                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div id="user-menu" class="absolute right-0 mt-2 w-48 bg-white border rounded-md shadow-lg py-2 hidden">
                <div class="px-4 py-2 text-sm text-gray-700">
                    Signed in as <br>
                    <strong><?php echo htmlspecialchars($user_email); ?></strong>
                </div>
                <div class="px-4 py-2 text-sm text-gray-500">
                    Role: <strong><?php echo htmlspecialchars($user_role); ?></strong>
                </div>
                <div class="border-t border-gray-200"></div>
                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
            </div>
        </div>
    </div>
</header>

<script>
// Toggle the dropdown visibility
function toggleDropdown() {
    const dropdown = document.getElementById('user-menu');
    dropdown.classList.toggle('hidden');
}

// Close the dropdown if clicked outside
window.addEventListener('click', function(e) {
    const button = document.getElementById('user-menu-button');
    const dropdown = document.getElementById('user-menu');

    if (!button.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>
