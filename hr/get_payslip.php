<?php
// filepath: c:\xampp\htdocs\project\hr\get_payslip.php
session_start();
include '../config.php';

// Check if user is logged in as HR admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo "Unauthorized access";
    exit();
}

// Get employee ID from request
if (!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
    echo "Employee ID is required";
    exit();
}

$employee_id = $_GET['employee_id'];
$current_period = date('F Y');
$current_month = date('Y-m');

// Fetch employee details
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    echo "Employee record not found.";
    exit();
}

// Fetch attendance details for the current month
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
$attendance_stmt->bind_param("is", $employee_id, $like_date);
$attendance_stmt->execute();
$attendance = $attendance_stmt->get_result()->fetch_assoc();

// Fetch approved expenses for this employee in the current month that should be reimbursed
$expenses_sql = "
    SELECT SUM(amount) AS total_reimbursement,
           COUNT(*) AS expense_count
    FROM expenses 
    WHERE employee_id = ? 
    AND status = 'Approved' 
    AND expense_date LIKE ?
    AND reimbursed_date IS NULL";
$expenses_stmt = $conn->prepare($expenses_sql);
$expenses_stmt->bind_param("is", $employee_id, $like_date);
$expenses_stmt->execute();
$expenses = $expenses_stmt->get_result()->fetch_assoc();

// Get expense details for itemization
$expense_details_sql = "
    SELECT expense_type, amount, expense_date
    FROM expenses 
    WHERE employee_id = ? 
    AND status = 'Approved' 
    AND expense_date LIKE ?
    AND reimbursed_date IS NULL
    ORDER BY expense_date";
$expense_details_stmt = $conn->prepare($expense_details_sql);
$expense_details_stmt->bind_param("is", $employee_id, $like_date);
$expense_details_stmt->execute();
$expense_details_result = $expense_details_stmt->get_result();

// Get total approved expenses amount
$reimbursement_amount = $expenses['total_reimbursement'] ?? 0;
$expense_count = $expenses['expense_count'] ?? 0;

// Initialize attendance data with default values
$total_hours_worked = $attendance['total_hours_worked'] ?? 0;
$total_overtime_hours = $attendance['total_overtime_hours'] ?? 0;
$total_night_hours = $attendance['total_night_hours'] ?? 0;
$total_night_overtime_hours = $attendance['total_night_overtime_hours'] ?? 0;
$total_holiday_hours = $attendance['total_holiday_hours'] ?? 0;
$total_restday_hours = $attendance['total_restday_hours'] ?? 0;
$total_special_holiday_hours = $attendance['total_special_holiday_hours'] ?? 0;
$total_legal_holiday_hours = $attendance['total_legal_holiday_hours'] ?? 0;

// Fetch today's attendance details
$today_date = date('Y-m-d');
$today_attendance_sql = "SELECT time_in FROM attendance WHERE employee_id = ? AND date = ?";
$today_attendance_stmt = $conn->prepare($today_attendance_sql);
$today_attendance_stmt->bind_param("is", $employee_id, $today_date);
$today_attendance_stmt->execute();
$today_attendance = $today_attendance_stmt->get_result()->fetch_assoc();

// First, calculate salary base values
$basic_salary = $employee['basic_salary']; // Monthly salary from DB
$basic_rate_per_day = $basic_salary / 22; // Daily rate
$hourly_rate = $basic_rate_per_day / 8; // Hourly rate
$overtime_rate = $hourly_rate * 1.25; // Overtime rate

// Now calculate late deduction using the proper hourly rate
$regular_start_time = strtotime('08:00:00'); // Regular start time
$employee_time_in = strtotime($today_attendance['time_in'] ?? '08:00:00'); // Employee's actual time in

// Calculate late time in hours
$late_seconds = max(0, $employee_time_in - $regular_start_time);
$total_late_minutes = $late_seconds / 60; // Convert seconds to minutes
$late_hours = $total_late_minutes / 60; // Convert minutes to hours

// Format for display
$late_hours_display = floor($late_hours);
$late_minutes_display = round(($late_hours - $late_hours_display) * 60);

// Calculate late deduction using hourly rate
$late_deduction = $late_hours * $hourly_rate;

// Make sure it's not negative
if ($late_deduction < 0) {
    $late_deduction = 0.00;
}

