<?php
// filepath: c:\xampp\htdocs\project\hr\get_all_payslips.php
session_start();
include '../config.php'; // Include database configuration

// Check if user is logged in as HR admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo "Unauthorized access";
    exit();
}

// Define the current pay period
$current_period = date('F Y');

// Fetch all employees
$sql = "SELECT * FROM employees ORDER BY department, full_name";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<h2>No employees found.</h2>";
    exit();
}

// Define the current month for attendance
$current_month = date('Y-m');

// Start building the HTML output
echo '<div class="company-header">';
echo '<h1>Company Name - Employee Payslips</h1>';
echo '<p>Pay Period: ' . $current_period . '</p>';
echo '</div>';

// Group employees by department
$departments = [];
while ($employee = $result->fetch_assoc()) {
    if (!isset($departments[$employee['department']])) {
        $departments[$employee['department']] = [];
    }
    $departments[$employee['department']][] = $employee;
}

// Loop through each department
foreach ($departments as $department => $employees) {
    echo '<h2>' . htmlspecialchars($department) . ' Department</h2>';
    
    // Create a table for the department
    echo '<table>';
    
    // Table header
    echo '<thead>';
    echo '<tr>';
    echo '<th>Employee ID</th>';
    echo '<th>Name</th>';
    echo '<th>Position</th>';
    echo '<th>Regular Hours</th>';
    echo '<th>Overtime Hours</th>';
    echo '<th>Night Hours</th>';
    echo '<th>Regular Pay (₱)</th>';
    echo '<th>Overtime Pay (₱)</th>';
    echo '<th>Night Pay (₱)</th>';
    echo '<th>Holiday Pay (₱)</th>';
    echo '<th>Gross Salary (₱)</th>';
    echo '<th>Deductions (₱)</th>';
    echo '<th>Net Salary (₱)</th>';
    echo '</tr>';
    echo '</thead>';
    
    echo '<tbody>';
    
    // Department totals
    $dept_total_gross = 0;
    $dept_total_deductions = 0;
    $dept_total_net = 0;
    
    // Loop through employees in this department
    foreach ($employees as $employee) {
        // Fetch attendance details for the current employee
        $attendance_sql = "
            SELECT 
                SUM(hours_worked) AS total_hours_worked,
                SUM(overtime_hours) AS total_overtime_hours,
                SUM(night_hours) AS total_night_hours,
                SUM(night_overtime_hours) AS total_night_overtime_hours,
                SUM(holiday_hours) AS total_holiday_hours,
                SUM(restday_hours) AS total_restday_hours,
                SUM(special_holiday_hours) AS total_special_holiday_hours,
                SUM(legal_holiday_hours) AS total_legal_holiday_hours
            FROM attendance 
            WHERE employee_id = ? AND date LIKE ?";
        $attendance_stmt = $conn->prepare($attendance_sql);
        $like_date = $current_month . '%';
        $attendance_stmt->bind_param("is", $employee['id'], $like_date);
        $attendance_stmt->execute();
        $attendance = $attendance_stmt->get_result()->fetch_assoc();

        // Initialize attendance data with default values if null
        $total_hours_worked = $attendance['total_hours_worked'] ?? 0;
        $total_overtime_hours = $attendance['total_overtime_hours'] ?? 0;
        $total_night_hours = $attendance['total_night_hours'] ?? 0;
        $total_night_overtime_hours = $attendance['total_night_overtime_hours'] ?? 0;
        $total_holiday_hours = $attendance['total_holiday_hours'] ?? 0;
        $total_restday_hours = $attendance['total_restday_hours'] ?? 0;
        $total_special_holiday_hours = $attendance['total_special_holiday_hours'] ?? 0;
        $total_legal_holiday_hours = $attendance['total_legal_holiday_hours'] ?? 0;

        // Salary calculations
        $basic_salary = $employee['basic_salary']; // Monthly salary from DB
        $basic_rate_per_day = $basic_salary / 22; // Daily rate
        $hourly_rate = $basic_rate_per_day / 8; // Hourly rate
        $overtime_rate = $hourly_rate * 1.25; // Overtime rate

        // Calculate pay components
        $regular_pay = $hourly_rate * $total_hours_worked;
        $overtime_pay = $overtime_rate * $total_overtime_hours;
        $night_diff_pay = $hourly_rate * 1.1 * $total_night_hours;
        $night_ot_pay = $overtime_rate * 1.1 * $total_night_overtime_hours;
        $holiday_pay = $hourly_rate * 2 * $total_holiday_hours;
        $restday_pay = $hourly_rate * 1.3 * $total_restday_hours;
        $special_holiday_pay = $hourly_rate * 1.3 * $total_special_holiday_hours;
        $legal_holiday_pay = $hourly_rate * 2 * $total_legal_holiday_hours;

        // Combine similar pay types for the table
        $total_night_pay = $night_diff_pay + $night_ot_pay;
        $total_holiday_pay = $holiday_pay + $restday_pay + $special_holiday_pay + $legal_holiday_pay;

        // Fetch expense reimbursements for the employee
        $expenses_sql = "
            SELECT SUM(amount) AS total_reimbursement
            FROM expenses 
            WHERE employee_id = ? 
            AND (status = 'Approved' OR status = 'Reimbursed')
            AND expense_date LIKE ?";
        $expenses_stmt = $conn->prepare($expenses_sql);
        $expenses_stmt->bind_param("is", $employee['id'], $like_date);
        $expenses_stmt->execute();
        $expenses = $expenses_stmt->get_result()->fetch_assoc();

        // Get reimbursement amount
        $reimbursement_amount = $expenses['total_reimbursement'] ?? 0;

        // Gross salary calculation
        $gross_salary = $regular_pay + $overtime_pay + $night_diff_pay + $night_ot_pay +
            $holiday_pay + $restday_pay + $special_holiday_pay + $legal_holiday_pay + $reimbursement_amount;

        // Deductions
        $sss = 525; // Fixed SSS contribution
        $philhealth = 250; // Fixed PhilHealth contribution
        $pagibig = 100; // Fixed Pag-IBIG contribution
        $total_deductions = $sss + $philhealth + $pagibig;

        // Calculate net salary
        $net_salary = $gross_salary - $total_deductions;
        
        // Add to department totals
        $dept_total_gross += $gross_salary;
        $dept_total_deductions += $total_deductions;
        $dept_total_net += $net_salary;
        
        // Output employee row
        echo '<tr>';
        echo '<td>' . str_pad($employee['id'], 4, '0', STR_PAD_LEFT) . '</td>';
        echo '<td>' . htmlspecialchars($employee['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($employee['job_position']) . '</td>';
        echo '<td>' . number_format($total_hours_worked, 2) . '</td>';
        echo '<td>' . number_format($total_overtime_hours, 2) . '</td>';
        echo '<td>' . number_format($total_night_hours, 2) . '</td>';
        echo '<td>' . number_format($regular_pay, 2) . '</td>';
        echo '<td>' . number_format($overtime_pay, 2) . '</td>';
        echo '<td>' . number_format($total_night_pay, 2) . '</td>';
        echo '<td>' . number_format($total_holiday_pay, 2) . '</td>';
        echo '<td>' . number_format($gross_salary, 2) . '</td>';
        echo '<td>' . number_format($total_deductions, 2) . '</td>';
        echo '<td class="bg-green-100">' . number_format($net_salary, 2) . '</td>';
        echo '</tr>';
    }
    
    // Department total row
    echo '<tr class="total">';
    echo '<td colspan="10" style="text-align:right;"><strong>Department Total:</strong></td>';
    echo '<td>' . number_format($dept_total_gross, 2) . '</td>';
    echo '<td>' . number_format($dept_total_deductions, 2) . '</td>';
    echo '<td class="bg-green-100">' . number_format($dept_total_net, 2) . '</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<div class="page-break"></div>';
}

// Grand totals for all departments
$grand_total_query = "
    SELECT 
        SUM(hours_worked) AS total_hours_worked,
        SUM(overtime_hours) AS total_overtime_hours,
        SUM(night_hours) AS total_night_hours
    FROM attendance 
    WHERE date LIKE ?";
$grand_stmt = $conn->prepare($grand_total_query);
$grand_stmt->bind_param("s", $like_date);
$grand_stmt->execute();
$grand_totals = $grand_stmt->get_result()->fetch_assoc();

// Footer
echo '<div class="footer">';
echo '<p>Generated on ' . date('Y-m-d H:i:s') . '</p>';
echo '<p>This is an official payroll document and is valid without signature.</p>';
echo '</div>';
?>