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

<main class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-8">Welcome, <?php echo htmlspecialchars($employee['full_name']); ?>!</h1>

        <!-- Warning for Default Password -->
        <?php if ($is_default_password): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg" role="alert">
                <p class="font-bold">Important:</p>
                <p>You are still using the default password. Please <a href="profile.php" class="text-blue-600 underline">update your password</a> immediately to secure your account.</p>
            </div>
        <?php endif; ?>

        <!-- Employee Details -->
        <div class="bg-white shadow-lg rounded-lg p-6 max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-blue-600 mb-4">Employee Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($employee['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email']); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($employee['contact_number']); ?></p>
                    <p><strong>Job Position:</strong> <?php echo htmlspecialchars($employee['job_position']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
                </div>
                <div>
                    <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($employee['dob']); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($employee['gender']); ?></p>
                    <p><strong>Date Hired:</strong> <?php echo htmlspecialchars($employee['date_hired']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($employee['status']); ?></p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Payslip Button -->
            <a href="payslip.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-700 hover:shadow-lg transition transform hover:-translate-y-1 hover:scale-105 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M10 14h10M10 18h10M4 14h4m-4 4h4" />
                </svg>
                View Payslip
            </a>

            <!-- Attendance Button -->
            <a href="attendance.php" class="bg-green-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-green-700 hover:shadow-lg transition transform hover:-translate-y-1 hover:scale-105 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l4-4m0 0l4-4m-4 4v8m0-12V4m-8 8h16" />
                </svg>
                Attendance
            </a>

            <!-- Request Leave Button -->
            <a href="leave.php" class="bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-md hover:bg-yellow-600 hover:shadow-lg transition transform hover:-translate-y-1 hover:scale-105 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 12H5m2 0h2m6-8V4m0 12h2m-2-4h2m-2 4l-2-2m0 0h2" />
                </svg>
                Request Leave
            </a>

            <!-- Update Profile Button -->
            <a href="profile.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-purple-700 hover:shadow-lg transition transform hover:-translate-y-1 hover:scale-105 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7M12 3v18m0-18v18" />
                </svg>
                Update Profile
            </a>
        </div>

        <!-- Logout Button -->
        <div class="flex justify-center mt-8">
            <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition transform hover:-translate-y-1 hover:scale-105">
                Logout
            </a>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>