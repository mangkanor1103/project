<!-- filepath: c:\xampp\htdocs\project\hr\manager.php -->
<?php
session_start();
include '../config.php';
include '../components/header.php'; // Include your header

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Handle promotion to manager
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_to_manager'])) {
    $employee_id = intval($_POST['employee_id']);
    $update_sql = "UPDATE employees SET job_position = 'Manager' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $employee_id);
    if ($stmt->execute()) {
        $success_message = "Employee promoted to Manager successfully!";
    } else {
        $error_message = "Failed to promote employee.";
    }
}

// Fetch employees
$employees = [];
$sql = "SELECT id, full_name, email, job_position, department FROM employees ORDER BY department ASC, job_position ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $employees = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="bg-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Promote Employee to Manager</h1>
            <a href="hr_dashboard.php"
                class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md shadow-md hover:bg-gray-300 transition">
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <p class="text-green-600 mb-4"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <p class="text-red-600 mb-4"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $employee['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['job_position']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['department']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <?php if ($employee['job_position'] !== 'Manager'): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to promote this employee to Manager?');">
                                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                            <button type="submit" name="promote_to_manager"
                                                class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                                                Promote to Manager
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-green-600 font-semibold">Already a Manager</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>