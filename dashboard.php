<?php
session_start();
include 'config.php'; // Include database configuration

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch employee details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Check if the password is still the default password
$is_default_password = password_verify("12345678", $employee['password']);
?>

<?php include 'components/header.php'; ?>

<main class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="flex flex-col items-center mb-10">
            <!-- Profile Photo -->
            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 mb-4 border-4 border-white shadow-lg">
                <?php if (!empty($employee['image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($employee['image']); ?>"
                        class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <h1 class="text-4xl font-bold text-blue-700 mb-2">Welcome,
                <?php echo htmlspecialchars($employee['full_name']); ?>!
            </h1>
            <div class="h-1 w-24 bg-blue-600 rounded-full mb-2"></div>
            <p class="text-gray-600 text-lg">Employee Dashboard</p>
        </div>

        <!-- Warning for Default Password -->
        <?php if ($is_default_password): ?>
            <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-5 mb-8 rounded-lg shadow-sm animate-pulse"
                role="alert">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-yellow-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-bold text-lg">Security Alert</p>
                        <p>You're using the default password. Please <a href="profile.php"
                                class="text-blue-700 font-medium hover:underline">update your password</a> immediately.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Employee Details -->
        <div
            class="bg-white shadow-xl rounded-2xl p-8 mb-10 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Employee Details</h2>

            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3 bg-gray-50 p-4 rounded-lg">
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Full Name:</span> <span
                            class="text-gray-800"><?php echo htmlspecialchars($employee['full_name']); ?></span></p>
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Email:</span> <span
                            class="text-gray-800"><?php echo htmlspecialchars($employee['email']); ?></span></p>
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Contact:</span> <span
                            class="text-gray-800"><?php echo htmlspecialchars($employee['contact_number']); ?></span>
                    </p>
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Job Position:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($employee['job_position']); ?></span>
                    </p>
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Department:</span> <span
                            class="text-gray-800"><?php echo htmlspecialchars($employee['department']); ?></span></p>
                </div>
                <div class="space-y-3 bg-gray-50 p-4 rounded-lg">
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Date of Birth:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($employee['dob']); ?></span>
                    </p>
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Gender:</span> <span
                            class="text-gray-800"><?php echo htmlspecialchars($employee['gender']); ?></span></p>
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Date Hired:</span> <span
                            class="text-gray-800"><?php echo htmlspecialchars($employee['date_hired']); ?></span></p>
                    <p class="flex items-center"><span class="font-semibold text-gray-700 w-32">Status:</span>
                        <span
                            class="px-3 py-1 text-xs font-medium rounded-full <?php echo $employee['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo htmlspecialchars($employee['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Updated Action Buttons section with Expenses button -->
        <h3 class="text-xl font-bold text-gray-800 mb-4 px-2">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-10">
            <!-- Payslip Button -->
            <div class="group">
                <a href="payslip.php"
                    class="bg-gradient-to-br from-blue-500 to-blue-600 text-white px-6 py-4 rounded-xl shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center h-full group-hover:-translate-y-1 transform group-hover:shadow-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <div>
                        <span class="font-medium block">View Payslip</span>
                        <span class="text-xs text-blue-100">Salary & benefits</span>
                    </div>
                </a>
            </div>

            <!-- Attendance Button -->
            <div class="group">
                <a href="attendance.php"
                    class="bg-gradient-to-br from-green-500 to-green-600 text-white px-6 py-4 rounded-xl shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center h-full group-hover:-translate-y-1 transform group-hover:shadow-green-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <span class="font-medium block">Attendance</span>
                        <span class="text-xs text-green-100">Time & schedule</span>
                    </div>
                </a>
            </div>

            <!-- Request Leave Button -->
            <div class="group">
                <a href="leave.php"
                    class="bg-gradient-to-br from-amber-500 to-amber-600 text-white px-6 py-4 rounded-xl shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center h-full group-hover:-translate-y-1 transform group-hover:shadow-amber-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-medium block">Request Leave</span>
                        <span class="text-xs text-amber-100">Time off & vacation</span>
                    </div>
                </a>
            </div>
            
            <!-- Expense Reimbursement Button -->
            <div class="group">
                <a href="expenses.php"
                    class="bg-gradient-to-br from-teal-500 to-teal-600 text-white px-6 py-4 rounded-xl shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center h-full group-hover:-translate-y-1 transform group-hover:shadow-teal-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-medium block">Expenses</span>
                        <span class="text-xs text-teal-100">Submit reimbursements</span>
                    </div>
                </a>
            </div>

            <!-- Update Profile Button -->
            <div class="group">
                <a href="profile.php"
                    class="bg-gradient-to-br from-purple-500 to-purple-600 text-white px-6 py-4 rounded-xl shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center h-full group-hover:-translate-y-1 transform group-hover:shadow-purple-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div>
                        <span class="font-medium block">Update Profile</span>
                        <span class="text-xs text-purple-100">Personal details</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="flex justify-center">
            <a href="logout.php"
                class="group flex items-center bg-white border border-red-500 text-red-600 px-6 py-3 rounded-full hover:bg-red-600 hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-2 group-hover:rotate-180 transition-transform duration-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="font-medium">Logout</span>
            </a>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>