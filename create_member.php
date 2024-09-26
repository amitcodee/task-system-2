<?php
session_start();
include 'config.php'; // Include your database connection

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect the form data
    $name = $_POST['member_name'];
    $email = $_POST['member_email'];
    $pass = password_hash($_POST['member_password'], PASSWORD_DEFAULT); // Hash the password
    $role = $_POST['member_role'];

    // Check if the email already exists in the database
    $email_check_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $email_check_query->bind_param("s", $email);
    $email_check_query->execute();
    $email_check_query->store_result();

    if ($email_check_query->num_rows > 0) {
        // Email already exists, show JavaScript alert
        echo " Email already registered.";
    } else {
        // Prepare the SQL query to insert the new member
        $stmt = $conn->prepare("INSERT INTO users (name, email, pass, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $pass, $role);

        // Execute the query
        if ($stmt->execute()) {
            echo "Member created successfully👍"; // Redirect to a success page
        } else {
            echo "<script>alert " . $stmt->error . "')";
        }

        $stmt->close();
    }

    $email_check_query->close();
    $conn->close();
}
?>
