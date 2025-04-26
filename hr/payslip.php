<?php
// filepath: c:\xampp\htdocs\project\hr\payslip.php
session_start();
include '../config.php'; // Include database configuration

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Define the current pay period (e.g., "April 2025")
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
      ORDER BY e.full_name";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die("No employees found.");
}

include '../components/header.php';
?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">Success!</p>
                <p>Payroll processed successfully. <?php echo $_GET['processed'] ?? 0; ?> expense reimbursements have been processed.</p>
            </div>
        <?php endif; ?>

        <!-- Payslip Header -->
        <div class="flex flex-col items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">All Employee Payslips</h1>
            <p class="text-gray-600">Pay Period: <?php echo $current_period; ?></p>
        </div>

        <!-- Back to Dashboard button -->
        <div class="fixed bottom-6 left-6">
            <a href="hr_dashboard.php" class="bg-blue-600 text-white px-4 py-3 rounded-full shadow-lg hover:bg-blue-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <div class="flex justify-end mb-6">
            <button onclick="printAllPayslips()" class="bg-green-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-green-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print All Payslips
            </button>
        </div>
        
        <!-- Payslip Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($employee = $result->fetch_assoc()): ?>
                <?php
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
                $total_late_minutes = $attendance['total_late_minutes'] ?? 0;
                
                // Calculate days_late based on late_minutes 
                $days_late = ($total_late_minutes > 0) ? 1 : 0;

                // Fetch employee preferences
                try {
                    $pref_sql = "SELECT 
                                    COALESCE(work_days_per_month, 22) AS work_days_per_month, 
                                    COALESCE(payment_frequency, 'Semi-Monthly') AS payment_frequency,
                                    weekend_workday
                                FROM employee_preferences 
                                WHERE employee_id = ?";
                    $pref_stmt = $conn->prepare($pref_sql);
                    $pref_stmt->bind_param("i", $employee['id']);
                    $pref_stmt->execute();
                    $preferences = $pref_stmt->get_result()->fetch_assoc();
                    
                    // Set values from database if available
                    $work_days_per_month = $preferences ? $preferences['work_days_per_month'] : 22;
                    $payment_frequency = $preferences ? $preferences['payment_frequency'] : 'Semi-Monthly';
                    $weekend_workday = $preferences ? $preferences['weekend_workday'] : null;
                } catch (Exception $e) {
                    // Table doesn't exist - use default values
                    $work_days_per_month = 22;
                    $payment_frequency = 'Semi-Monthly';
                    $weekend_workday = null;
                }
                
                // Determine if this is first or second half of month (for Semi-Monthly payments)
                $current_day = date('d');
                $is_first_half = $current_day <= 15;
                
                // Calculate working days for the month or half-month
                $total_working_days = $work_days_per_month;
                if ($payment_frequency == 'Semi-Monthly') {
                    $total_working_days = $work_days_per_month / 2;
                }
                
                // Correct calculation of daily rate from monthly salary
                $basic_salary = $employee['basic_salary']; // Monthly salary from DB
                $days_per_month = $work_days_per_month; // 22 or 26 days

                // Proper formula: Monthly Salary ÷ Number of Working Days = Daily Rate
                $daily_rate = $basic_salary / $days_per_month;

                // Calculate hourly rate (8 working hours per day)
                $hourly_rate = $daily_rate / 8;

                // Calculate overtime and premium rates
                $overtime_rate = $hourly_rate * 1.25; // Overtime rate (25% premium)
                $night_diff_rate = $hourly_rate * 0.1; // Night differential (10% premium)
                $night_overtime_rate = $overtime_rate * 0.1; // Night differential on overtime (10% premium)
                $restday_premium_rate = $hourly_rate * 0.3; // Rest day premium (30%)
                $special_holiday_rate = $hourly_rate * 0.3; // Special holiday premium (30%)
                $legal_holiday_rate = $hourly_rate * 1.0; // Legal holiday premium (100%)

                // Calculate late deduction
                $late_deduction = ($total_late_minutes / 60) * $hourly_rate; // Convert minutes to hours

                // Calculate days absent (working days - days present)
                $days_absent = $total_working_days - $days_present;
                $days_absent = max(0, $days_absent); // Ensure no negative values

                // For absences, deduct the full daily rate
                $absence_deduction = $days_absent * $daily_rate;

                // Fetch approved expenses for this employee in the current month that should be reimbursed
                $expenses_sql = "
                    SELECT SUM(amount) AS total_reimbursement, COUNT(*) AS expense_count
                    FROM expenses 
                    WHERE employee_id = ? 
                    AND status = 'Approved' 
                    AND expense_date LIKE ?
                    AND reimbursed_date IS NULL";
                $expenses_stmt = $conn->prepare($expenses_sql);
                $expenses_stmt->bind_param("is", $employee['id'], $like_date);
                $expenses_stmt->execute();
                $expenses = $expenses_stmt->get_result()->fetch_assoc();

                // Get total approved expenses amount
                $reimbursement_amount = $expenses['total_reimbursement'] ?? 0;
                $expense_count = $expenses['expense_count'] ?? 0;

                // For semi-monthly payments, adjust the salary to half of monthly
                $salary_multiplier = ($payment_frequency == 'Semi-Monthly') ? 0.5 : 1;
                $base_salary = $basic_salary * $salary_multiplier;

                // Regular pay calculation - base salary minus absence deduction
                $regular_pay = $base_salary - $absence_deduction;

                // Calculate premium pays
                $overtime_pay = $overtime_rate * $total_overtime_hours;
                $night_diff_pay = $night_diff_rate * $total_night_hours; 
                $night_ot_pay = $night_overtime_rate * $total_night_overtime_hours;
                $holiday_pay = $hourly_rate * $total_holiday_hours; // Regular pay is already included in base

                // For 26-day employees, calculate weekend rates based on preference
                $weekend_workday = $employee['weekend_workday'] ?? 'Saturday';
                if ($days_per_month == 26) {
                    if ($weekend_workday == 'Saturday') {
                        // Saturday is already in base pay for 26-day employees
                        $restday_pay = 0;
                    } else if ($weekend_workday == 'Sunday') {
                        // Sunday gets premium
                        $restday_pay = $restday_premium_rate * $total_restday_hours;
                    } else if ($weekend_workday == 'Both') {
                        // One day is in base, one gets premium - prorate based on actual rest day hours worked
                        // Assume a 50/50 split between Saturday and Sunday unless we track them separately
                        $restday_pay = ($restday_premium_rate * $total_restday_hours) / 2; 
                    } else {
                        // Default with no weekend day selected
                        $restday_pay = 0;
                    }
                } else {
                    // For 22-day employees, all weekend work gets rest day premium
                    $restday_pay = $restday_premium_rate * $total_restday_hours;
                }

                $special_holiday_pay = $special_holiday_rate * $total_special_holiday_hours;
                $legal_holiday_pay = $legal_holiday_rate * $total_legal_holiday_hours;

                // Final gross salary calculation
                // Regular pay already has absence deduction applied
                // Late deduction is applied separately
                $gross_salary = $regular_pay - $late_deduction + 
                              $overtime_pay + $night_diff_pay + $night_ot_pay +
                              $holiday_pay + $restday_pay + 
                              $special_holiday_pay + $legal_holiday_pay + 
                              $reimbursement_amount;

                // Deductions
                // SSS, PhilHealth, and Pag-IBIG contributions
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
                
                // Display formats for hours
                $late_hours_display = floor($total_late_minutes / 60);
                $late_minutes_display = $total_late_minutes % 60;
                
                // Convert hours to hours, minutes format for display
                $total_hours = floor($total_hours_worked);
                $total_minutes = floor(($total_hours_worked - $total_hours) * 60);
                ?>

                <!-- Payslip Card -->
                <div class="bg-white shadow-lg rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($employee['full_name']); ?></h2>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($employee['job_position']); ?> • <?php echo htmlspecialchars($employee['department']); ?></p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-xs font-medium">
                                <?php echo $payment_frequency; ?>
                            </span>
                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-md text-xs font-medium">
                                <?php echo $work_days_per_month; ?> days/month
                            </span>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-md text-xs font-medium">
                                <?php echo $days_present; ?> days present
                            </span>
                            <?php if ($days_absent > 0): ?>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded-md text-xs font-medium">
                                <?php echo $days_absent; ?> days absent
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Improved rate information section for the payslip cards -->
                    <div class="px-6 py-4 bg-blue-50 border-t border-b border-blue-100">
                        <h4 class="text-sm font-semibold text-gray-600 mb-2">RATE INFORMATION</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">Monthly Salary</span>
                                <p class="font-semibold">₱<?php echo number_format($basic_salary, 2); ?></p>
                            </div>
                            <div class="relative">
                                <span class="text-sm text-gray-500">Daily Rate</span>
                                <p class="font-semibold">₱<?php echo number_format($daily_rate, 2); ?></p>
                                <div class="absolute -top-3 -right-3 bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded-full shadow-sm">
                                    Monthly ÷ <?php echo $days_per_month; ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Hourly Rate (8 hrs)</span>
                                <p class="font-semibold">₱<?php echo number_format($hourly_rate, 2); ?></p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">OT Rate (×1.25)</span>
                                <p class="font-semibold">₱<?php echo number_format($overtime_rate, 2); ?></p>
                            </div>
                        </div>
                        <!-- Daily rate formula explainer -->
                        <div class="mt-2 text-xs bg-white px-2 py-1.5 rounded border border-blue-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-700">
                                    Formula: ₱<?php echo number_format($basic_salary, 2); ?> ÷ <?php echo $days_per_month; ?> days = ₱<?php echo number_format($daily_rate, 2); ?>/day
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 bg-gray-50">
                        <p><strong>Period Salary:</strong> ₱<?php echo number_format($base_salary, 2); ?></p>
                        
                        <?php if ($absence_deduction > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Absences (<?php echo $days_absent; ?> days × ₱<?php echo number_format($daily_rate, 2); ?>):</strong></span>
                                <span class="text-red-600 font-medium">- ₱<?php echo number_format($absence_deduction, 2); ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($late_deduction > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Late (<?php echo "$late_hours_display hrs $late_minutes_display mins"; ?> × ₱<?php echo number_format($hourly_rate, 2); ?>):</strong></span>
                                <span class="text-red-600 font-medium">- ₱<?php echo number_format($late_deduction, 2); ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($overtime_pay > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Overtime (<?php echo number_format($total_overtime_hours, 2); ?> hrs × ₱<?php echo number_format($overtime_rate, 2); ?>):</strong></span>
                                <span class="text-green-600 font-medium">+ ₱<?php echo number_format($overtime_pay, 2); ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($night_diff_pay > 0 || $night_ot_pay > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Night Differential:</strong></span>
                                <span class="text-green-600 font-medium">+ ₱<?php echo number_format($night_diff_pay + $night_ot_pay, 2); ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($reimbursement_amount > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Reimbursements (<?php echo $expense_count; ?>):</strong></span>
                                <span class="text-green-600 font-medium">+ ₱<?php echo number_format($reimbursement_amount, 2); ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <p class="mt-3 pt-2 border-t"><strong>Gross Pay:</strong> ₱<?php echo number_format($gross_salary, 2); ?></p>
                        
                        <div class="mt-2 text-sm">
                            <p class="flex justify-between">
                                <span>SSS:</span>
                                <span>- ₱<?php echo number_format($sss, 2); ?></span>
                            </p>
                            <p class="flex justify-between">
                                <span>PhilHealth:</span>
                                <span>- ₱<?php echo number_format($philhealth, 2); ?></span>
                            </p>
                            <p class="flex justify-between">
                                <span>Pag-IBIG:</span>
                                <span>- ₱<?php echo number_format($pagibig, 2); ?></span>
                            </p>
                            <p class="flex justify-between font-medium">
                                <span>Total Deductions:</span>
                                <span>- ₱<?php echo number_format($total_deductions, 2); ?></span>
                            </p>
                        </div>
                        
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="flex justify-between items-center">
                                <span class="font-bold">Net Salary:</span>
                                <span class="font-bold text-lg text-blue-700">₱<?php echo number_format($net_salary, 2); ?></span>
                            </p>
                        </div>
                        
                        <button onclick="viewPayslip(<?php echo $employee['id']; ?>)" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                            View Details
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="payslipModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50 overflow-auto">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl max-h-[90vh] overflow-y-auto">
        <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
            <h2 class="text-xl font-bold text-gray-800">Detailed Payslip</h2>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <div id="modalContent" class="p-6">
            <!-- Payslip details will be loaded here -->
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>

<script>
// Add this function to your existing script section
function printAllPayslips() {
    // Show loading message
    const loadingModal = document.createElement('div');
    loadingModal.className = 'fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50';
    loadingModal.innerHTML = `
        <div class="bg-white p-5 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-700 mr-3"></div>
                <p>Preparing payslips for printing...</p>
            </div>
        </div>
    `;
    document.body.appendChild(loadingModal);
    
    // Fetch all employee data
    fetch('get_all_payslips.php')
        .then(response => response.text())
        .then(data => {
            // Remove loading modal
            document.body.removeChild(loadingModal);
            
            // Open new window with horizontal table layout
            const printWindow = window.open('', '', 'width=1200,height=800,scrollbars=yes');
            printWindow.document.write('<html><head><title>All Employee Payslips</title>');
            printWindow.document.write('<style>');
            printWindow.document.write(`
                @page { size: landscape; }
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                h1 { text-align: center; margin-bottom: 20px; color: #2563eb; }
                .company-header { text-align: center; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th { background-color: #f3f4f6; text-align: left; padding: 8px; border: 1px solid #e5e7eb; font-weight: bold; }
                td { padding: 8px; border: 1px solid #e5e7eb; }
                .total { font-weight: bold; background-color: #f9fafb; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #6b7280; }
                .page-break { page-break-after: always; }
                .bg-green-100 { background-color: #d1fae5; }
            `);
            printWindow.document.write('</style></head><body>');
            printWindow.document.write(data);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            
            setTimeout(function() {
                printWindow.print();
            }, 1000);
        })
        .catch(error => {
            // Remove loading modal
            document.body.removeChild(loadingModal);
            alert('Error generating payslips: ' + error.message);
        });
}

// Keep your existing functions here
function viewPayslip(employeeId) {
    // Show loading state
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-700"></div></div>';
    document.getElementById('payslipModal').classList.remove('hidden');
    
    // Fetch employee payslip data using AJAX
    fetch(`get_payslip.php?employee_id=${employeeId}`)
        .then(response => response.text())
        .then(data => {
            modalContent.innerHTML = data;
        })
        .catch(error => {
            modalContent.innerHTML = `<div class="text-red-500">Error loading payslip data: ${error.message}</div>`;
        });
}

function closeModal() {
    document.getElementById('payslipModal').classList.add('hidden');
}

// Close modal when clicking outside the content area
document.getElementById('payslipModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeModal();
    }
});

function printPayslip() {
    const printContent = document.getElementById('payslip-content').innerHTML;
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write('<html><head><title>Print Payslip</title>');
    printWindow.document.write('<style>');
    printWindow.document.write(`
    body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h1 { color: #2563eb; margin-bottom: 5px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th { background-color: #f3f4f6; text-align: left; padding: 8px; }
    td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
    .total { font-weight: bold; }
    .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #6b7280; }
    `);
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    setTimeout(function () {
        printWindow.print();
    }, 500);
}

function downloadPDF() {
    alert("PDF download functionality requires a server-side PDF generation library.");
}
</script>