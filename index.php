<?php
session_start();
include 'config.php'; // Database configuration

$employee_error_message = "";
$admin_error_message = "";

// Fetch announcements from the database
$announcements = [];
$sql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $announcements = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch events from the database
$events = [];
$sql = "SELECT * FROM calendar_events ORDER BY date ASC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch total employees
$total_employees = 0;
$sql = "SELECT COUNT(*) AS total FROM employees";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_employees = $row['total'];
}

// Handle Employee Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password']) && !isset($_POST['admin_login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Fetch employee by email
        $sql = "SELECT * FROM employees WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables and redirect to employee dashboard
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                header("Location: dashboard.php");
                exit();
            } else {
                $employee_error_message = "Invalid password. Please try again.";
            }
        } else {
            $employee_error_message = "No user found with this email.";
        }
    } else {
        $employee_error_message = "Please enter both email and password.";
    }
}

// Handle Admin Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['admin_login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // First, check the admins table
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Set session variables
                session_regenerate_id(true);
                $_SESSION['admin_loggedin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role']; // Assuming 'role' column exists in the 'admins' table

                // Redirect based on role
                if ($admin['role'] === 'superadmin') {
                    header("Location: superadmin/superadmin.php");
                } elseif ($admin['role'] === 'admin') {
                    header("Location: hr/hr_dashboard.php");
                } else {
                    $admin_error_message = "Unauthorized role.";
                }
                exit();
            } else {
                $admin_error_message = "Invalid password.";
            }
        } else {
            // If not found in admins table, check the employees table for managers
            $sql = "SELECT * FROM employees WHERE email = ? AND job_position = 'Manager'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $manager = $result->fetch_assoc();

                // Verify password
                if (password_verify($password, $manager['password'])) {
                    // Set session variables
                    session_regenerate_id(true);
                    $_SESSION['admin_loggedin'] = true;
                    $_SESSION['admin_id'] = $manager['id'];
                    $_SESSION['admin_username'] = $manager['full_name'];
                    $_SESSION['admin_role'] = $manager['job_position'];

                    // Redirect to manager dashboard
                    header("Location: manager/dash.php");
                    exit();
                } else {
                    $admin_error_message = "Invalid password.";
                }
            } else {
                $admin_error_message = "Admin not found or not authorized.";
            }
        }
    } else {
        $admin_error_message = "Please enter both username and password.";
    }
}
 include 'components/header.php'; ?>

