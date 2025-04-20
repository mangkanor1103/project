<!-- filepath: c:\xampp\htdocs\project\superadmin\password.php -->
<?php
session_start();
include '../config.php'; // Include database connection

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    header("Location: ../index.php");
    exit();
}

$success_message = '';
$error_message = '';
$password_updated = false;

// Handle password update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Hash the new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $admin_id = $_SESSION['admin_id'];
        $update_query = "UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $password_hash, $admin_id);
        
        if ($stmt->execute()) {
            $success_message = "Password updated successfully!";
            $password_updated = true;
            
            // We'll handle the logout via JavaScript after showing success message
        } else {
            $error_message = "Failed to update password. Please try again.";
        }
    }
}
?>

<?php include '../components/header.php'; ?>

<div class="flex min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <main class="flex-1">
        <div class="container mx-auto px-4 py-10">
            <!-- Back to Dashboard Button -->
            <a href="superadmin.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg shadow hover:bg-gray-400 transition mb-6 inline-block">
                &larr; Back to Dashboard
            </a>

            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-blue-600 px-6 py-4">
                    <h1 class="text-white text-xl font-semibold">Update Your Password</h1>
                </div>
                
                <div class="p-6">
                    <?php if (!empty($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?= $error_message ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long.</p>
                        </div>
                        
                        <div class="mb-6">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="max-w-md mx-auto mt-6">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                For security reasons, you will be logged out automatically after changing your password.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($password_updated): ?>
<script>
    // Show success message with SweetAlert2
    Swal.fire({
        title: 'Success!',
        text: 'Your password has been updated successfully. You will be logged out for security reasons.',
        icon: 'success',
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6'
    }).then((result) => {
        // When alert is closed, perform logout
        logoutUser();
    });

    function logoutUser() {
        // Create a form to POST to logout endpoint
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../logout.php';
        
        // Add a hidden field to indicate this was a password change logout
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'password_changed';
        hiddenField.value = 'true';
        form.appendChild(hiddenField);
        
        document.body.appendChild(form);
        form.submit();
    }
</script>
<?php endif; ?>

<?php include '../components/footer.php'; ?>