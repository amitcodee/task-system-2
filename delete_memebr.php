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
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $member_id = (int)$_POST['id']; // Cast to int for safety

    // Debugging: Log the member ID
    echo "Received member ID: " . $member_id;

    // Check if the member exists before deleting
    $check_member = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check_member->bind_param("i", $member_id);
    $check_member->execute();
    $check_member->store_result();

    if ($check_member->num_rows > 0) {
        // Member exists, proceed with deletion
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $member_id);

        if ($stmt->execute()) {
            echo "Member deleted successfully";
        } else {
            echo "Error deleting member: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Member not found.";
    }

    $check_member->close();
} else {
    echo "No member ID provided.";
}

$conn->close();
