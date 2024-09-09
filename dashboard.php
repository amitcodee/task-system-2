<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header('Location: login.php');
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
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Include Sidenav -->
        <?php include 'sidenav.php'; ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Include Header -->
            <?php include 'header.php'; ?>

            <!-- Dashboard Content -->
            <main class="flex-1 p-6 bg-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 1 -->
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-200 rounded-full">
                                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4-4 4 4m0 0l-4-4m4 4V7"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold">Open Tasks</h4>
                                <p class="text-gray-600 text-sm">138</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-200 rounded-full">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold">Completed Tasks</h4>
                                <p class="text-gray-600 text-sm">60</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-200 rounded-full">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold">Total Projects</h4>
                                <p class="text-gray-600 text-sm">17</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphs -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-lg font-semibold mb-4">Completed in the last 7 days</h4>
                        <canvas id="completedTasksChart"></canvas>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-lg font-semibold mb-4">Most productive month</h4>
                        <canvas id="productiveMonthChart"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Graphs Configuration -->
    <script>
        // Completed Tasks Chart
        const ctx1 = document.getElementById('completedTasksChart').getContext('2d');
        const completedTasksChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Mon'],
                datasets: [{
                    label: 'Tasks',
                    data: [4, 3, 2, 0, 5, 4, 2],
                    borderColor: 'rgba(96, 165, 250, 1)',
                    backgroundColor: 'rgba(96, 165, 250, 0.3)',
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Most Productive Month Chart
        const ctx2 = document.getElementById('productiveMonthChart').getContext('2d');
        const productiveMonthChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Completed Tasks',
                    data: [0, 0, 0, 0, 0, 0, 0, 20, 10, 0, 0, 0],
                    borderColor: 'rgba(96, 165, 250, 1)',
                    backgroundColor: 'rgba(96, 165, 250, 0.3)',
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
