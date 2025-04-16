<?php
session_start();

// Check if the HR Admin is logged in
if (!isset($_SESSION['hr_loggedin']) || $_SESSION['hr_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}
?>

<?php include '../components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <section class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-6 text-center">HR Admin Dashboard</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Create Employee -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">Create Employee</h2>
                <p class="text-gray-700 mb-4">Add a new employee to the system.</p>
                <a href="create_employee.php" class="block bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition text-center">
                    Create Employee
                </a>
            </div>
            <!-- Assign Department Manager -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">Assign Department Manager</h2>
                <p class="text-gray-700 mb-4">Promote an employee to Department Manager.</p>
                <a href="create_employee.php#assign_manager" class="block bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition text-center">
                    Assign Manager
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