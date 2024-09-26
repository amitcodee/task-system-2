<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Fetch the logged-in user's role
$logged_in_user_role = '';
$query_role = $conn->prepare("SELECT role FROM users WHERE email = ?");
$query_role->bind_param("s", $_SESSION['user_email']);
$query_role->execute();
$query_role->bind_result($logged_in_user_role);
$query_role->fetch();
$query_role->close();

// Handle member deletion if a POST request is made for deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $member_id = (int)$_POST['id']; // Cast to int for safety

    // Only allow admins to delete members
    if ($logged_in_user_role !== 'Admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit;
    }

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
            echo json_encode(['status' => 'success', 'message' => 'Member deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error deleting member: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Member not found.']);
    }

    $check_member->close();
    exit;
}

// Retrieve all users for displaying the members list
$query = $conn->prepare("SELECT id, name, email, role FROM users");
$query->execute();
$query->bind_result($id, $name, $email, $role);
$members = [];
while ($query->fetch()) {
    $members[] = [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'role' => $role
    ];
}
$query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Members</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        /* Make modals hidden by default */
        .hidden { display: none; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex h-screen">

    <!-- Include Sidebar -->
    <?php include 'sidenav.php'; ?>

    <div class="flex-1 flex flex-col">

        <!-- Include Header -->
        <?php include 'header.php'; ?>

        <!-- Team Members List -->
        <main class="flex-1 p-6 bg-gray-100">
            <div class="container mx-auto">
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="p-4 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold">Team Members</h2>
                        <?php if ($logged_in_user_role === 'Admin'): ?>
                            <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded-md">Create Member</button>
                        <?php endif; ?>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="memberTable">
                            <?php foreach ($members as $member): ?>
                                <tr id="member_<?php echo $member['id']; ?>">
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['role']); ?></td>
                                    <td class="px-6 py-4 flex space-x-2">
                                        <?php if ($logged_in_user_role === 'Admin'): ?>
                                            <!-- Edit Button triggers modal -->
                                            <button onclick="openEditModal('<?php echo $member['id']; ?>', '<?php echo $member['name']; ?>', '<?php echo $member['email']; ?>', '<?php echo $member['role']; ?>')" class="text-blue-500">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13.5V17h3.5l7.036-7.036a2.121 2.121 0 00-3-3L9.5 13.5z"></path>
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button onclick="deleteMember('<?php echo $member['id']; ?>')" class="text-red-500">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-1 14H6L5 7m5-4h4l1 4H9l1-4z"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>
</div>

<!-- Modal for Create Member -->
<div id="createMemberModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h2 class="text-xl font-semibold">Create Member</h2>
            <button onclick="closeModal('createMemberModal')" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="createMemberForm">
            <div class="mb-4">
                <label for="member_name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="member_name" name="member_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="member_email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="member_email" name="member_email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <!-- Password Input with Eye Icon -->
            <div class="mb-4 relative">
                <label for="member_password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="member_password" name="member_password" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                <!-- Eye Icon -->
                <span class="absolute inset-y-0 right-3 flex items-center cursor-pointer" onclick="togglePasswordVisibility()">
                    <i id="eyeIcon" class="fas fa-eye text-gray-400 mt-5"></i>
                </span>
            </div>

            <div class="mb-6">
                <label for="member_role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="member_role" name="member_role" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    <option value="Admin">Admin</option>
                    <!-- <option value="Manager">Manager</option> -->
                    <option value="Member">Member</option>
                </select>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('createMemberModal')" class="bg-white text-gray-700 px-4 py-2 border border-gray-300 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Create Member</button>
            </div>
        </form>
    </div>
</div>

<!-- Toggle Password Visibility Script -->
<script>
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('member_password');
        const eyeIcon = document.getElementById('eyeIcon');
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        eyeIcon.classList.toggle('fa-eye-slash');
    }
</script>


<!-- Modal for Edit Member -->
<div id="editMemberModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h2 class="text-xl font-semibold">Edit Member</h2>
            <button onclick="closeModal('editMemberModal')" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="editMemberForm">
            <input type="hidden" id="edit_member_id" name="member_id">

            <div class="mb-4">
                <label for="edit_member_name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="edit_member_name" name="member_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="edit_member_email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="edit_member_email" name="member_email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="edit_member_password" class="block text-sm font-medium text-gray-700">Password (optional)</label>
                <input type="password" id="edit_member_password" name="member_password" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
            </div>

            <div class="mb-6">
                <label for="edit_member_role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="edit_member_role" name="member_role" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Member">Member</option>
                </select>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('editMemberModal')" class="bg-white text-gray-700 px-4 py-2 border border-gray-300 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Update Member</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createMemberModal').classList.remove('hidden');
    }

    function openEditModal(id, name, email, role) {
        document.getElementById('edit_member_id').value = id;
        document.getElementById('edit_member_name').value = name;
        document.getElementById('edit_member_email').value = email;
        document.getElementById('edit_member_role').value = role;
        document.getElementById('editMemberModal').classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // AJAX for creating a member
    $('#createMemberForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url: 'create_member.php',
            data: $(this).serialize(),
            success: function(response) {
                alert(response);
                location.reload(); // Reload the page after creation
            }
        });
    });

    // AJAX for editing a member
    $('#editMemberForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url: 'edit_member.php',
            data: $(this).serialize(),
            success: function(response) {
                alert(response);
                location.reload(); // Reload the page after update
            }
        });
    });

    function deleteMember(memberId) {
    if (confirm('Are you sure you want to delete this member?')) {
        $.ajax({
            type: 'POST',
            url: 'member.php', // Send the request to the same file
            data: { id: memberId, action: 'delete' }, // Pass 'id' and 'action' to handle deletion
            success: function(response) {
                const res = JSON.parse(response);
                alert(res.message); // Show the response message
                if (res.status === 'success') {
                    $('#member_' + memberId).remove(); // Remove the member row from the table
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error); // In case of any issues
            }
        });
    }
}

</script>

</body>
</html>