// Deduct late hours from total hours worked
$total_hours_worked -= $late_hours;
if ($total_hours_worked < 0) {
    $total_hours_worked = 0; // Ensure total hours worked is not negative
}

// Calculate pay components
$regular_pay = $hourly_rate * $total_hours_worked;
$overtime_pay = $overtime_rate * $total_overtime_hours;
$night_diff_pay = $hourly_rate * 1.1 * $total_night_hours;
$night_ot_pay = $overtime_rate * 1.1 * $total_night_overtime_hours;
$holiday_pay = $hourly_rate * 2 * $total_holiday_hours;
$restday_pay = $hourly_rate * 1.3 * $total_restday_hours;
$special_holiday_pay = $hourly_rate * 1.3 * $total_special_holiday_hours;
$legal_holiday_pay = $hourly_rate * 2 * $total_legal_holiday_hours;

// Convert total hours worked into hours, minutes, and seconds
$total_hours = floor($total_hours_worked);
$total_minutes = floor(($total_hours_worked - $total_hours) * 60);
$total_seconds = round((($total_hours_worked - $total_hours) * 60 - $total_minutes) * 60);

// Update the gross salary calculation
$gross_salary = $regular_pay + $overtime_pay + $night_diff_pay + $night_ot_pay +
    $holiday_pay + $restday_pay + $special_holiday_pay + $legal_holiday_pay + $reimbursement_amount;

// Deductions
$sss = 525; // Fixed SSS contribution
$philhealth = 250; // Fixed PhilHealth contribution
$pagibig = 100; // Fixed Pag-IBIG contribution
$total_deductions = $sss + $philhealth + $pagibig;

// Calculate net salary
$net_salary = $gross_salary - $total_deductions;
?>

