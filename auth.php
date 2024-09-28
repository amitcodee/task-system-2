<?php
// Start the session
session_start();

// Enable error reporting for debugging (only during development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration file
include 'config.php'; 

// Check if the request method is POST (ensure it's a form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the email and password from the form
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all the fields!";
        header("Location: login.php");
        exit();
    }

    // Prepare SQL statement to fetch the user details based on the email
    if ($stmt = $conn->prepare("SELECT id, name, pass, role, last_login FROM users WHERE email = ?")) {
        // Bind the email to the prepared statement
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result(); // Store the result to check if user exists

        // Check if the user exists
        if ($stmt->num_rows > 0) {
            // Bind the results
            $stmt->bind_result($user_id, $name, $stored_password, $role, $last_login);
            $stmt->fetch();

            // Verify the password using password_verify() (checking the hashed password)
            if (password_verify($password, $stored_password)) {
                // If password matches, set session variables
                $_SESSION['user_email'] = $email;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['last_login'] = $last_login; // Store the last login time

                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Update the last login date and time
                $date = new DateTime("now", new DateTimeZone('Asia/Kolkata'));
                $current_time = $date->format('Y-m-d H:i:s');

                // Prepare the query to update last login
                $update_login = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
                $update_login->bind_param("si", $current_time, $user_id);
                $update_login->execute();
                $update_login->close();

                // Redirect to the dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                // If password doesn't match, return an error
                $_SESSION['error'] = "Invalid password!";
                header("Location: login.php");
                exit();
            }
        } else {
            // If no user is found with the email, return an error
            $_SESSION['error'] = "No user found with that email!";
            header("Location: login.php");
            exit();
        }

        // Close the statement
        $stmt->close();
    } else {
        // If the SQL query fails, log the error
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: login.php");
        exit();
    }

    // Close the database connection
    $conn->close();
} else {
    // If the page is accessed without submitting the form, redirect to the login page
    header("Location: login.php");
    exit();
}
?>
