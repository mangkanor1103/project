<?php
session_start();
include '../config.php'; // Include database configuration

// Check if user is logged in as HR admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo "Unauthorized access";
    exit();
}

// Define the current pay period
$current_period = date('F Y');
$current_month = date('Y-m');

// Fetch all employees
$sql = "SELECT e.*, 
         COALESCE(ep.work_days_per_month, 22) AS work_days_per_month,
         COALESCE(ep.payment_frequency, 'Monthly') AS payment_frequency,
         COALESCE(ep.pay_day_1, 30) AS pay_day_1,
         COALESCE(ep.pay_day_2, 15) AS pay_day_2,
         ep.weekend_workday
         FROM employees e 
         LEFT JOIN employee_preferences ep ON e.id = ep.employee_id
         ORDER BY e.department, e.full_name";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<h2>No employees found.</h2>";
    exit();
}

// CSS styles for the report
echo '
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        color: #333;
        margin: 20px;
    }
    .company-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .company-header h1 {
        font-size: 24px;
        margin-bottom: 5px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 11px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 5px;
        text-align: right;
    }
    th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }
    td:nth-child(1), td:nth-child(2), td:nth-child(3) {
        text-align: left;
    }
    tr.total {
        background-color: #eef7fd;
        font-weight: bold;
    }
    .page-break {
        page-break-before: always;
    }
    .footer {
        margin-top: 30px;
        font-size: 10px;
        text-align: center;
        color: #666;
    }
    .bg-green-100 {
        background-color: #d1fae5;
    }
</style>';

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

// Initialize grand totals
$grand_total_gross = 0;
$grand_total_deductions = 0;
$grand_total_net = 0;

