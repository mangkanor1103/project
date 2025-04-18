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
?>

<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-8">Welcome, <?php echo htmlspecialchars($employee['full_name']); ?>!</h1>

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

        <!-- Logout Button -->
        <div class="flex justify-center mt-8">
            <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition transform hover:-translate-y-1 hover:scale-105">
                Logout
            </a>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>