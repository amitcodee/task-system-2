<?php
session_start();
include 'config.php'; // Include the database connection file

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare the SQL statement to fetch user details
    if ($stmt = $conn->prepare("SELECT id, name, pass, role, last_login FROM users WHERE email = ?")) {
        // Bind parameters and execute the query
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // Check if the user exists
        if ($stmt->num_rows > 0) {
            // Bind the result to variables
            $stmt->bind_result($user_id, $name, $stored_password, $role, $last_login);
            $stmt->fetch();

            // Verify the password using password_verify()
            if (password_verify($password, $stored_password)) {
                // If password is correct, set session variables
                $_SESSION['user_email'] = $email;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['last_login'] = $last_login; // Store last login in session

                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Update the last login date and time in the database
                // Set the time zone to India (Asia/Kolkata)
                $date = new DateTime("now", new DateTimeZone('Asia/Kolkata'));

                // Get the current date and time in the specified time zone
                $current_time = $date->format('Y-m-d H:i:s');

                // Update the last_login field for the user
                $update_login = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
                $update_login->bind_param("si", $current_time, $user_id);
                $update_login->execute();
                $update_login->close();


                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                // Password is incorrect
                $_SESSION['error'] = "Invalid password!";
                header("Location: login.php");
                exit();
            }
        } else {
            // Email not found in the database
            $_SESSION['error'] = "Invalid email!";
            header("Location: login.php");
            exit();
        }

        // Close the statement
        $stmt->close();
    } else {
        // If the SQL statement fails, throw an error
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: login.php");
        exit();
    }

    // Close the database connection
    $conn->close();
} else {
    // If accessed directly, redirect to the login page
    header("Location: login.php");
    exit();
}
