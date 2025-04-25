<?php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Get admin permissions
$admin_id = $_SESSION['admin_id'];
$permissions = [];

$permissions_sql = "SELECT permission FROM admin_permissions WHERE admin_id = ?";
$stmt = $conn->prepare($permissions_sql);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$permissions_result = $stmt->get_result();

while ($row = $permissions_result->fetch_assoc()) {
    $permissions[] = $row['permission'];
}

// If superadmin or no permissions are set, allow all permissions
$allow_all = ($_SESSION['admin_role'] === 'superadmin' || empty($permissions));

// Define all modules and their permission keys
$modules = [
    [
        'name' => 'Create Employee',
        'permission' => 'create_employee',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>',
        'description' => 'Add new employees to the system with all their details and credentials.',
        'link' => 'create_employee.php',
        'color' => 'blue'
    ],
    [
        'name' => 'Assign Manager',
        'permission' => 'assign_manager',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>',
        'description' => 'Promote qualified employees to department managers to enhance workflow.',
        'link' => 'manager.php',
        'color' => 'purple'
    ],
    [
        'name' => 'Leave Requests',
        'permission' => 'leave_approval',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
        'description' => 'Review and manage employee leave requests for proper attendance tracking.',
        'link' => 'approval.php',
        'color' => 'green'
    ],
    [
        'name' => 'Manage Announcements',
        'permission' => 'manage_announcements',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'description' => 'Create and manage announcements for employees to view on the portal.',
        'link' => 'announcements.php',
        'color' => 'indigo'
    ],
    [
        'name' => 'Manage Calendar',
        'permission' => 'manage_calendar',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
        'description' => 'Add and manage holidays and events for employees to view on the calendar.',
        'link' => 'calendar.php',
        'color' => 'teal'
    ],
    [
        'name' => 'Expense Approval',
        'permission' => 'expense_approval',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'description' => 'Review and approve employee expense reimbursement requests.',
        'link' => 'expenses.php',
        'color' => 'teal'
    ],
    [
        'name' => 'View Employees',
        'permission' => 'view_employees',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg>',
        'description' => 'View the complete list of employees and their details.',
        'link' => 'employee_list.php',
        'color' => 'gray'
    ],
    [
        'name' => 'Manage Payslips',
        'permission' => 'manage_payslips',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2z" /></svg>',
        'description' => 'Generate and manage employee payslips for payroll processing.',
        'link' => 'payslip.php',
        'color' => 'red'
    ],
    [
        'name' => 'Manage Departments',
        'permission' => 'manage_departments',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
        'description' => 'Add, update, and delete departments in the organization.',
        'link' => 'department.php',
        'color' => 'yellow'
    ],
    [
        'name' => 'Attendance Management',
        'permission' => 'manage_attendance',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
        'description' => 'Manage employee attendance records, including adding, editing, and generating reports.',
        'link' => 'attendance.php',
        'color' => 'blue'
    ],
    [
        'name' => 'Manage Job Positions',
        'permission' => 'manage_job_positions',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>',
        'description' => 'Add, update, and manage job positions and employee role assignments.',
        'link' => 'job_position.php',
        'color' => 'indigo'
    ],
    [
        'name' => 'Employee Work Preferences',
        'permission' => 'manage_work_preferences',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM12 18v-6m4 0l-4-4-4 4" /></svg>',
        'description' => 'Manage employee work day preferences and payroll schedules for the organization.',
        'link' => 'days.php',
        'color' => 'pink'
    ],

];

// Get stats for the dashboard
// Get employee count
$employee_count = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];

// Get pending leave requests count
$pending_leaves = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'Pending'")->fetch_assoc()['count'];

// Get departments count
$departments = $conn->query("SELECT COUNT(DISTINCT department) as count FROM employees")->fetch_assoc()['count'];

// Get today's absent count (explicitly marked as absent + no records)
$today = date('Y-m-d');
$marked_absent = $conn->query("SELECT COUNT(*) as count FROM attendance 
                              WHERE date = '$today' AND is_absent = 1")->fetch_assoc()['count'];

// Get employees without attendance records for today (considered absent)
$no_record_query = "SELECT COUNT(e.id) as count 
                    FROM employees e 
                    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = '$today'
                    LEFT JOIN leave_requests lr ON e.id = lr.employee_id 
                       AND '$today' BETWEEN lr.start_date AND lr.end_date 
                       AND lr.status = 'Approved'
                    WHERE a.id IS NULL AND lr.id IS NULL";
$no_record_count = $conn->query($no_record_query)->fetch_assoc()['count'];

// Total absent count
$absent_count = $marked_absent + $no_record_count;
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
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

            <!-- Absent Today -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Absent Today</h3>
                        <p class="text-3xl font-bold text-red-600"><?php echo $absent_count; ?></p>
                        <p class="text-xs text-gray-500">
                            <?php echo round(($absent_count / $employee_count) * 100, 1); ?>% of employees
                        </p>
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
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 a1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
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
            <?php foreach ($modules as $module): ?>
                <?php
                // Show the module if admin has permission or all modules are allowed
                if ($allow_all || in_array($module['permission'], $permissions)):
                    ?>
                    <div
                        class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                        <div
                            class="bg-gradient-to-r from-<?php echo $module['color']; ?>-600 to-<?php echo $module['color']; ?>-700 px-6 py-4">
                            <h2 class="text-xl font-bold text-white"><?php echo $module['name']; ?></h2>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600 mb-6"><?php echo $module['description']; ?></p>
                            <div class="flex justify-between items-center">
                                <div class="text-<?php echo $module['color']; ?>-600">
                                    <?php echo $module['icon']; ?>
                                </div>
                                <a href="<?php echo $module['link']; ?>"
                                    class="bg-gradient-to-r from-<?php echo $module['color']; ?>-600 to-<?php echo $module['color']; ?>-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-<?php echo $module['color']; ?>-700 hover:to-<?php echo $module['color']; ?>-800 transition-all flex items-center">
                                    <span>View <?php echo $module['name']; ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (empty($permissions) && $_SESSION['admin_role'] !== 'superadmin'): ?>
                <div class="col-span-3 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                No permissions have been assigned to your account. Contact the superadmin to request access.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>