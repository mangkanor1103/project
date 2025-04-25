<!-- filepath: c:\xampp\htdocs\project\hr\manager.php -->
<?php
session_start();
include '../config.php';
include '../components/header.php'; // Include your header
include 'check_permission.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

requirePermission('assign_manager');

// Define available job positions
$available_positions = [
    'Staff' => 'Staff',
    'Supervisor' => 'Supervisor',
    'Team Lead' => 'Team Lead',
    'Manager' => 'Manager',
    'Senior Manager' => 'Senior Manager',
    'Director' => 'Director',
    'Executive' => 'Executive'
];

// Handle promotion to new position
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_employee'])) {
    $employee_id = intval($_POST['employee_id']);
    $new_position = $_POST['new_position'];

    // Validate that the position is in our allowed list
    if (array_key_exists($new_position, $available_positions)) {
        $update_sql = "UPDATE employees SET job_position = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_position, $employee_id);
        if ($stmt->execute()) {
            $success_message = "Employee position updated to {$new_position} successfully!";
        } else {
            $error_message = "Failed to update employee position.";
        }
    } else {
        $error_message = "Invalid position selected.";
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
            <h1 class="text-3xl font-bold text-gray-800">Update Employee Positions</h1>
            <a href="hr_dashboard.php"
                class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md shadow-md hover:bg-gray-300 transition">
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full
                            Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Current Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Department</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Update Position</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $employee['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['full_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($employee['job_position']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['department']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <form method="POST" class="flex justify-end items-center space-x-2"
                                        onsubmit="return confirm('Are you sure you want to update this employee\'s position?');">
                                        <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                        <select name="new_position"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <?php foreach ($available_positions as $position_key => $position_label): ?>
                                                <option value="<?php echo $position_key; ?>" <?php echo ($employee['job_position'] === $position_key) ? 'selected' : ''; ?>>
                                                    <?php echo $position_label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="Custom">Custom...</option>
                                        </select>
                                        <button type="submit" name="promote_employee"
                                            class="bg-blue-600 text-white px-3 py-1 rounded-md shadow-sm hover:bg-blue-700 transition text-sm">
                                            Update
                                        </button>
                                    </form>
                                    <div id="custom-position-<?php echo $employee['id']; ?>" class="hidden mt-2">
                                        <form method="POST" class="flex justify-end items-center space-x-2">
                                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                            <input type="text" name="new_position" placeholder="Enter custom position"
                                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
                                            <button type="submit" name="promote_employee"
                                                class="bg-green-600 text-white px-3 py-1 rounded-md shadow-sm hover:bg-green-700 transition text-sm">
                                                Apply
                                            </button>
                                        </form>
                                    </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Add event listeners to all position selects
        const selects = document.querySelectorAll('select[name="new_position"]');
        selects.forEach(select => {
            select.addEventListener('change', function () {
                const employeeId = this.closest('form').querySelector('input[name="employee_id"]').value;
                const customDiv = document.getElementById('custom-position-' + employeeId);

                if (this.value === 'Custom') {
                    customDiv.classList.remove('hidden');
                } else {
                    customDiv.classList.add('hidden');
                }
            });
        });
    });
</script>

<?php include '../components/footer.php'; ?>