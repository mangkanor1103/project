<?php
// filepath: c:\xampp\htdocs\project\hr\days.php
session_start();
include '../config.php';

// Check if user is logged in as HR admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Fetch all employees and their preferences
$employee_sql = "SELECT e.id, e.full_name, e.job_position, e.department, e.basic_salary,
                 COALESCE(ep.work_days_per_month, 22) AS work_days_per_month,
                 COALESCE(ep.payment_frequency, 'Semi-Monthly') AS payment_frequency,
                 COALESCE(ep.pay_day_1, 15) AS pay_day_1,
                 COALESCE(ep.pay_day_2, 30) AS pay_day_2,
                 ep.weekend_workday
                 FROM employees e
                 LEFT JOIN employee_preferences ep ON e.id = ep.employee_id
                 ORDER BY e.department, e.full_name";
$employee_result = $conn->query($employee_sql);
$employees = [];

while ($row = $employee_result->fetch_assoc()) {
    $employees[] = $row;
}

// Handle form submission for updating employee preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle individual employee update
    if (isset($_POST['update_preferences'])) {
        $employee_id = $_POST['employee_id'];
        $work_days = $_POST['work_days'];
        $payment_frequency = $_POST['payment_frequency'];
        $pay_day_1 = $_POST['pay_day_1'];
        $pay_day_2 = ($payment_frequency === 'Semi-Monthly') ? $_POST['pay_day_2'] : null;
        $weekend_workday = ($work_days == 26) ? $_POST['weekend_workday'] : null;

        // Check if preferences already exist for this employee
        $check_sql = "SELECT employee_id FROM employee_preferences WHERE employee_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $employee_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing preferences
            $update_sql = "UPDATE employee_preferences 
                          SET work_days_per_month = ?,
                              payment_frequency = ?,
                              pay_day_1 = ?,
                              pay_day_2 = ?,
                              weekend_workday = ?
                          WHERE employee_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("issisi", $work_days, $payment_frequency, $pay_day_1, $pay_day_2, $weekend_workday, $employee_id);

            if ($update_stmt->execute()) {
                $success_message = "Work preferences updated successfully for employee #$employee_id.";
            } else {
                $error_message = "Error updating preferences: " . $conn->error;
            }
        } else {
            // Insert new preferences
            $insert_sql = "INSERT INTO employee_preferences 
                          (employee_id, work_days_per_month, payment_frequency, pay_day_1, pay_day_2, weekend_workday)
                          VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iissis", $employee_id, $work_days, $payment_frequency, $pay_day_1, $pay_day_2, $weekend_workday);

            if ($insert_stmt->execute()) {
                $success_message = "Work preferences set successfully for employee #$employee_id.";
            } else {
                $error_message = "Error setting preferences: " . $conn->error;
            }
        }
    }
    // Handle bulk update
    else if (isset($_POST['bulk_update'])) {
        $department = $_POST['department'];
        $work_days = $_POST['bulk_work_days'];
        $payment_frequency = $_POST['bulk_payment_frequency'];
        $pay_day_1 = $_POST['bulk_pay_day_1'];
        $pay_day_2 = ($payment_frequency === 'Semi-Monthly') ? $_POST['bulk_pay_day_2'] : null;
        $weekend_workday = ($work_days == 26) ? $_POST['bulk_weekend_workday'] : null;
        
        // Get all employees from the selected department
        $dept_employees_sql = "SELECT id FROM employees WHERE department = ?";
        $dept_employees_stmt = $conn->prepare($dept_employees_sql);
        $dept_employees_stmt->bind_param("s", $department);
        $dept_employees_stmt->execute();
        $dept_employees_result = $dept_employees_stmt->get_result();
        
        $updated_count = 0;
        $inserted_count = 0;
        
        while ($employee = $dept_employees_result->fetch_assoc()) {
            $employee_id = $employee['id'];
            
            // Check if preferences exist for this employee
            $check_sql = "SELECT employee_id FROM employee_preferences WHERE employee_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $employee_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing preferences
                $update_sql = "UPDATE employee_preferences 
                              SET work_days_per_month = ?,
                                  payment_frequency = ?,
                                  pay_day_1 = ?,
                                  pay_day_2 = ?,
                                  weekend_workday = ?
                              WHERE employee_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("issisi", $work_days, $payment_frequency, $pay_day_1, $pay_day_2, $weekend_workday, $employee_id);
                
                if ($update_stmt->execute()) {
                    $updated_count++;
                }
            } else {
                // Insert new preferences
                $insert_sql = "INSERT INTO employee_preferences 
                              (employee_id, work_days_per_month, payment_frequency, pay_day_1, pay_day_2, weekend_workday)
                              VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iissis", $employee_id, $work_days, $payment_frequency, $pay_day_1, $pay_day_2, $weekend_workday);
                
                if ($insert_stmt->execute()) {
                    $inserted_count++;
                }
            }
        }
        
        $total_affected = $updated_count + $inserted_count;
        if ($total_affected > 0) {
            $success_message = "Bulk update successful! Updated preferences for $updated_count employees and created preferences for $inserted_count employees in the $department department.";
        } else {
            $error_message = "No employees were updated in the $department department.";
        }
    }
}

