<?php
session_start();
include 'config.php'; // Include the database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $member_name = $_POST['member_name'];
    $member_email = $_POST['member_email'];
    $member_role = $_POST['member_role'];
    $member_password = $_POST['member_password']; // Password is optional

    // Prepare the SQL query
    if (!empty($member_password)) {
        // If the user provided a new password, hash it
        $hashed_password = password_hash($member_password, PASSWORD_DEFAULT);
        $query = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, pass = ? WHERE id = ?");
        $query->bind_param("ssssi", $member_name, $member_email, $member_role, $hashed_password, $member_id);
    } else {
        // If no new password is provided, update only the name, email, and role
        $query = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        $query->bind_param("sssi", $member_name, $member_email, $member_role, $member_id);
    }

    // Execute the query and check for success
    if ($query->execute()) {
        // Redirect back to the members list or display a success message
        $_SESSION['success_message'] = "Member updated successfully!";
        header('Location: member.php');
        exit;
    } else {
        // Handle error
        $_SESSION['error_message'] = "Error updating member: " . $query->error;
        header('Location: member.php');
        exit;
    }

    $query->close();
}
?>