// Loop through each department
foreach ($departments as $department => $employees) {
    echo '<h2>' . htmlspecialchars($department) . ' Department</h2>';
    
    // Create a table for the department
    echo '<table>';
    
    // Table header
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Name</th>';
    echo '<th>Position</th>';
    echo '<th>Monthly Rate (₱)</th>';
    echo '<th>Daily Rate (₱)</th>';
    echo '<th>Work Days</th>';
    echo '<th>Days Present</th>';
    echo '<th>Days Absent</th>';
    echo '<th>Late (hrs)</th>'; // Display late as hours
    echo '<th>Regular Pay (₱)</th>';
    echo '<th>Overtime (₱)</th>';
    echo '<th>Night Diff (₱)</th>';
    echo '<th>Holiday Pay (₱)</th>';
    echo '<th>Gross (₱)</th>';
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
                SUM(legal_holiday_hours) AS total_legal_holiday_hours,
                COUNT(DISTINCT date) AS days_present,
                COUNT(CASE WHEN late_minutes > 0 THEN 1 END) AS days_late,
                SUM(late_minutes) AS total_late_minutes
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
        $days_present = $attendance['days_present'] ?? 0;
        $days_late = $attendance['days_late'] ?? 0;
        $total_late_minutes = $attendance['total_late_minutes'] ?? 0;
        
        // Get work days and payment frequency
        $work_days_per_month = $employee['work_days_per_month'];
        $payment_frequency = $employee['payment_frequency'];
        $weekend_workday = $employee['weekend_workday'];
        
        // Calculate working days for the month or half-month
        $total_working_days = $work_days_per_month;
        if ($payment_frequency == 'Semi-Monthly') {
            $total_working_days = $work_days_per_month / 2;
        }
        
        // Calculate days absent (working days - days present)
        $days_absent = $total_working_days - $days_present;
        $days_absent = max(0, $days_absent); // Ensure no negative values

        // Correct calculation of daily rate from monthly salary
        $basic_salary = $employee['basic_salary']; // Monthly salary
        $days_per_month = $work_days_per_month; // 22 or 26 days
        
        // Proper formula: Monthly Salary ÷ Number of Working Days = Daily Rate
        $daily_rate = $basic_salary / $days_per_month;
        
        // Calculate hourly rate (8 working hours per day)
        $hourly_rate = $daily_rate / 8;
        
        // Calculate overtime and premium rates
        $overtime_rate = $hourly_rate * 1.25; // Overtime rate (25% premium)
        $night_diff_rate = $hourly_rate * 0.1; // Night differential (10% premium)
        $night_overtime_rate = $overtime_rate * 0.1; // Night differential on overtime
        $restday_premium_rate = $hourly_rate * 0.3; // Rest day premium (30%)
        $special_holiday_rate = $hourly_rate * 0.3; // Special holiday premium (30%)
        $legal_holiday_rate = $hourly_rate * 1.0; // Legal holiday premium (100%)
        
        // Convert late minutes to hours for display and calculations
        $late_hours = $total_late_minutes / 60;
        $late_hours_display = number_format($late_hours, 2); // Format with 2 decimal places
        
        // Calculate late deduction based on hourly rate
        $late_deduction = $late_hours * $hourly_rate;
        
        // Determine if this is first or second half of month (for Semi-Monthly payments)
        $current_day = date('d');
        $is_first_half = $current_day <= 15;
        
        // For semi-monthly payments, adjust the salary to half of monthly
        $salary_multiplier = ($payment_frequency == 'Semi-Monthly') ? 0.5 : 1;
        $period_salary = $basic_salary * $salary_multiplier;
        
        // Calculate absences deduction (days absent * daily rate)
        $absence_deduction = $days_absent * $daily_rate;
        
        // Regular pay calculation - base salary minus absences and lates
        $regular_pay = $period_salary - $absence_deduction - $late_deduction;
        
        // Calculate premium pays
        $overtime_pay = $overtime_rate * $total_overtime_hours;
        $night_diff_pay = $night_diff_rate * $total_night_hours;
        $night_ot_pay = $night_overtime_rate * $total_night_overtime_hours;
        
        // For 26-day employees, calculate weekend rates based on preference
        if ($days_per_month == 26) {
            if ($weekend_workday == 'Saturday') {
                // Saturday is already in base pay for 26-day employees
                $restday_pay = 0;
            } else if ($weekend_workday == 'Sunday') {
                // Sunday gets premium
                $restday_pay = $restday_premium_rate * $total_restday_hours;
            } else if ($weekend_workday == 'Both') {
                // One day is in base, one gets premium - prorate based on actual rest day hours worked
                $restday_pay = ($restday_premium_rate * $total_restday_hours) / 2;
            } else {
                // Default with no weekend day selected
                $restday_pay = 0;
            }
        } else {
            // For 22-day employees, all weekend work gets rest day premium
            $restday_pay = $restday_premium_rate * $total_restday_hours;
        }
        
        // Calculate holiday pays
        $special_holiday_pay = $special_holiday_rate * $total_special_holiday_hours;
        $legal_holiday_pay = $legal_holiday_rate * $total_legal_holiday_hours;
        $holiday_pay = $hourly_rate * $total_holiday_hours;
        
        // Combine similar pay types for the table
        $total_night_pay = $night_diff_pay + $night_ot_pay;
        $total_holiday_pay = $holiday_pay + $restday_pay + $special_holiday_pay + $legal_holiday_pay;

        // Fetch expense reimbursements for the employee
        $expenses_sql = "
            SELECT SUM(amount) AS total_reimbursement
            FROM expenses 
            WHERE employee_id = ? 
            AND status = 'Approved' 
            AND expense_date LIKE ?
            AND reimbursed_date IS NULL";
        $expenses_stmt = $conn->prepare($expenses_sql);
        $expenses_stmt->bind_param("is", $employee['id'], $like_date);
        $expenses_stmt->execute();
        $expenses = $expenses_stmt->get_result()->fetch_assoc();

        // Get reimbursement amount
        $reimbursement_amount = $expenses['total_reimbursement'] ?? 0;

        // Final gross salary calculation
        $gross_salary = $regular_pay + $overtime_pay + $total_night_pay + $total_holiday_pay + $reimbursement_amount;

        // Deductions - SSS, PhilHealth, and Pag-IBIG
        // These values should ideally be calculated based on government tables
        $sss = ($gross_salary > 20000) ? 900 : ($gross_salary > 10000 ? 525 : 300); // Example tiered SSS contribution
        $philhealth = min(max($gross_salary * 0.03, 100), 1800) / 2; // PhilHealth is 3% of salary, split between employer/employee
        $pagibig = min($gross_salary * 0.02, 100); // Pag-IBIG is 2% with ₱100 cap

        // Adjust deductions for semi-monthly payments
        if ($payment_frequency == 'Semi-Monthly') {
            if ($is_first_half) {
                // First half of the month gets all contributions
            } else {
                // Second half of the month - no contributions
                $sss = 0;
                $philhealth = 0;
                $pagibig = 0;
            }
        }
        
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
        echo '<td>' . number_format($basic_salary, 2) . '</td>';
        echo '<td>' . number_format($daily_rate, 2) . '</td>';
        echo '<td>' . $work_days_per_month . ($payment_frequency == 'Semi-Monthly' ? '/2' : '') . '</td>';
        echo '<td>' . number_format($days_present, 1) . '</td>';
        echo '<td>' . number_format($days_absent, 1) . '</td>';
        echo '<td>' . $late_hours_display . '</td>'; // Display late as hours
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
    echo '<td colspan="9" style="text-align:right;"><strong>Department Total:</strong></td>';
    echo '<td>' . number_format($dept_total_gross - $dept_total_deductions, 2) . '</td>';
    echo '<td>' . number_format(0, 2) . '</td>';
    echo '<td>' . number_format(0, 2) . '</td>';
    echo '<td>' . number_format(0, 2) . '</td>';
    echo '<td>' . number_format($dept_total_gross, 2) . '</td>';
    echo '<td>' . number_format($dept_total_deductions, 2) . '</td>';
    echo '<td class="bg-green-100">' . number_format($dept_total_net, 2) . '</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    // Add to grand totals
    $grand_total_gross += $dept_total_gross;
    $grand_total_deductions += $dept_total_deductions;
    $grand_total_net += $dept_total_net;
    
    echo '<div class="page-break"></div>';
}

