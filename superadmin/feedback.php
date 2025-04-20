<!-- filepath: c:\xampp\htdocs\project\superadmin\feedback.php -->
<?php
session_start();
include '../config.php'; // Include database connection

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    header("Location: ../index.php");
    exit();
}

// Fetch all feedback
$sql = "SELECT * FROM feedback ORDER BY created_at DESC";
$result = $conn->query($sql);
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../components/header.php'; ?>

<div class="container mx-auto px-4 py-10">
    <!-- Back to Dashboard Button -->
    <a href="superadmin.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg shadow hover:bg-gray-400 transition mb-6 inline-block">
        &larr; Back to Dashboard
    </a>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">User Feedback</h1>

    <!-- Feedback Table -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Feedback List</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($feedbacks)): ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $feedback['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($feedback['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($feedback['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($feedback['message']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $feedback['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No feedback found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../components/footer.php'; ?>