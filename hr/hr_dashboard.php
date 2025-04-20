<?php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

?>

<?php include '../components/header.php'; ?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Dashboard Header -->
        <div class="flex flex-col items-center mb-10">
            <h1 class="text-4xl font-bold text-blue-700 mb-2">HR Admin Dashboard</h1>
            <div class="h-1 w-24 bg-blue-600 rounded-full mb-2"></div>
            <p class="text-gray-600 text-lg">Manage employees and approvals</p>
        </div>

        <div class="flex justify-end mb-6">
            <a href="logout.php"
                class="bg-red-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-red-700 transition">
                Logout
            </a>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <?php
            // Get employee count
            $employee_count = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];

            // Get pending leave requests count
            $pending_leaves = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'Pending'")->fetch_assoc()['count'];

            // Get departments count
            $departments = $conn->query("SELECT COUNT(DISTINCT department) as count FROM employees")->fetch_assoc()['count'];
            ?>

            <!-- Employee Count -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Total Employees</h3>
                        <p class="text-3xl font-bold text-blue-700"><?php echo $employee_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Leaves -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="bg-amber-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Pending Leaves</h3>
                        <p class="text-3xl font-bold text-amber-600"><?php echo $pending_leaves; ?></p>
                    </div>
                </div>
            </div>

            <!-- Departments -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Departments</h3>
                        <p class="text-3xl font-bold text-green-600"><?php echo $departments; ?></p>
                    </div>
                </div>
            </div>
        </div>

            <!-- Admin Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- Create Employee -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Create Employee</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Add new employees to the system with all their details and
                        credentials.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <a href="create_employee.php"
                            class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-blue-700 hover:to-blue-800 transition-all flex items-center">
                            <span>Create Employee</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Assign Department Manager -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Assign Manager</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Promote qualified employees to department managers to enhance
                        workflow.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-purple-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <a href="manager.php"
                            class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-purple-700 hover:to-purple-800 transition-all flex items-center">
                            <span>Assign Manager</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Leave Approval -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Leave Requests</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Review and manage employee leave requests for proper attendance
                        tracking.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-green-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <a href="approval.php"
                            class="bg-gradient-to-r from-green-600 to-green-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-green-700 hover:to-green-800 transition-all flex items-center">
                            <span>Manage Leaves</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Announcements -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Manage Announcements</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Create and manage announcements for employees to view on the portal.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <a href="announcements.php"
                            class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-indigo-700 hover:to-indigo-800 transition-all flex items-center">
                            <span>Manage Announcements</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Manage Calendar -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Manage Calendar</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Add and manage holidays and events for employees to view on the calendar.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-teal-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <a href="calendar.php"
                            class="bg-gradient-to-r from-teal-600 to-teal-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-teal-700 hover:to-teal-800 transition-all flex items-center">
                            <span>Manage Calendar</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- View Employees -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">View Employees</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">View the complete list of employees and their details.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                        </div>
                        <a href="employee_list.php"
                            class="bg-gradient-to-r from-gray-600 to-gray-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-gray-700 hover:to-gray-800 transition-all flex items-center">
                            <span>View Employees</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>