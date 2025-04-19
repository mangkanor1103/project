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

// Initialize variables for messages
$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $home_address = trim($_POST['home_address']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Handle profile photo upload
    $image_name = $employee['image']; // Default to current image

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Validate file type
        if (in_array($file_ext, $allowed)) {
            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_filename;

            // Try to move the uploaded file
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $image_name = $new_filename;

                // Remove old photo if exists and not the default
                if (!empty($employee['image']) && $employee['image'] != 'default.png' && file_exists('uploads/' . $employee['image'])) {
                    unlink('uploads/' . $employee['image']);
                }
            } else {
                $error_message = "Failed to upload image. Please try again.";
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG and GIF files are allowed.";
        }
    }

    // Update query (including image and excluding password if not provided)
    if (empty($error_message)) {
        if (!empty($password) && $password === $confirm_password) {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "UPDATE employees SET full_name = ?, contact_number = ?, email = ?, home_address = ?, password = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $full_name, $contact_number, $email, $home_address, $hashed_password, $image_name, $user_id);
        } elseif (empty($password)) {
            $sql = "UPDATE employees SET full_name = ?, contact_number = ?, email = ?, home_address = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $full_name, $contact_number, $email, $home_address, $image_name, $user_id);
        } else {
            $error_message = "Passwords do not match.";
        }

        if (empty($error_message) && $stmt->execute()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = $stmt->error ?: "An error occurred while updating your profile.";
        }
    }
}
?>

<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-8">Update Profile</h1>
    </div>

    <!-- SweetAlert2 Messages -->
    <?php if (!empty($success_message)): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?php echo $success_message; ?>',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "dashboard.php";
            });
        </script>
    <?php elseif (!empty($error_message)): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo $error_message; ?>',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>

    <!-- Update Profile Form -->
    <div class="container mx-auto px-4 py-8">
        <form action="profile.php" method="POST" enctype="multipart/form-data"
            class="bg-white shadow-lg rounded-lg p-6 max-w-4xl mx-auto space-y-6">
            <!-- Profile Photo -->
            <div class="flex flex-col items-center mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-4">Profile Photo</label>
                <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-200 mb-4">
                    <?php if (!empty($employee['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($employee['image']); ?>"
                            class="w-full h-full object-cover"
                            alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <input type="file" id="profile_photo" name="profile_photo"
                    class="w-full max-w-xs mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                <p class="text-sm text-gray-500 mt-1">JPG, PNG, or GIF. Max 2MB.</p>
            </div>

            <!-- Full Name -->
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                    value="<?php echo htmlspecialchars($employee['full_name']); ?>"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
                    required>
            </div>

            <!-- Contact Number -->
            <div>
                <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number"
                    value="<?php echo htmlspecialchars($employee['contact_number']); ?>"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
                    required>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
                    required>
            </div>

            <!-- Home Address -->
            <div>
                <label for="home_address" class="block text-sm font-medium text-gray-700">Home Address</label>
                <textarea id="home_address" name="home_address" rows="3"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
                    required><?php echo htmlspecialchars($employee['home_address']); ?></textarea>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New Password (optional)</label>
                <input type="password" id="password" name="password"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New
                    Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
            </div>

            <!-- Submit and Cancel Buttons -->
            <div class="flex justify-center space-x-4">
                <a href="dashboard.php"
                    class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition transform hover:-translate-y-1 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:shadow-lg flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</main>

<?php include 'components/footer.php'; ?>