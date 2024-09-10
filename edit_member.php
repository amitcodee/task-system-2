<?php
session_start();
include 'config.php'; // Your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['member_id'];
    $name = $_POST['member_name'];
    $email = $_POST['member_email'];
    $role = $_POST['member_role'];

    // Check if the password field is not empty
    if (!empty($_POST['member_password'])) {
        // Hash the password using bcrypt
        $hashed_password = password_hash($_POST['member_password'], PASSWORD_DEFAULT);

        // Update query including password
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, pass = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $hashed_password, $role, $id);
    } else {
        // Update query excluding password
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $role, $id);
    }

    // Execute the query and check for success
    if ($stmt->execute()) {
        echo 'Member updated successfully';
    } else {
        echo 'Error updating member: ' . $stmt->error;
    }

    $stmt->close();
}
?>
