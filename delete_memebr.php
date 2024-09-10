<?php
session_start();
include 'config.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['user_email'])) {
    echo 'Unauthorized request';
    exit;
}

// Fetch the logged-in user's role to verify permissions
$query_role = $conn->prepare("SELECT role FROM users WHERE email = ?");
$query_role->bind_param("s", $_SESSION['user_email']);
$query_role->execute();
$query_role->bind_result($logged_in_user_role);
$query_role->fetch();
$query_role->close();

// Only allow admins to delete members
if ($logged_in_user_role !== 'Admin') {
    echo "Unauthorized access";
    exit;
}

// Check if the member ID is provided via POST
if (isset($_POST['id'])) {
    $member_id = $_POST['id'];

    // Prepare the delete query
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $member_id);

    // Execute the query and check if successful
    if ($stmt->execute()) {
        echo "Member deleted successfully";
    } else {
        echo "Error deleting member: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "No member ID provided.";
}

$conn->close();
?>
