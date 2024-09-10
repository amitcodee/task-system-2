<?php
include 'config.php'; // Database connection

$task_id = $_GET['task_id'];

$response_query = $conn->prepare("
    SELECT 
        tr.comment, tr.file_path, tr.output_link, tr.created_at, 
        u.name, u.profile_image 
    FROM task_responses tr 
    JOIN users u ON tr.user_id = u.id 
    WHERE tr.task_id = ?
");
$response_query->bind_param("i", $task_id);
$response_query->execute();
$response_query->bind_result($comment, $file_path, $output_link, $created_at, $username, $profile_image);

while ($response_query->fetch()) {
    echo '<div class="mb-2">';
    echo '<div class="flex items-center space-x-3">';
    echo '<img src="' . htmlspecialchars($profile_image) . '" alt="' . htmlspecialchars($username) . '" class="w-10 h-10 rounded-full">';
    echo '<div>';
    echo '<strong>' . htmlspecialchars($username) . '</strong> - ' . date("g:i A", strtotime($created_at)) . '<br>';
    if ($comment) {
        echo '<p>' . htmlspecialchars($comment) . '</p>';
    }
    if ($file_path) {
        echo '<p><a href="' . htmlspecialchars($file_path) . '" class="text-blue-500" download>Download File</a></p>';
    }
    if ($output_link) {
        echo '<p><a href="' . htmlspecialchars($output_link) . '" target="_blank" class="text-blue-500">View Output</a></p>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
$response_query->close();
?>
