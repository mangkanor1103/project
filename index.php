<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen relative">
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-extrabold mb-4 animate-fade-in-down">Welcome to the Employee Portal</h1>
            <p class="text-xl font-light mb-6 animate-fade-in">Your one-stop solution for managing attendance, payroll, and leave requests.</p>
            <a href="login.php" class="bg-white text-blue-600 font-semibold px-8 py-3 rounded-md shadow-lg hover:bg-gray-200 transition transform hover:-translate-y-1 hover:scale-105 animate-bounce-in">
                Get Started
            </a>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="container mx-auto px-4 mt-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Announcements Section -->
            <div class="bg-white shadow-lg rounded-lg p-6 hover:shadow-2xl hover:-translate-y-1 hover:scale-105 transition-transform duration-300">
                <h2 class="text-2xl font-bold text-blue-600 mb-4 flex items-center">
                    <!-- SVG Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 2v12a4 4 0 1 0 8 0V2"/>
                        <path d="M10 6h8"/>
                        <path d="M4 6h1M4 18h1M4 12h1M19 6h1M19 18h1M19 12h1"/>
                    </svg>
                    Announcements
                </h2>
                <ul class="space-y-4">
                    <li class="border-b pb-3">
                        <p class="text-gray-700"><strong>April 20, 2025:</strong> Payroll processing for April is complete. Download your payslips now!</p>
                    </li>
                    <li class="border-b pb-3">
                        <p class="text-gray-700"><strong>April 15, 2025:</strong> Team building activity scheduled for April 30th. Confirm attendance.</p>
                    </li>
                    <li>
                        <p class="text-gray-700"><strong>April 10, 2025:</strong> Tax deduction updates have been applied to your profile.</p>
                    </li>
                </ul>
            </div>

            <!-- Welcome Message Section -->
            <div class="bg-white shadow-lg rounded-lg p-6 hover:shadow-2xl hover:-translate-y-1 hover:scale-105 transition-transform duration-300">
                <h2 class="text-2xl font-bold text-blue-600 mb-4 flex items-center">
                    <!-- SVG Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 4V2M12 22v-2M3 12H2M22 12h-2M6.5 6.5l-1-1M18.5 6.5l1-1M6.5 17.5l-1 1M18.5 17.5l1 1"/>
                    </svg>
                    Welcome, Employee!
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

    <!-- Feedback Form Section -->
    <section id="feedback-form" class="container mx-auto px-4 mt-16">
        <h2 class="text-3xl font-bold text-blue-600 text-center mb-8">Submit Your Feedback</h2>
        <form action="submit_feedback.php" method="POST" class="max-w-2xl mx-auto bg-white p-8 shadow-lg rounded-lg hover:shadow-2xl transition-transform">
            <div class="mb-6">
                <label for="name" class="block text-gray-700 font-semibold mb-2">Your Name</label>
                <input type="text" id="name" name="name" class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none" required>
            </div>
            <div class="mb-6">
                <label for="email" class="block text-gray-700 font-semibold mb-2">Your Email</label>
                <input type="email" id="email" name="email" class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none" required>
            </div>
            <div class="mb-6">
                <label for="message" class="block text-gray-700 font-semibold mb-2">Your Message</label>
                <textarea id="message" name="message" rows="5" class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none" required></textarea>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:scale-105">
                Submit Feedback
            </button>
        </form>
    </section>
</main>

<?php include 'components/footer.php'; ?>