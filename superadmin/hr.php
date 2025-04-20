<!-- filepath: c:\xampp\htdocs\project\superadmin\hr.php -->
<?php
session_start();
include '../config.php'; // Include database connection

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    header("Location: ../index.php");
    exit();
}

// Handle Add Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = 'admin'; // Force role to 'admin'

    if (!empty($username) && !empty($password)) {
        $sql = "INSERT INTO admins (username, password, role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $success_message = "Admin added successfully.";
    } else {
        $error_message = "All fields are required.";
    }
}

// Handle Update Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'];
    $username = trim($_POST['username']);

    if (!empty($id) && !empty($username)) {
        $sql = "UPDATE admins SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND role = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        $success_message = "Admin updated successfully.";
    } else {
        $error_message = "All fields are required.";
    }
}

// Handle Delete Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];

    if (!empty($id)) {
        $sql = "DELETE FROM admins WHERE id = ? AND role = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success_message = "Admin deleted successfully.";
    } else {
        $error_message = "Invalid admin ID.";
    }
}

// Fetch all admins (excluding superadmin)
$sql = "SELECT * FROM admins WHERE role = 'admin'";
$result = $conn->query($sql);
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../components/header.php'; ?>

<div class="container mx-auto px-4 py-10">
    <!-- Back Button -->
    <a href="superadmin.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg shadow hover:bg-gray-400 transition mb-6 inline-block">
        &larr; Back to Dashboard
    </a>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Admins</h1>

    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Add Admin Button -->
    <button onclick="openModal('add-modal')" class="bg-blue-600 text-white px-6 py-3 rounded-lg mb-6">Add Admin</button>

    <!-- Admins Table -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Admins List</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $admin['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($admin['username']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $admin['created_at']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <!-- Update Admin -->
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($admin)); ?>)"
                                class="text-blue-600 hover:text-blue-800">Edit</button>

                            <!-- Delete Admin -->
                            <button onclick="openDeleteModal(<?= $admin['id']; ?>)"
                                class="text-red-600 hover:text-red-800 ml-2">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Admin Modal -->
<div id="add-modal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Add Admin</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label for="add-username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="add-username" name="username" class="mt-1 p-3 border rounded-lg w-full" required>
            </div>
            <div class="mb-4">
                <label for="add-password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="add-password" name="password" class="mt-1 p-3 border rounded-lg w-full" required>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg">Add Admin</button>
            <button type="button" onclick="closeModal('add-modal')" class="ml-2 bg-gray-300 text-gray-800 px-6 py-3 rounded-lg">Cancel</button>
        </form>
    </div>
</div>

<!-- Edit Admin Modal -->
<div id="edit-modal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Admin</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit-id" name="id">
            <div class="mb-4">
                <label for="edit-username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="edit-username" name="username" class="mt-1 p-3 border rounded-lg w-full" required>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg">Update Admin</button>
            <button type="button" onclick="closeModal('edit-modal')" class="ml-2 bg-gray-300 text-gray-800 px-6 py-3 rounded-lg">Cancel</button>
        </form>
    </div>
</div>

<!-- Delete Admin Modal -->
<div id="delete-modal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Delete Admin</h2>
        <p>Are you sure you want to delete this admin?</p>
        <form method="POST" class="mt-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" id="delete-id" name="id">
            <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg">Delete</button>
            <button type="button" onclick="closeModal('delete-modal')" class="ml-2 bg-gray-300 text-gray-800 px-6 py-3 rounded-lg">Cancel</button>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function openEditModal(admin) {
        document.getElementById('edit-id').value = admin.id;
        document.getElementById('edit-username').value = admin.username;
        openModal('edit-modal');
    }

    function openDeleteModal(adminId) {
        document.getElementById('delete-id').value = adminId;
        openModal('delete-modal');
    }
</script>

<?php include '../components/footer.php'; ?>