// Fetch unique departments for bulk update
$departments_sql = "SELECT DISTINCT department FROM employees ORDER BY department";
$departments_result = $conn->query($departments_sql);
$departments = [];

while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row['department'];
}

// Fetch all employee preferences for display
$preferences_sql = "SELECT ep.*, e.full_name, e.job_position, e.department, e.basic_salary 
                   FROM employee_preferences ep
                   JOIN employees e ON ep.employee_id = e.id
                   ORDER BY e.department, e.full_name";
$preferences_result = $conn->query($preferences_sql);
$preferences = [];

while ($row = $preferences_result->fetch_assoc()) {
    $preferences[$row['employee_id']] = $row;
}
?>

<?php include '../components/header.php'; ?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Back to Dashboard Button -->
        <div class="mb-6">
            <a href="../hr/hr_dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>
        
        <div class="flex flex-col items-center mb-8">
            <h1 class="text-3xl font-bold text-blue-700 mb-2">Employee Work Schedule & Payment Preferences</h1>
            <p class="text-gray-600">Set employee work days and payroll frequency preferences</p>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">Success!</p>
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Error!</p>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Individual Employee Preference Form -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Set Individual Preferences</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="employee_id">
                                Employee
                            </label>
                            <select name="employee_id" id="employee_id" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Employee...</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['full_name']); ?> - 
                                    <?php echo htmlspecialchars($employee['job_position']); ?> (<?php echo htmlspecialchars($employee['department']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Work Days Per Month
                            </label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="work_days" value="22" class="form-radio text-blue-600" checked>
                                    <span class="ml-2">22 Days</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="work_days" value="26" class="form-radio text-blue-600">
                                    <span class="ml-2">26 Days</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4 hidden" id="weekend-workday-container">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Weekend Work Day
                            </label>
                            <select name="weekend_workday" id="weekend_workday" 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                                <option value="Both">Both Saturday & Sunday</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Payment Frequency
                            </label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="payment_frequency" value="Monthly" class="form-radio text-blue-600" checked>
                                    <span class="ml-2">Monthly</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="payment_frequency" value="Semi-Monthly" class="form-radio text-blue-600">
                                    <span class="ml-2">Semi-Monthly</span>
                                </label>
                            </div>
                        </div>
                        
                        <div id="individual-pay-days" class="mb-6">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="pay_day_1">
                                    Pay Day 1 (Day of Month)
                                </label>
                                <input type="number" name="pay_day_1" id="pay_day_1" min="1" max="31" value="15"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div id="individual-pay-day-2-container" class="mb-4 hidden">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="pay_day_2">
                                    Pay Day 2 (Day of Month)
                                </label>
                                <input type="number" name="pay_day_2" id="pay_day_2" min="1" max="31" value="30"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <button type="submit" name="update_preferences" value="1"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                                Update Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bulk Update Form -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Bulk Update by Department</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="department">
                                Department
                            </label>
                            <select name="department" id="department" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Department...</option>
                                <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department); ?>">
                                    <?php echo htmlspecialchars($department); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        Work Days Per Month
                                    </label>
                                    <div class="flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="bulk_work_days" value="22" class="form-radio text-blue-600" checked>
                                            <span class="ml-2">22 Days</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="bulk_work_days" value="26" class="form-radio text-blue-600">
                                            <span class="ml-2">26 Days</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-4 hidden" id="bulk-weekend-workday-container">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        Weekend Work Day
                                    </label>
                                    <select name="bulk_weekend_workday" id="bulk_weekend_workday" 
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                        <option value="Both">Both Saturday & Sunday</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        Payment Frequency
                                    </label>
                                    <div class="flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="bulk_payment_frequency" value="Monthly" class="form-radio text-blue-600" checked>
                                            <span class="ml-2">Monthly</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="bulk_payment_frequency" value="Semi-Monthly" class="form-radio text-blue-600">
                                            <span class="ml-2">Semi-Monthly</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="bulk-pay-days" class="mb-6">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="bulk_pay_day_1">
                                            Pay Day 1 (Day of Month)
                                        </label>
                                        <input type="number" name="bulk_pay_day_1" id="bulk_pay_day_1" min="1" max="31" value="15"
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    
                                    <div id="bulk-pay-day-2-container" class="mb-4 hidden">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="bulk_pay_day_2">
                                            Pay Day 2 (Day of Month)
                                        </label>
                                        <input type="number" name="bulk_pay_day_2" id="bulk_pay_day_2" min="1" max="31" value="30"
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <button type="submit" name="bulk_update" value="1"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                                Update Department Preferences
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Employee Preferences List -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800">Employee Work Preferences</h2>
                        <p class="text-gray-600 mt-1">Current work days and payment settings</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Department
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Work Days
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Weekend Day
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Payment
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Daily Rate
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($employees as $employee): 
                                    $hasPreference = isset($preferences[$employee['id']]);
                                    $workDays = $employee['work_days_per_month'];
                                    $paymentFreq = $employee['payment_frequency'];
                                    $weekendDay = $employee['weekend_workday'] ?? 'N/A';
                                    $dailyRate = $employee['basic_salary'] / $workDays;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($employee['full_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($employee['job_position']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($employee['department']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $workDays; ?> days/month</div>
                                        <?php if ($hasPreference): ?>
                                        <div class="text-xs text-gray-500">
                                            <?php if ($workDays == 22): ?>
                                            Mon-Fri
                                            <?php else: ?>
                                            Mon-Fri + <?php echo $weekendDay; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo ($workDays == 26) ? $weekendDay : 'N/A'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $paymentFreq; ?></div>
                                        <?php if ($paymentFreq == 'Semi-Monthly'): ?>
                                        <div class="text-xs text-gray-500">
                                            Days: <?php echo $employee['pay_day_1']; ?> & <?php echo $employee['pay_day_2']; ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-xs text-gray-500">
                                            Day: <?php echo $employee['pay_day_1']; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₱<?php echo number_format($dailyRate, 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($employees) == 0): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No employees found.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Individual form
    const workDaysRadios = document.querySelectorAll('input[name="work_days"]');
    const weekendContainer = document.getElementById('weekend-workday-container');
    const paymentFrequencyRadios = document.querySelectorAll('input[name="payment_frequency"]');
    const payDay2Container = document.getElementById('individual-pay-day-2-container');
    
    // Bulk form
    const bulkWorkDaysRadios = document.querySelectorAll('input[name="bulk_work_days"]');
    const bulkWeekendContainer = document.getElementById('bulk-weekend-workday-container');
    const bulkPaymentFrequencyRadios = document.querySelectorAll('input[name="bulk_payment_frequency"]');
    const bulkPayDay2Container = document.getElementById('bulk-pay-day-2-container');
    
    // Individual form event listeners
    workDaysRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === '26') {
                weekendContainer.classList.remove('hidden');
            } else {
                weekendContainer.classList.add('hidden');
            }
        });
    });
    
    paymentFrequencyRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'Semi-Monthly') {
                payDay2Container.classList.remove('hidden');
            } else {
                payDay2Container.classList.add('hidden');
            }
        });
    });
    
    // Bulk form event listeners
    bulkWorkDaysRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === '26') {
                bulkWeekendContainer.classList.remove('hidden');
            } else {
                bulkWeekendContainer.classList.add('hidden');
            }
        });
    });
    
    bulkPaymentFrequencyRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'Semi-Monthly') {
                bulkPayDay2Container.classList.remove('hidden');
            } else {
                bulkPayDay2Container.classList.add('hidden');
            }
        });
    });
    
    // Initialize the form state for employee selector
    const employeeSelect = document.getElementById('employee_id');
    employeeSelect.addEventListener('change', function() {
        // This would be for loading the selected employee's preferences
        // Would require AJAX in a real implementation
    });
});
</script>

<?php include '../components/footer.php'; ?>