<main class="bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen relative">

    <!-- Login Modals -->
    <div id="login-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50" onclick="closeModal('login-modal')"></div>
        <div class="bg-white shadow-lg rounded-lg w-full max-w-md p-6 relative">
            <h2 class="text-2xl font-bold text-blue-600 text-center mb-6">Employee Login</h2>
            <?php if (!empty($employee_error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <span><?php echo $employee_error_message; ?></span>
                </div>
            <?php endif; ?>
            <form method="POST" onsubmit="return validateForm('login-modal')">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" class="w-full mt-1 p-3 border rounded-lg" required>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" class="w-full mt-1 p-3 border rounded-lg" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 mt-4 rounded-lg">Login</button>
            </form>
        </div>
    </div>

    <div id="admin-login-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50" onclick="closeModal('admin-login-modal')"></div>
        <div class="bg-white shadow-lg rounded-lg w-full max-w-md p-6 relative">
            <h2 class="text-2xl font-bold text-indigo-600 text-center mb-6">Admin Login</h2>
            <?php if (!empty($admin_error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <span><?php echo $admin_error_message; ?></span>
                </div>
            <?php endif; ?>
            <form method="POST" onsubmit="return validateForm('admin-login-modal')">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" name="username" class="w-full mt-1 p-3 border rounded-lg" required>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" class="w-full mt-1 p-3 border rounded-lg" required>
                </div>
                <input type="hidden" name="admin_login" value="1">
                <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 mt-4 rounded-lg">Login</button>
            </form>
        </div>
    </div>

    <script>
        // Open modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Validate form and close modal if fields are empty
        function validateForm(modalId) {
            const modal = document.getElementById(modalId);
            const inputs = modal.querySelectorAll('input[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                }
            });

            if (!isValid) {
                closeModal(modalId);
            }

            return isValid;
        }

        // Event listeners for buttons
        document.getElementById('employee-login-btn').addEventListener('click', () => openModal('login-modal'));
        document.getElementById('admin-login-btn').addEventListener('click', () => openModal('admin-login-modal'));
    </script>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0"
                style="background-image: url('assets/img/pattern.svg'); background-size: 300px;"></div>
        </div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-5xl font-extrabold mb-4 animate-fade-in-down">Payroll Management System</h1>
            <p class="text-xl font-light mb-8 max-w-2xl mx-auto animate-fade-in">Simplify your workforce management with
                our comprehensive payroll solution.</p>
            <div class="flex justify-center gap-4">
                <button id="employee-login-btn"
                    class="bg-white text-indigo-700 font-semibold px-8 py-3 rounded-md shadow-lg hover:bg-gray-100 transition transform hover:-translate-y-1 hover:scale-105 animate-bounce-in">
                    Employee Login
                </button>
                <button id="admin-login-btn"
    class="bg-indigo-900 text-white border border-indigo-400 font-semibold px-8 py-3 rounded-md shadow-lg hover:bg-indigo-800 transition transform hover:-translate-y-1">
    Admin Portal
</button>
            </div>
        </div>
    </section>

    <!-- Quick Access Cards -->
    <section class="container mx-auto px-4 -mt-10 relative z-20">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6 border-t-4 border-blue-500 hover:shadow-xl transition">
                <div class="flex items-center mb-3">
                    <div class="p-3 bg-blue-100 rounded-full mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Payslips</h3>
                </div>
                <p class="text-gray-600 text-sm">Access and download your monthly payslips</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 border-t-4 border-green-500 hover:shadow-xl transition">
                <div class="flex items-center mb-3">
                    <div class="p-3 bg-green-100 rounded-full mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Attendance</h3>
                </div>
                <p class="text-gray-600 text-sm">Track your time and manage attendance records</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 border-t-4 border-purple-500 hover:shadow-xl transition">
                <div class="flex items-center mb-3">
                    <div class="p-3 bg-purple-100 rounded-full mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Tax Documents</h3>
                </div>
                <p class="text-gray-600 text-sm">View and download your tax-related documents</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 border-t-4 border-amber-500 hover:shadow-xl transition">
                <div class="flex items-center mb-3">
                    <div class="p-3 bg-amber-100 rounded-full mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Leave Requests</h3>
                </div>
                <p class="text-gray-600 text-sm">Submit and track your leave applications</p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="container mx-auto px-4 mt-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Announcements & Events Section -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden md:col-span-2">
                <div class="bg-indigo-700 text-white py-4 px-6">
                    <h2 class="text-xl font-bold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd" />
                        </svg>
                        Announcements & Events
                    </h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-4">
                        <!-- Announcements -->
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <li class="flex items-start pb-3 border-b border-gray-200">
                                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-1 mr-3 mt-1">
                                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-gray-800">
                                            <span class="font-semibold text-indigo-600"><?php echo date('F d, Y', strtotime($announcement['created_at'])); ?>:</span>
                                            <?php echo htmlspecialchars($announcement['content']); ?>
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="text-gray-500">No announcements available.</li>
                        <?php endif; ?>

                        <!-- Events -->
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <li class="flex items-start pb-3 border-b border-gray-200">
                                    <div class="flex-shrink-0 bg-teal-100 rounded-full p-1 mr-3 mt-1">
                                        <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-gray-800">
                                            <span class="font-semibold text-teal-600"><?php echo date('F d, Y', strtotime($event['date'])); ?>:</span>
                                            <?php echo htmlspecialchars($event['event_name']); ?> (<?php echo htmlspecialchars($event['event_type']); ?>)
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="text-gray-500">No upcoming events.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Quick Info Section -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-gray-700 text-white py-4 px-6">
                    <h2 class="text-xl font-bold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd" />
                        </svg>
                        Quick Info
                    </h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-4">
                        <li class="text-gray-800">
                            <span class="font-semibold">Today's Date:</span> <?php echo date('F d, Y'); ?>
                        </li>
                        <li class="text-gray-800">
                            <span class="font-semibold">Current Time:</span> <span id="current-time"></span>
                        </li>
                        <li class="text-gray-800">
                            <span class="font-semibold">Upcoming Holiday:</span> Labor Day (May 1, 2025)
                        </li>
                        <li class="text-gray-800">
                            <span class="font-semibold">Total Employees:</span> <?php echo $total_employees; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Update current time dynamically
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('current-time').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>

    <!-- Feedback Form Section -->
    <section id="feedback-form" class="container mx-auto px-4 mt-16 mb-16">
        <h2 class="text-3xl font-bold text-indigo-800 text-center mb-2">Submit Your Feedback</h2>
        <p class="text-gray-600 text-center mb-8 max-w-2xl mx-auto">Help us improve our payroll system by sharing your
            experience and suggestions</p>
        <form action="submit_feedback.php" method="POST"
            class="max-w-2xl mx-auto bg-white p-8 shadow-lg rounded-lg hover:shadow-2xl transition-transform border border-gray-200">
            <div class="mb-6">
                <label for="name" class="block text-gray-700 font-semibold mb-2">Your Name</label>
                <input type="text" id="name" name="name"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                    required>
            </div>
            <div class="mb-6">
                <label for="email" class="block text-gray-700 font-semibold mb-2">Your Email</label>
                <input type="email" id="email" name="email"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                    required>
            </div>
            <div class="mb-6">
                <label for="message" class="block text-gray-700 font-semibold mb-2">Your Message</label>
                <textarea id="message" name="message" rows="5"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                    required></textarea>
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition transform hover:-translate-y-1">
                Submit Feedback
            </button>
        </form>
    </section>

</main>

<?php include 'components/footer.php'; ?>