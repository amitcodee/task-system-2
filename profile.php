<?php
session_start();
include 'config.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Retrieve current user info from the database
$user_email = $_SESSION['user_email'];
$query = $conn->prepare("SELECT name, email, pass, profile_image FROM users WHERE email = ?");
$query->bind_param("s", $user_email);
$query->execute();
$query->bind_result($user_name, $user_email, $stored_password, $profile_image);
$query->fetch();
$query->close();

// Handle form submission to update profile (only update UI without affecting login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password'];
    $profile_image_path = $profile_image; // Use current profile image if no new image is uploaded

    // Check if a new profile image is uploaded
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $target_dir = "upload/";
        $target_file = $target_dir . basename($_FILES['profile_image']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate the uploaded file is an image
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false) {
            // Move uploaded file to the target directory
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image_path = "upload/" . basename($_FILES['profile_image']['name']); // Save the relative path to the database
            } else {
                echo "<script>alert('Error uploading image.');</script>";
            }
        } else {
            echo "<script>alert('File is not an image.');</script>";
        }
    }

    // Only update the password if the user entered a new one
    if (!empty($new_password)) {
        $update_password = ", pass = ?";
        $new_password = password_hash($new_password, PASSWORD_DEFAULT);  // Hash the password before storing it
    } else {
        $update_password = "";  // If no new password, don't update it
    }

    // Prepare the update query based on whether a new password is provided or not
    if (!empty($update_password)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, pass = ?, profile_image = ? WHERE email = ?");
        $stmt->bind_param("sssss", $new_name, $new_email, $new_password, $profile_image_path, $user_email);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_image = ? WHERE email = ?");
        $stmt->bind_param("ssss", $new_name, $new_email, $profile_image_path, $user_email);
    }

    // Execute the query
    if ($stmt->execute()) {
        // Update session with the new email only if it's different from the current one
        if ($new_email !== $user_email) {
            $_SESSION['user_email'] = $new_email;
        }

        // Show success message
        echo "<script>alert('Profile updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating profile.');</script>";
    }
    $stmt->close();

    // Refresh the page to reflect the changes without affecting login
    header('Location: profile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">

    <div class="flex h-screen">

        <!-- Include Sidebar -->
        <?php include 'sidenav.php'; ?>

        <div class="flex-1 flex flex-col">

            <!-- Include Header -->
            <?php include 'header.php'; ?>

            <!-- Profile Content -->
            <main class="flex-1 p-6 bg-gray-100">
                <div class="container mx-auto p-6">
                    <div class="bg-white p-8 rounded-lg shadow-lg max-w-lg mx-auto">
                        <h2 class="text-2xl font-semibold mb-6">Profile</h2>

                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <div class="flex justify-center mb-6">
                                <div class="relative">
                                    <?php if ($profile_image): ?>
                                        <img src="<?php echo $profile_image; ?>" alt="Profile Image" class="mt-4 h-24 w-24 rounded-full object-cover">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Name -->

                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            </div>

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            </div>

                            <!-- New Password (Optional) -->
                            <div class="mb-6">
                                <label for="password" class="block text-sm font-medium text-gray-700">New Password (Optional)</label>
                                <input type="password" id="password" name="password" placeholder="New Password (Optional)" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Profile Image -->
                            <div class="mb-6">
                                <label for="profile_image" class="block text-sm font-medium text-gray-700">Profile Image</label>
                                <input type="file" id="profile_image" name="profile_image" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">

                            </div>

                            <!-- Save Button -->
                            <div class="text-right">
                                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>

        </div>
    </div>

</body>

</html>