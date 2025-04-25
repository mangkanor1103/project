<?php
session_start();
include '../config.php'; // Include database connection

// Check if the user is logged in and has a valid role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Handle password verification if submitted
$password_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_password'])) {
    $current_password = $_POST['current_password'];

    // Get admin's stored password hash
    $admin_id = $_SESSION['admin_id'];
    $query = "SELECT password FROM admins WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($current_password, $row['password'])) {
            // Password is correct, redirect to password.php
            header('Location: password.php');
            exit();
        } else {
            $password_error = 'Incorrect password. Please try again.';
        }
    } else {
        $password_error = 'Error retrieving account information.';
    }
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
                    <a id="logoutLink" href="logout.php"
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

            <!-- Quick Actions -->
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
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-xl font-semibold text-gray-800">Manage HR</h3>
                        </div>
                        <p class="text-gray-600 mb-6">View and manage HR-related tasks, including employee records and
                            payroll.</p>
                        <a href="hr.php" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
                            <span>Go to HR</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Feedback Quick Action -->
                <div
                    class="bg-white rounded-lg shadow-sm overflow-hidden transform transition duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="h-2 bg-green-600"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16h6m-7 4h8a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-xl font-semibold text-gray-800">Feedback</h3>
                        </div>
                        <p class="text-gray-600 mb-6">View and manage feedback submitted by users.</p>
                        <a href="feedback.php"
                            class="inline-flex items-center text-green-600 hover:text-green-700 font-medium">
                            <span>Go to Feedback</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Update My Password Quick Action -->
                <div
                    class="bg-white rounded-lg shadow-sm overflow-hidden transform transition duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="h-2 bg-red-600"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="bg-red-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-xl font-semibold text-gray-800">Update My Password</h3>
                        </div>
                        <p class="text-gray-600 mb-6">Change your account password to keep your account secure.</p>
                        <button class="inline-flex items-center text-red-600 hover:text-red-700 font-medium"
                            onclick="document.getElementById('passwordVerifyModal').classList.remove('hidden')">
                            <span>Go to Password Update</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Password Verification Modal -->
<div id="passwordVerifyModal"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="border-b px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Verify Your Password</h3>
                <button type="button" onclick="document.getElementById('passwordVerifyModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form method="POST" class="px-6 py-4">
            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Please enter your
                    current password to continue</label>
                <input type="password" name="current_password" id="current_password"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    required>
                <?php if (!empty($password_error)): ?>
                    <p class="text-red-600 text-sm mt-1"><?= $password_error ?></p>
                <?php endif; ?>
            </div>
            <div class="flex justify-end mt-4">
                <button type="button" onclick="document.getElementById('passwordVerifyModal').classList.add('hidden')"
                    class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md mr-2 hover:bg-gray-300 transition">Cancel</button>
                <button type="submit" name="verify_password"
                    class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">Verify &
                    Continue</button>
            </div>
        </form>
    </div>
</div>

<!-- Permission Management Modal -->
<div id="permissionsModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 overflow-hidden">
        <div class="border-b px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Manage HR Admin Permissions</h3>
                <button type="button" onclick="document.getElementById('permissionsModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
            <div id="permissionsList" class="space-y-6">
                <!-- Admin permissions will be loaded here -->
                <div class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <p class="mt-2 text-gray-600">Loading admins...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add a Quick Action for Permission Management -->