// Company-wide grand total section
echo '<h2>Company Summary</h2>';
echo '<table>';
echo '<tr>';
echo '<th style="text-align:left;">Description</th>';
echo '<th>Gross Pay (₱)</th>';
echo '<th>Total Deductions (₱)</th>';
echo '<th>Net Pay (₱)</th>';
echo '</tr>';
echo '<tr class="total">';
echo '<td style="text-align:left;"><strong>Grand Total</strong></td>';
echo '<td>' . number_format($grand_total_gross, 2) . '</td>';
echo '<td>' . number_format($grand_total_deductions, 2) . '</td>';
echo '<td class="bg-green-100">' . number_format($grand_total_net, 2) . '</td>';
echo '</tr>';
echo '</table>';

// Add summary statistics
echo '<div style="margin-top: 20px; display: flex; justify-content: space-between;">';
echo '<div style="width: 32%; background-color: #f0f9ff; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
echo '<h3 style="font-size: 16px; color: #2563eb; margin: 0 0 10px 0;">Total Gross Pay</h3>';
echo '<p style="font-size: 20px; font-weight: bold; margin: 0;">₱' . number_format($grand_total_gross, 2) . '</p>';
echo '</div>';

echo '<div style="width: 32%; background-color: #fff1f2; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
echo '<h3 style="font-size: 16px; color: #dc2626; margin: 0 0 10px 0;">Total Deductions</h3>';
echo '<p style="font-size: 20px; font-weight: bold; margin: 0;">₱' . number_format($grand_total_deductions, 2) . '</p>';
echo '</div>';

echo '<div style="width: 32%; background-color: #ecfdf5; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
echo '<h3 style="font-size: 16px; color: #059669; margin: 0 0 10px 0;">Total Net Pay</h3>';
echo '<p style="font-size: 20px; font-weight: bold; margin: 0;">₱' . number_format($grand_total_net, 2) . '</p>';
echo '</div>';
echo '</div>';

// Add signature lines for approvals
echo '<div style="margin-top: 50px; display: flex; justify-content: space-between;">';
echo '<div style="width: 30%; text-align: center;">';
echo '<div style="border-top: 1px solid #000; padding-top: 5px;">HR Manager</div>';
echo '</div>';
echo '<div style="width: 30%; text-align: center;">';
echo '<div style="border-top: 1px solid #000; padding-top: 5px;">Finance Officer</div>';
echo '</div>';
echo '<div style="width: 30%; text-align: center;">';
echo '<div style="border-top: 1px solid #000; padding-top: 5px;">Company President</div>';
echo '</div>';
echo '</div>';

// Footer
echo '<div class="footer">';
echo '<p>Generated on ' . date('Y-m-d H:i:s') . '</p>';
echo '<p>This is an official payroll document generated by the HR system.</p>';
echo '<p>Company Name © ' . date('Y') . ' - All Rights Reserved</p>';
echo '</div>';
?>