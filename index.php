<?php
session_start();
// Process login if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    include 'config.php'; // Database configuration

    // Retrieve email and password from POST request
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $error_message = "";

    // Validate input
    if (!empty($email) && !empty($password)) {
        // SQL to fetch user by email
        $sql = "SELECT * FROM employees WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables and redirect to dashboard
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid password. Please try again.";
            }
        } else {
            $error_message = "No user found with this email.";
        }
    } else {
        $error_message = "Please enter both email and password.";
    }

    // If we reached here, there was an error in login
    // We'll handle this with JavaScript below
}
?>

<?php include 'components/header.php'; ?>

<main class="bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen relative">
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
                <a href="admin_login.php"
                    class="bg-indigo-900 text-white border border-indigo-400 font-semibold px-8 py-3 rounded-md shadow-lg hover:bg-indigo-800 transition transform hover:-translate-y-1">
                    Admin Portal
                </a>
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
            <!-- Announcements Section -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden md:col-span-2">
                <div class="bg-indigo-700 text-white py-4 px-6">
                    <h2 class="text-xl font-bold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z"
                                clip-rule="evenodd" />
                        </svg>
                        Latest Announcements
                    </h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-4">
                        <li class="flex items-start pb-3 border-b border-gray-200">
                            <div class="flex-shrink-0 bg-blue-100 rounded-full p-1 mr-3 mt-1">
                                <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-800"><span class="font-semibold text-indigo-600">April 20,
                                        2025:</span> Payroll processing for April is complete. Download your payslips
                                    now!</p>
                            </div>
                        </li>
                        <li class="flex items-start pb-3 border-b border-gray-200">
                            <div class="flex-shrink-0 bg-purple-100 rounded-full p-1 mr-3 mt-1">
                                <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-800"><span class="font-semibold text-indigo-600">April 15,
                                        2025:</span> Team building activity scheduled for April 30th. Confirm
                                    attendance.</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 bg-amber-100 rounded-full p-1 mr-3 mt-1">
                                <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-800"><span class="font-semibold text-indigo-600">April 10,
                                        2025:</span> Tax deduction updates have been applied to your profile.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Welcome Message Section -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-indigo-700 text-white py-4 px-6">
                    <h2 class="text-xl font-bold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        Quick Info
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col space-y-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="font-medium text-blue-800 mb-1">Next Payday</h3>
                            <p class="text-blue-600">April 30, 2025</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-medium text-green-800 mb-1">Available Leave</h3>
                            <p class="text-green-600">14 days remaining</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h3 class="font-medium text-purple-800 mb-1">HR Contact</h3>
                            <p class="text-purple-600">hr@company.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

    <!-- Login Modal -->
    <div id="login-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50" id="modal-overlay"></div>

        <!-- Modal Content -->
        <div class="bg-white shadow-lg rounded-lg w-full max-w-md p-6 relative z-10 animate-fade-in-up">
            <!-- Close Button -->
            <button id="close-modal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h2 class="text-2xl font-bold text-blue-600 text-center mb-6">Employee Login</h2>

            <!-- Error message container -->
            <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 hidden"
                role="alert">
                <span class="block sm:inline" id="error-text"></span>
            </div>

            <form id="login-form" method="POST" class="space-y-6">
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email"
                        class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
                        placeholder="Enter your email" required>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password"
                        class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
                        placeholder="Enter your password" required>
                </div>

                <!-- Login Button -->
                <button type="submit"
                    class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:scale-105">
                    Login
                </button>
            </form>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>