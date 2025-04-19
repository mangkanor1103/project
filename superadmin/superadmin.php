<?php
session_start();

// Check if the user is logged in and has a valid role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}
?>

<?php include '../components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <section class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-6 text-center">Welcome, <?= htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Add Role -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">Manage Roles</h2>
                <p class="text-gray-700 mb-4">Add, edit, or remove user roles within the system.</p>
                <a href="add_role.php" class="block bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition text-center">
                    Add Role
                </a>
            </div>
            <!-- Other Admin Tasks -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">Manage Users</h2>
                <p class="text-gray-700 mb-4">View, edit, or delete user accounts across the system.</p>
                <a href="#" class="block bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition text-center">
                    Manage Users
                </a>
            </div>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">System Settings</h2>
                <p class="text-gray-700 mb-4">Configure global system settings and preferences.</p>
                <a href="#" class="block bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition text-center">
                    System Settings
                </a>
            </div>
        </div>
        <div class="text-center mt-6">
            <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition">
                Logout
            </a>
        </div>
    </section>
</main>

<?php include '../components/footer.php'; ?>