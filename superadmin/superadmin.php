<?php
session_start();
include '../config.php'; // Include database connection

// Check if the user is logged in and has a valid role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Fetch actual counts from database
// Count total employees/users
$users_query = "SELECT COUNT(*) as total FROM employees";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total'];

// Count distinct admin roles
$roles_query = "SELECT COUNT(DISTINCT role) as total FROM admins";
$roles_result = $conn->query($roles_query);
$total_roles = $roles_result->fetch_assoc()['total'];

// Count distinct departments
$departments_query = "SELECT COUNT(DISTINCT department) as total FROM employees";
$departments_result = $conn->query($departments_query);
$total_departments = $departments_result->fetch_assoc()['total'];

// Simple system health check (could be expanded)
// Check if database connection is active
$system_health = ($conn->ping()) ? 100 : 0;
?>

<?php include '../components/header.php'; ?>

<div class="flex min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">


    <!-- Main Content -->
    <main class="flex-1">
        <!-- Mobile Header -->
        <div class="lg:hidden bg-white shadow-sm p-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-blue-700">Admin Panel</h1>
            <button class="p-2 rounded-md text-gray-700 hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div class="p-6 md:p-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800">Welcome, <span
                            class="text-blue-600"><?= htmlspecialchars($_SESSION['admin_username']); ?></span></h1>
                    <p class="mt-2 text-gray-600">Here's an overview of your system administration tools.</p>
                </div>
                <div class="mt-4 md:mt-0 flex items-center space-x-3">
                    <span class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">Super
                        Admin</span>
                    <a href="logout.php"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center transition duration-200 shadow-sm hover:shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm10 3a1 1 0 00-2 0v4.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L13 10.586V6z"
                                clip-rule="evenodd" />
                        </svg>
                        Logout
                    </a>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Users</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $total_users ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Roles</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $total_roles ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Departments</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $total_departments ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">System Health</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $system_health ?>%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Actions -->
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    class="bg-white rounded-lg shadow-sm overflow-hidden transform transition duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="h-2 bg-blue-600"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-xl font-semibold text-gray-800">Manage Roles</h3>
                        </div>
                        <p class="text-gray-600 mb-6">Add, edit, or remove user roles within the system. Configure
                            permissions and access levels.</p>
                        <a href="add_role.php"
                            class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
                            <span>Manage Roles</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div
                    class="bg-white rounded-lg shadow-sm overflow-hidden transform transition duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="h-2 bg-green-600"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-xl font-semibold text-gray-800">Manage Users</h3>
                        </div>
                        <p class="text-gray-600 mb-6">View, edit, or delete user accounts across the system. Reset
                            passwords and manage user permissions.</p>
                        <a href="#" class="inline-flex items-center text-green-600 hover:text-green-700 font-medium">
                            <span>Manage Users</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div
                    class="bg-white rounded-lg shadow-sm overflow-hidden transform transition duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="h-2 bg-purple-600"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="bg-purple-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-xl font-semibold text-gray-800">System Settings</h3>
                        </div>
                        <p class="text-gray-600 mb-6">Configure global system settings and preferences. Customize the
                            application behavior and appearance.</p>
                        <a href="#" class="inline-flex items-center text-purple-600 hover:text-purple-700 font-medium">
                            <span>System Settings</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../components/footer.php'; ?>