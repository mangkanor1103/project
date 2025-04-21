<!-- filepath: c:\xampp\htdocs\project\hr\get_department_employees.php -->
<?php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo "<p class='text-center text-red-500'>Unauthorized access</p>";
    exit();
}

// Assign employees to department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_employees'])) {
    $dept_name = trim($_POST['department_name']);
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : [];
    
    if (empty($employee_ids)) {
        $error_message = "No employees selected for assignment.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update each employee's department
            $update_sql = "UPDATE employees SET department = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            $success_count = 0;
            $reassigned_count = 0;
            $new_assignment_count = 0;
            
            foreach ($employee_ids as $emp_id) {
                // Check current department
                $check_dept_sql = "SELECT department FROM employees WHERE id = ?";
                $check_dept_stmt = $conn->prepare($check_dept_sql);
                $check_dept_stmt->bind_param("i", $emp_id);
                $check_dept_stmt->execute();
                $current_dept = $check_dept_stmt->get_result()->fetch_assoc()['department'];
                
                // Update department
                $update_stmt->bind_param("si", $dept_name, $emp_id);
                if ($update_stmt->execute()) {
                    $success_count++;
                    if (!empty($current_dept)) {
                        $reassigned_count++;
                    } else {
                        $new_assignment_count++;
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $message_parts = [];
            if ($new_assignment_count > 0) {
                $message_parts[] = "$new_assignment_count new employees assigned";
            }
            if ($reassigned_count > 0) {
                $message_parts[] = "$reassigned_count employees reassigned";
            }
            
            $success_message = implode(" and ", $message_parts) . " to '{$dept_name}' successfully!";
        } catch (Exception $e) {
            // Rollback on failure
            $conn->rollback();
            $error_message = "Failed to assign employees: " . $e->getMessage();
        }
    }
}

if (!isset($_GET['department_name']) || empty($_GET['department_name'])) {
    echo "<p class='text-center text-red-500'>Department name is required</p>";
    exit();
}

$department_name = trim($_GET['department_name']);

// Get employees in this department
$emp_sql = "SELECT id, full_name, job_position, date_hired, contact_number, email FROM employees 
            WHERE department = ? 
            ORDER BY job_position, full_name";
$emp_stmt = $conn->prepare($emp_sql);
$emp_stmt->bind_param("s", $department_name);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();

if ($emp_result->num_rows === 0) {
    echo "<p class='text-center py-4 text-gray-500'>No employees in this department</p>";
    exit();
}
?>

<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Hired</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php while ($employee = $emp_result->fetch_assoc()): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        <?php echo htmlspecialchars($employee['job_position']); ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['date_hired']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['contact_number']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['email']); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table></span>