<?php
session_start();

// Simulated credentials for HR Admin (replace with database lookup in production)
$valid_username = "hradmin";
$valid_password = "admin123"; // Use hashed passwords in a real application

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    // Check credentials
    if ($username === $valid_username && $password === $valid_password) {
        // Set session variables
        $_SESSION['hr_loggedin'] = true;
        $_SESSION['hr_username'] = $username;

        // Redirect to HR Dashboard
        header("Location: hr_dashboard.php");
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<?php include '../components/header.php'; ?>

<main class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-md rounded-lg p-6 max-w-md w-full">
        <h1 class="text-3xl font-bold text-blue-600 mb-6 text-center">HR Admin Login</h1>
        <?php if (!empty($error_message)): ?>
            <p class="text-red-600 text-center mb-4"><?= $error_message ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- Username Field -->
            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-bold mb-2">Username</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="w-full p-3 border rounded pl-10 focus:outline-none focus:border-blue-500" 
                        placeholder="Enter your username" 
                        required>
                    <div class="absolute inset-y-0 left-3 flex items-center text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-4a4 4 0 100-8 4 4 0 000 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Password Field -->
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full p-3 border rounded pl-10 focus:outline-none focus:border-blue-500" 
                        placeholder="Enter your password" 
                        required>
                    <div class="absolute inset-y-0 left-3 flex items-center text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2-10a2 2 0 11-4 0 2 2 0 014 0zm-4 4a4 4 0 108 0c0-1.105-.895-2-2-2H8c-1.105 0-2 .895-2 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Login Button -->
            <button 
                type="submit" 
                class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition w-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-10.707a1 1 0 00-1.414-1.414L9 9.586 6.707 7.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l5-5z" clip-rule="evenodd" />
                </svg>
                Login
            </button>
        </form>
        
        <!-- Back Button -->
        <a href="../index.php" class="bg-gray-600 text-white px-6 py-3 rounded hover:bg-gray-700 transition w-full flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-3.707-5.707a1 1 0 011.414-1.414L9 10.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-5-5z" clip-rule="evenodd" />
            </svg>
            Back to Home
        </a>
    </div>
</main>

<?php include '../components/footer.php'; ?>