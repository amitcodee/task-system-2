<?php
session_start();
include 'config.php'; // Include your database connection

// Fetch the search query from the URL or set it as an empty string
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch the filter option for the last login time (e.g., today, yesterday)
$login_filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';

// Define date ranges for filtering
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$three_days_ago = date('Y-m-d', strtotime('-3 days'));
$five_days_ago = date('Y-m-d', strtotime('-5 days'));

// Prepare the base SQL query
$query = "
    SELECT id, name, role, last_login
    FROM users
    WHERE name LIKE ?";

// Add condition based on the selected filter
switch ($login_filter) {
    case 'Today':
        $query .= " AND DATE(last_login) = '$today'";
        break;
    case 'Yesterday':
        $query .= " AND DATE(last_login) = '$yesterday'";
        break;
    case 'ThreeDaysAgo':
        $query .= " AND DATE(last_login) >= '$three_days_ago'";
        break;
    case 'FiveDaysAgo':
        $query .= " AND (last_login IS NULL OR DATE(last_login) <= '$five_days_ago')";
        break;
}

// Append the order by clause
$query .= " ORDER BY last_login DESC";

// Prepare the query and bind the search query
$stmt = $conn->prepare($query);
$search_term = "%" . $search_query . "%";
$stmt->bind_param("s", $search_term);

// Execute the query and fetch results
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login Dates</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">

<div class="flex h-screen">
    <!-- Include Sidebar -->
    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">

        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 bg-gray-100">
            <div class="container mx-auto">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">User Login Dates</h2>

                    <!-- Search Bar -->
                    <form action="user_login_date.php" method="GET" class="mb-6">
                        <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search_query); ?>" class="border border-gray-300 p-2 rounded w-full">
                    </form>

                    <!-- Navigation Tabs for Filtering -->
                    <nav class="flex mb-6">
                        <a href="?filter=All&search=<?php echo htmlspecialchars($search_query); ?>" class="px-4 py-2 text-sm font-medium <?php echo $login_filter === 'All' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            All
                        </a>
                        <a href="?filter=Today&search=<?php echo htmlspecialchars($search_query); ?>" class="px-4 py-2 text-sm font-medium <?php echo $login_filter === 'Today' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            Today
                        </a>
                        <!-- <a href="?filter=Yesterday&search=<?php echo htmlspecialchars($search_query); ?>" class="px-4 py-2 text-sm font-medium <?php echo $login_filter === 'Yesterday' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            Yesterday
                        </a> -->
                        <!-- <a href="?filter=ThreeDaysAgo&search=<?php echo htmlspecialchars($search_query); ?>" class="px-4 py-2 text-sm font-medium <?php echo $login_filter === 'ThreeDaysAgo' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            Last 3 Days
                        </a> -->
                        <a href="?filter=FiveDaysAgo&search=<?php echo htmlspecialchars($search_query); ?>" class="px-4 py-2 text-sm font-medium <?php echo $login_filter === 'FiveDaysAgo' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'; ?>">
                            Not Logged In for 5+ Days
                        </a>
                    </nav>

                    <!-- Display user login data in a table -->
                    <table class="min-w-full bg-white custom-table">
                        <thead>
                            <tr>
                                <th class="text-left">SNo.</th>
                                <th class="text-left">Name</th>
                                <th class="text-left">Role</th>
                                <th class="text-left">Last Login Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php 
                                    static $i = 0;
                                    echo ++$i;
                                     ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td>
                                        <?php 
                                        if ($user['last_login']) {
                                            // Format the date as "25-September 2024 at 08:49 PM"
                                            echo htmlspecialchars(date("d-F Y \a\\t h:i A", strtotime($user['last_login'])));
                                        } else {
                                            echo 'Never Logged In';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </main>

    </div>
</div>

</body>

</html>
