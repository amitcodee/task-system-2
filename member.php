<?php
session_start();
include 'config.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Retrieve all users
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
                        <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded-md">Create Member</button>
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
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['role']); ?></td>
                                    <td class="px-6 py-4 flex space-x-2">
                                        <!-- Edit Button triggers modal -->
                                        <button onclick="openEditModal('<?php echo $member['id']; ?>', '<?php echo $member['name']; ?>', '<?php echo $member['email']; ?>', '<?php echo $member['role']; ?>')" class="text-blue-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13.5V17h3.5l7.036-7.036a2.121 2.121 0 00-3-3L9.5 13.5z"></path>
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <form method="POST" action="delete_member.php">
                                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                            <button type="submit" class="text-red-500">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-1 14H6L5 7m5-4h4l1 4H9l1-4z"></path>
                                                </svg>
                                            </button>
                                        </form>
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

        <form method="POST" action="create_member.php">
            <div class="mb-4">
                <label for="member_name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="member_name" name="member_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="member_email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="member_email" name="member_email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="member_password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="member_password" name="member_password" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-6">
                <label for="member_role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="member_role" name="member_role" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Member">Member</option>
                </select>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('createMemberModal')" class="bg-white text-gray-700 px-4 py-2 border border-gray-300 rounded-md shadow-sm">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md shadow-sm">Create Member</button>
            </div>
        </form>
    </div>
</div>

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

        <form method="POST" action="edit_member.php">
            <input type="hidden" id="edit_member_id" name="member_id">

            <div class="mb-4">
                <label for="edit_member_name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="edit_member_name" name="member_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="edit_member_email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="edit_member_email" name="member_email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="edit_member_password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="edit_member_password" name="member_password" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-6">
                <label for="edit_member_role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="edit_member_role" name="member_role" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Member">Member</option>
                </select>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('editMemberModal')" class="bg-white text-gray-700 px-4 py-2 border border-gray-300 rounded-md shadow-sm">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md shadow-sm">Update Member</button>
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
</script>

</body>
</html>