<!-- Detailed Payslip Content -->
<div id="payslip-content" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <!-- Payslip Header with company logo placeholder -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold">Company Name</h2>
                <p class="text-sm text-blue-100">123 Business Avenue, City, Country</p>
            </div>
            <div>
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-blue-600 font-bold">
                    LOGO
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Information Section -->
    <div class="px-8 py-6 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center space-x-4 mb-4">
            <!-- Employee photo -->
            <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 border-2 border-blue-500">
                <?php if (!empty($employee['image'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employee['image']); ?>"
                        class="w-full h-full object-cover"
                        alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h3 class="text-xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($employee['full_name']); ?>
                </h3>
                <p class="text-blue-600">
                    <?php echo htmlspecialchars($employee['job_position']); ?> • 
                    <?php echo htmlspecialchars($employee['department']); ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Personal Information</h4>
                <div class="space-y-2">
                    <p>
                        <span class="font-medium text-gray-600">Employee ID:</span> 
                        <span class="text-gray-800">#<?php echo str_pad($employee['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </p>
                    <p>
                        <span class="font-medium text-gray-600">Email:</span> 
                        <span class="text-gray-800"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></span>
                    </p>
                    <p>
                        <span class="font-medium text-gray-600">Date Hired:</span> 
                        <span class="text-gray-800"><?php echo htmlspecialchars($employee['date_hired'] ?? 'N/A'); ?></span>
                    </p>
                </div>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Payment Details</h4>
                <p><span class="font-medium text-gray-600">Payment Method:</span> <span class="text-gray-800">Direct Deposit</span></p>
                <p><span class="font-medium text-gray-600">Pay Date:</span> <span class="text-gray-800"><?php echo date('F d, Y'); ?></span></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mt-4">
            <div>
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Work Summary</h4>
                <p><span class="font-medium text-gray-600">Total Hours Worked:</span>
                    <?php echo "$total_hours hrs $total_minutes mins $total_seconds secs"; ?>
                </p>
                <p><span class="font-medium text-gray-600">Late Time:</span>
                    <?php echo "$late_hours_display hrs $late_minutes_display mins"; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Salary Details -->
    <div class="p-8">
        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Earnings</h4>
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-3 gap-4 border-b border-gray-200 pb-4 mb-4">
                <div class="font-medium text-gray-600">Description</div>
                <div class="font-medium text-gray-600">Hours/Rate</div>
                <div class="font-medium text-gray-600 text-right">Amount</div>
            </div>

            <!-- Regular Hours -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Regular Hours</div>
                <div>
                    <?php echo "$total_hours hrs $total_minutes mins $total_seconds secs"; ?> ×
                    ₱<?php echo number_format($hourly_rate, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($regular_pay, 2); ?></div>
            </div>

            <!-- Overtime -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Overtime</div>
                <div>
                    <?php echo number_format($total_overtime_hours, 2); ?> hrs ×
                    ₱<?php echo number_format($overtime_rate, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($overtime_pay, 2); ?></div>
            </div>

            <!-- Night Differential -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Night Differential</div>
                <div>
                    <?php echo number_format($total_night_hours, 2); ?> hrs ×
                    ₱<?php echo number_format($hourly_rate * 1.1, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($night_diff_pay, 2); ?></div>
            </div>

            <!-- Night Overtime -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Night Overtime</div>
                <div>
                    <?php echo number_format($total_night_overtime_hours, 2); ?> hrs ×
                    ₱<?php echo number_format($overtime_rate * 1.1, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($night_ot_pay, 2); ?></div>
            </div>

            <!-- Holiday Hours -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Holiday Hours</div>
                <div>
                    <?php echo number_format($total_holiday_hours, 2); ?> hrs ×
                    ₱<?php echo number_format($hourly_rate * 2, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($holiday_pay, 2); ?></div>
            </div>

            <!-- Rest Day Hours -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Rest Day Hours</div>
                <div>
                    <?php echo number_format($total_restday_hours, 2); ?> hrs ×
                    ₱<?php echo number_format($hourly_rate * 1.3, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($restday_pay, 2); ?></div>
            </div>

            <!-- Special Holiday -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Special Holiday</div>
                <div>
                    <?php echo number_format($total_special_holiday_hours, 2); ?> hrs ×
                    ₱<?php echo number_format($hourly_rate * 1.3, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($special_holiday_pay, 2); ?></div>
            </div>

            <!-- Legal Holiday -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Legal Holiday</div>
                <div>
                    <?php echo number_format($total_legal_holiday_hours, 2); ?> hrs ×
                    ₱<?php echo number_format($hourly_rate * 2, 2); ?>
                </div>
                <div class="text-right">₱<?php echo number_format($legal_holiday_pay, 2); ?></div>
            </div>

            <!-- Late Deduction -->
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Late Deduction</div>
                <div>
                    <?php 
                    // Always display as hours with hourly rate, even for small amounts
                    echo number_format($late_hours, 2) . " hours × ₱" . number_format($hourly_rate, 2) . "/hour";
                    ?>
                </div>
                <div class="text-right text-red-600">-₱<?php echo number_format($late_deduction, 2); ?></div>
            </div>

            <!-- Expense Details Section - Add after the Late Deduction section -->
            <?php if ($reimbursement_amount > 0): ?>
                <!-- Expense Reimbursements Header -->
                <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200">
                    <div class="font-medium text-gray-700 col-span-3">Expense Reimbursements (<?php echo $expense_count; ?> items)</div>
                </div>
                
                <?php while ($expense = $expense_details_result->fetch_assoc()): ?>
                <!-- Individual Expense Items -->
                <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                    <div><?php echo htmlspecialchars($expense['expense_type']); ?></div>
                    <div><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></div>
                    <div class="text-right text-green-600">₱<?php echo number_format($expense['amount'], 2); ?></div>
                </div>
                <?php endwhile; ?>
                
                <!-- Reimbursement Subtotal -->
                <div class="grid grid-cols-3 gap-4 py-2 mb-2">
                    <div class="col-span-2 text-right font-medium">Total Reimbursements:</div>
                    <div class="text-right text-green-600 font-medium">₱<?php echo number_format($reimbursement_amount, 2); ?></div>
                </div>
            <?php endif; ?>

            <!-- Modify the existing Reimbursements row to only show if no detailed items -->
            <?php if ($reimbursement_amount > 0 && $expense_count == 0): ?>
            <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                <div>Reimbursements</div>
                <div>
                    <?php echo $expense_count; ?> items
                </div>
                <div class="text-right text-green-600">₱<?php echo number_format($reimbursement_amount, 2); ?></div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-3 gap-4 pt-4 font-semibold">
                <div class="col-span-2 text-right">Gross Earnings:</div>
                <div class="text-right text-blue-700">₱<?php echo number_format($gross_salary, 2); ?></div>
            </div>
        </div>

        <!-- Deductions -->
        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Deductions</h4>
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="flex justify-between py-2 border-b border-gray-200 border-dashed">
                <span>SSS Contribution</span>
                <span>₱<?php echo number_format($sss, 2); ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200 border-dashed">
                <span>PhilHealth</span>
                <span>₱<?php echo number_format($philhealth, 2); ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200 border-dashed">
                <span>Pag-IBIG</span>
                <span>₱<?php echo number_format($pagibig, 2); ?></span>
            </div>
            <div class="flex justify-between pt-3 font-semibold">
                <span>Total Deductions:</span>
                <span class="text-red-600">₱<?php echo number_format($total_deductions, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Salary Summary -->
    <div class="p-8 bg-gradient-to-r from-blue-50 to-blue-100">
        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Salary Summary</h4>
        <div class="bg-white rounded-lg p-6 shadow-sm">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Gross Earnings:</span>
                        <span class="font-medium">₱<?php echo number_format($gross_salary, 2); ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Total Deductions:</span>
                        <span class="font-medium text-red-600">₱<?php echo number_format($total_deductions, 2); ?></span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200">
                        <span class="font-bold text-gray-700">Net Pay:</span>
                        <span class="font-bold text-green-700 text-xl">₱<?php echo number_format($net_salary, 2); ?></span>
                    </div>
                </div>
                <div>
                    <h5 class="font-medium text-gray-700 mb-2">Payment Information</h5>
                    <p class="text-sm mb-1"><span class="text-gray-600">Payment Method:</span> Direct Deposit</p>
                    <p class="text-sm mb-1"><span class="text-gray-600">Pay Date:</span> <?php echo date('F d, Y'); ?></p>
                    <p class="text-sm"><span class="text-gray-600">Pay Period:</span> <?php echo $current_period; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Year to Date Summary -->
    <div class="px-8 pb-8">
        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Year to Date Summary</h4>
        <div class="bg-gray-50 rounded-lg p-4 shadow-sm border border-gray-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-3 bg-white rounded-lg shadow-sm">
                    <div class="text-xs text-gray-500 mb-1">Gross YTD</div>
                    <div class="text-lg font-bold text-gray-800">
                        ₱<?php echo number_format($gross_salary * 12, 2); ?>
                    </div>
                </div>
                <div class="p-3 bg-white rounded-lg shadow-sm">
                    <div class="text-xs text-gray-500 mb-1">Deductions YTD</div>
                    <div class="text-lg font-bold text-red-600">
                        ₱<?php echo number_format($total_deductions * 12, 2); ?>
                    </div>
                </div>
                <div class="p-3 bg-white rounded-lg shadow-sm">
                    <div class="text-xs text-gray-500 mb-1">Net Income YTD</div>
                    <div class="text-lg font-bold text-green-600">
                        ₱<?php echo number_format(($gross_salary - $total_deductions) * 12, 2); ?>
                    </div>
                </div>
                <div class="p-3 bg-white rounded-lg shadow-sm">
                    <div class="text-xs text-gray-500 mb-1">Total Hours YTD</div>
                    <div class="text-lg font-bold text-blue-600">
                        <?php echo number_format($total_hours_worked * 12, 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Salary -->
    <div class="bg-gradient-to-r from-green-50 to-green-100 p-6 flex justify-between items-center">
        <div>
            <h4 class="text-lg font-medium text-gray-800">Net Pay</h4>
        </div>
        <div class="text-3xl font-bold text-green-700">₱<?php echo number_format($net_salary, 2); ?></div>
    </div>

    <!-- Footer -->
    <div class="px-8 py-4 text-center text-xs text-gray-500">
        <p>This is an electronic payslip and is valid without signature.</p>
        <p>For questions regarding this payslip, please contact HR department.</p>
    </div>
</div>

<!-- Modal Actions -->
<div class="flex justify-center mt-6 space-x-4 pb-4">
    <button onclick="printPayslip()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2-2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        Print Payslip
    </button>
    <button onclick="downloadPDF()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        Download PDF
    </button>
</div>