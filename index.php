<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen relative">
    <!-- Floating Icons -->
    <div class="fixed bottom-6 right-6 space-y-4 z-50">
        <!-- Floating Icon: Admin -->
        <a href="hr/login.php" class="bg-blue-600 text-white p-4 rounded-full shadow-lg hover:bg-blue-700 transition">
            <i class="fas fa-user-tie"></i>
        </a>
        <!-- Floating Icon: Manager -->
        <a href="manager/manager.php" class="bg-red-600 text-white p-4 rounded-full shadow-lg hover:bg-red-700 transition">
            <i class="fas fa-users"></i>
        </a>
        <!-- Floating Icon: Super Admin -->
        <a href="superadmin/login.php" class="bg-gray-800 text-white p-4 rounded-full shadow-lg hover:bg-gray-900 transition">
            <i class="fas fa-user-shield"></i>
        </a>
    </div>

    <!-- Hero Section -->
    <section class="bg-blue-600 text-white py-12">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Welcome to the Employee Portal</h1>
            <p class="text-lg">Your one-stop solution for managing attendance, payroll, and leave requests.</p>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="container mx-auto px-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Announcements Section -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4 flex items-center">
                    <i class="fas fa-bullhorn text-red-600 mr-2"></i> Announcements
                </h2>
                <ul class="space-y-3">
                    <li class="border-b pb-2">
                        <p><strong>April 20, 2025:</strong> Payroll processing for April is complete. Download your payslips now!</p>
                    </li>
                    <li class="border-b pb-2">
                        <p><strong>April 15, 2025:</strong> Team building activity scheduled for April 30th. Confirm attendance.</p>
                    </li>
                    <li>
                        <p><strong>April 10, 2025:</strong> Tax deduction updates have been applied to your profile.</p>
                    </li>
                </ul>
            </div>

            <!-- Quick Links Section -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4 flex items-center">
                    <i class="fas fa-link text-red-600 mr-2"></i> Quick Links
                </h2>
                <ul class="space-y-3">
                    <li>
                        <a href="payslip.php" class="flex items-center bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition">
                            <i class="fas fa-file-invoice-dollar mr-2"></i> View Payslips
                        </a>
                    </li>
                    <li>
                        <a href="attendance.php" class="flex items-center bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition">
                            <i class="fas fa-clock mr-2"></i> Log In/Out (Attendance)
                        </a>
                    </li>
                    <li>
                        <a href="leave.php" class="flex items-center bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition">
                            <i class="fas fa-calendar-alt mr-2"></i> Request Leave
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="flex items-center bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition">
                            <i class="fas fa-user mr-2"></i> Update Profile
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Welcome Message Section -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4 flex items-center">
                    <i class="fas fa-smile text-red-600 mr-2"></i> Welcome, Employee!
                </h2>
                <p class="text-gray-700 mb-4">
                    We're glad to have you on board. Use this portal to manage your work details easily and efficiently.
                </p>
                <p class="text-gray-700">
                    If you encounter any issues, feel free to reach out to the HR team for assistance.
                </p>
            </div>
        </div>
    </section>

    <!-- Form Section -->
    <section class="container mx-auto px-4 mt-12">
        <h2 class="text-3xl font-bold text-blue-600 text-center mb-6">Submit Your Feedback</h2>
        <form action="submit_feedback.php" method="POST" class="max-w-2xl mx-auto bg-white p-6 shadow-md rounded-lg">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-bold mb-2">Your Name</label>
                <input type="text" id="name" name="name" class="w-full p-3 border rounded" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Your Email</label>
                <input type="email" id="email" name="email" class="w-full p-3 border rounded" required>
            </div>
            <div class="mb-4">
                <label for="message" class="block text-gray-700 font-bold mb-2">Your Message</label>
                <textarea id="message" name="message" rows="5" class="w-full p-3 border rounded" required></textarea>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition">
                Submit Feedback
            </button>
        </form>
    </section>
</main>

<?php include 'components/footer.php'; ?>