<script>
    // Add this to your existing grid of Quick Actions
    document.addEventListener('DOMContentLoaded', function () {
        const quickActionsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-6');

        if (quickActionsContainer) {
            const permissionsAction = document.createElement('div');
            permissionsAction.className = 'bg-white rounded-lg shadow-sm overflow-hidden transform transition duration-300 hover:shadow-md hover:-translate-y-1';
            permissionsAction.innerHTML = `
                <div class="h-2 bg-purple-600"></div>
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="ml-3 text-xl font-semibold text-gray-800">Admin Permissions</h3>
                    </div>
                    <p class="text-gray-600 mb-6">Manage which features each HR admin can access in the system.</p>
                    <button id="managePermissionsBtn" class="inline-flex items-center text-purple-600 hover:text-purple-700 font-medium">
                        <span>Manage Permissions</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            `;

            quickActionsContainer.appendChild(permissionsAction);

            document.getElementById('managePermissionsBtn').addEventListener('click', function () {
                loadAdminPermissions();
                document.getElementById('permissionsModal').classList.remove('hidden');
            });
        }
    });

    // List of available permissions
    const availablePermissions = [
        { key: 'create_employee', name: 'Create Employee', description: 'Add new employees to the system' },
        { key: 'assign_manager', name: 'Assign Department Manager', description: 'Assign managers to departments' },
        { key: 'leave_approval', name: 'Leave Approval', description: 'Approve or reject leave requests' },
        { key: 'manage_announcements', name: 'Manage Announcements', description: 'Create and manage announcements' },
        { key: 'manage_calendar', name: 'Manage Calendar', description: 'Manage events and holidays' },
        { key: 'expense_approval', name: 'Expense Approval', description: 'Approve expense reimbursements' },
        { key: 'view_employees', name: 'View Employees', description: 'View the list of all employees' },
        { key: 'manage_payslips', name: 'Manage Payslips', description: 'Generate and manage payslips' },
        { key: 'manage_departments', name: 'Manage Departments', description: 'Add and edit departments' },
        { key: 'manage_attendance', name: 'Manage Attendance', description: 'Record and edit employee attendance' },
        { key: 'manage_job_positions', name: 'Manage Job Positions', description: 'Add and edit job positions' },
        { key: 'manage_work_preferences', name: 'Manage Work Preferences', description: 'Set employee work preferences' },

    ];

    // Load admin permissions
    function loadAdminPermissions() {
        fetch('../api/get_admins_permissions.php')
            .then(response => response.json())
            .then(data => {
                const permissionsList = document.getElementById('permissionsList');
                permissionsList.innerHTML = '';

                if (data.length === 0) {
                    permissionsList.innerHTML = '<p class="text-center py-8 text-gray-500">No HR admins found.</p>';
                    return;
                }

                data.forEach(admin => {
                    const adminPermissions = admin.permissions || [];

                    const adminCard = document.createElement('div');
                    adminCard.className = 'bg-white border border-gray-200 rounded-lg shadow-sm p-6';

                    let permissionsHtml = '';
                    availablePermissions.forEach(permission => {
                        const isChecked = adminPermissions.includes(permission.key);
                        permissionsHtml += `
                            <div class="flex items-start mb-4">
                                <div class="flex items-center h-5">
                                    <input id="${admin.id}_${permission.key}" 
                                           type="checkbox" 
                                           value="${permission.key}" 
                                           data-admin-id="${admin.id}"
                                           ${isChecked ? 'checked' : ''}
                                           onchange="updatePermission(${admin.id}, '${permission.key}', this.checked)"
                                           class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="${admin.id}_${permission.key}" class="font-medium text-gray-900">${permission.name}</label>
                                    <p class="text-xs text-gray-500">${permission.description}</p>
                                </div>
                            </div>
                        `;
                    });

                    adminCard.innerHTML = `
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">${admin.username}</h3>
                                <p class="text-sm text-gray-500">ID: ${admin.id} | Created: ${new Date(admin.created_at).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <button onclick="toggleAdminPermissions('admin_${admin.id}_permissions')"
                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                    Toggle all permissions
                                </button>
                            </div>
                        </div>
                        <div id="admin_${admin.id}_permissions" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${permissionsHtml}
                        </div>
                    `;

                    permissionsList.appendChild(adminCard);

                    if (data.indexOf(admin) !== data.length - 1) {
                        const divider = document.createElement('hr');
                        divider.className = 'my-6 border-gray-200';
                        permissionsList.appendChild(divider);
                    }
                });
            })
            .catch(error => {
                console.error('Error loading admin permissions:', error);
                document.getElementById('permissionsList').innerHTML =
                    '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4" role="alert">' +
                    '<p>Failed to load admin permissions. Please try again.</p></div>';
            });
    }

    // Update a specific permission
    function updatePermission(adminId, permission, isGranted) {
        fetch('../api/update_permission.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                admin_id: adminId,
                permission: permission,
                granted: isGranted
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Permission Updated',
                        text: isGranted ? 'Permission granted successfully.' : 'Permission revoked successfully.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    throw new Error(data.message || 'Failed to update permission');
                }
            })
            .catch(error => {
                console.error('Error updating permission:', error);
                // Revert the checkbox state
                document.getElementById(`${adminId}_${permission}`).checked = !isGranted;

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update permission. Please try again.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
    }

    // Toggle all permissions for an admin
    function toggleAdminPermissions(containerId) {
        const container = document.getElementById(containerId);
        const checkboxes = container.querySelectorAll('input[type="checkbox"]');

        // Check if any are unchecked
        const hasUnchecked = Array.from(checkboxes).some(checkbox => !checkbox.checked);

        // If any are unchecked, check all. Otherwise, uncheck all.
        checkboxes.forEach(checkbox => {
            if (checkbox.checked !== hasUnchecked) {
                checkbox.checked = hasUnchecked;

                // Trigger the change event to update the database
                const adminId = checkbox.getAttribute('data-admin-id');
                const permission = checkbox.value;
                updatePermission(adminId, permission, hasUnchecked);
            }
        });
    }
</script>

<!-- Include SweetAlert2 via CDN if not already included -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.getElementById('logoutLink').addEventListener('click', function (event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, logout!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    });
</script>

<?php include '../components/footer.php'; ?>