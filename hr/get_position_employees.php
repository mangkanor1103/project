<?php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo "<p class='text-center text-red-500'>Unauthorized access</p>";
    exit();
}

if (!isset($_GET['position_name']) || empty($_GET['position_name'])) {
    echo "<p class='text-center text-red-500'>Position name is required</p>";
    exit();
}

$position_name = trim($_GET['position_name']);

// Get employees in this position
$emp_sql = "SELECT id, full_name, department, date_hired, contact_number, email FROM employees 
            WHERE job_position = ? 
            ORDER BY department, full_name";
$emp_stmt = $conn->prepare($emp_sql);
$emp_stmt->bind_param("s", $position_name);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();

if ($emp_result->num_rows === 0) {
    echo "<p class='text-center py-4 text-gray-500'>No employees in this job position</p>";
    exit();
}
?>

<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Hired</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php while ($employee = $emp_result->fetch_assoc()): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo htmlspecialchars($employee['full_name']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <?php if (!empty($employee['department'])): ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            <?php echo htmlspecialchars($employee['department']); ?>
                        </span>
                    <?php else: ?>
                        <span class="text-gray-500 italic">(Unassigned)</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['date_hired']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['contact_number']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['email']); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>