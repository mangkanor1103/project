<?php
session_start();
include 'config.php'; // Include database configuration

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Define the current pay period (e.g., "April 2025")
$current_period = date('F Y');

// Fetch employee details including work preferences
$user_id = $_SESSION['user_id'];
$sql = "SELECT e.*, 
               COALESCE(ep.work_days_per_month, 22) AS work_days_per_month,
               COALESCE(ep.payment_frequency, 'Monthly') AS payment_frequency,
               COALESCE(ep.pay_day_1, 30) AS pay_day_1,
               COALESCE(ep.pay_day_2, 15) AS pay_day_2,
               ep.weekend_workday
        FROM employees e
        LEFT JOIN employee_preferences ep ON e.id = ep.employee_id
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    die("Employee record not found.");
}

// Determine if this payslip is for the first half or second half of the month (for semi-monthly payments)
$is_first_half = date('d') <= 15;
$pay_period_text = "";

if ($employee['payment_frequency'] == 'Semi-Monthly') {
    if ($is_first_half) {
        $pay_period_text = "1st - 15th " . date('F Y');
    } else {
        $pay_period_text = "16th - " . date('t') . " " . date('F Y');
    }
} else {
    $pay_period_text = "1st - " . date('t') . " " . date('F Y'); 
}

// Fetch attendance details for the current month or pay period
$current_month = date('Y-m');

if ($employee['payment_frequency'] == 'Semi-Monthly') {
    if ($is_first_half) {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-15');
    } else {
        $start_date = date('Y-m-16');
        $end_date = date('Y-m-t');
    }
    
    // Count working days in the period (excluding weekends)
    $working_days_in_period = 0;
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        $day_of_week = $current_date->format('w'); // 0 for Sunday, 6 for Saturday
        
        // For 22 days/month (standard weekday work schedule)
        if ($employee['work_days_per_month'] == 22) {
            if ($day_of_week != 0 && $day_of_week != 6) {
                $working_days_in_period++;
            }
        } 
        // For 26 days/month (includes weekend days)
        else if ($employee['work_days_per_month'] == 26) {
            if ($day_of_week != 0 && $day_of_week != 6) {
                $working_days_in_period++; // Weekdays always count
            } else {
                // Add weekend day if it matches their preference
                if (($day_of_week == 6 && ($employee['weekend_workday'] == 'Saturday' || $employee['weekend_workday'] == 'Both')) ||
                    ($day_of_week == 0 && ($employee['weekend_workday'] == 'Sunday' || $employee['weekend_workday'] == 'Both'))) {
                    $working_days_in_period++;
                }
            }
        }
        $current_date->modify('+1 day');
    }
    
    // Get attendance records for the period
    $attendance_sql = "
        SELECT date, time_in, time_out, hours_worked, 
            overtime_hours, night_hours, night_overtime_hours,
            holiday_hours, restday_hours, special_holiday_hours, legal_holiday_hours,
            late_minutes
        FROM attendance 
        WHERE employee_id = ? AND date BETWEEN ? AND ?";
        
    $attendance_stmt = $conn->prepare($attendance_sql);
    $attendance_stmt->bind_param("iss", $user_id, $start_date, $end_date);
} else {
    // For monthly payments, use the whole month
    // Count working days in the month
    $working_days_in_period = 0;
    $days_in_month = date('t');
    
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = date('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        $day_of_week = date('w', strtotime($date)); // 0 for Sunday, 6 for Saturday
        
        // For 22 days/month
        if ($employee['work_days_per_month'] == 22) {
            if ($day_of_week != 0 && $day_of_week != 6) {
                $working_days_in_period++;
            }
        } 
        // For 26 days/month
        else if ($employee['work_days_per_month'] == 26) {
            if ($day_of_week != 0 && $day_of_week != 6) {
                $working_days_in_period++; // Weekdays always count
            } else {
                // Add weekend day if it matches their preference
                if (($day_of_week == 6 && ($employee['weekend_workday'] == 'Saturday' || $employee['weekend_workday'] == 'Both')) ||
                    ($day_of_week == 0 && ($employee['weekend_workday'] == 'Sunday' || $employee['weekend_workday'] == 'Both'))) {
                    $working_days_in_period++;
                }
            }
        }
    }
    
    // Get all attendance records for the month
    $attendance_sql = "
        SELECT date, time_in, time_out, hours_worked, 
            overtime_hours, night_hours, night_overtime_hours,
            holiday_hours, restday_hours, special_holiday_hours, legal_holiday_hours,
            late_minutes
        FROM attendance 
        WHERE employee_id = ? AND date LIKE ?";
        
    $attendance_stmt = $conn->prepare($attendance_sql);
    $like_date = $current_month . '%';
    $attendance_stmt->bind_param("is", $user_id, $like_date);
}

$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Process attendance records
$attendance_records = [];
$days_present = 0;
$total_hours_worked = 0;
$total_overtime_hours = 0;
$total_night_hours = 0;
$total_night_overtime_hours = 0;
$total_holiday_hours = 0;
$total_restday_hours = 0;
$total_special_holiday_hours = 0;
$total_legal_holiday_hours = 0;
$total_late_minutes = 0;
$present_dates = []; // Track dates when employee was present

while ($record = $attendance_result->fetch_assoc()) {
    $attendance_records[$record['date']] = $record;
    $present_dates[] = $record['date'];
    $days_present++;
    
    // Add up hours based on morning and afternoon shifts
    $hours_worked = $record['hours_worked'] ?? 0;
    $total_hours_worked += $hours_worked;
    
    // Add up other hours
    $total_overtime_hours += $record['overtime_hours'] ?? 0;
    $total_night_hours += $record['night_hours'] ?? 0;
    $total_night_overtime_hours += $record['night_overtime_hours'] ?? 0;
    $total_holiday_hours += $record['holiday_hours'] ?? 0;
    $total_restday_hours += $record['restday_hours'] ?? 0;
    $total_special_holiday_hours += $record['special_holiday_hours'] ?? 0;
    $total_legal_holiday_hours += $record['legal_holiday_hours'] ?? 0;
    $total_late_minutes += $record['late_minutes'] ?? 0;
}

// Calculate absences (working days they didn't show up)
$absent_dates = [];
$current_date = new DateTime($employee['payment_frequency'] == 'Semi-Monthly' ? $start_date : date('Y-m-01'));
$end_date_obj = new DateTime($employee['payment_frequency'] == 'Semi-Monthly' ? $end_date : date('Y-m-t'));

while ($current_date <= $end_date_obj) {
    $current_date_str = $current_date->format('Y-m-d');
    $day_of_week = $current_date->format('w'); // 0 for Sunday, 6 for Saturday
    
    $is_work_day = false;
    
    // For 22 days/month (standard weekday work schedule)
    if ($employee['work_days_per_month'] == 22) {
        if ($day_of_week != 0 && $day_of_week != 6) {
            $is_work_day = true;
        }
    } 
    // For 26 days/month (includes weekend days)
    else if ($employee['work_days_per_month'] == 26) {
        if ($day_of_week != 0 && $day_of_week != 6) {
            $is_work_day = true;
        } else {
            // Count weekend day if it matches their preference
            if (($day_of_week == 6 && ($employee['weekend_workday'] == 'Saturday' || $employee['weekend_workday'] == 'Both')) ||
                ($day_of_week == 0 && ($employee['weekend_workday'] == 'Sunday' || $employee['weekend_workday'] == 'Both'))) {
                $is_work_day = true;
            }
        }
    }
    
    // If this is a work day and employee was not present, mark as absent
    // Only mark as absent if no attendance record exists for this day
    if ($is_work_day && !in_array($current_date_str, $present_dates)) {
        $absent_dates[] = $current_date_str;
    }
    
    $current_date->modify('+1 day');
}

$days_absent = count($absent_dates);

// Salary calculations based on employee preferences
$basic_salary = $employee['basic_salary']; // Monthly salary from DB

// Calculate daily rate based on preferred work days
$days_per_month = $employee['work_days_per_month']; // 22 or 26 days
$daily_rate = $basic_salary / $days_per_month;

// Calculate hourly rate (8 working hours per day: 4 morning + 4 afternoon)
// For 8-5 workday with lunch break 12-1
$hourly_rate = $daily_rate / 8; 

// Calculate overtime and premium rates
$overtime_rate = $hourly_rate * 1.25; // Overtime rate (25% premium)
$night_diff_rate = $hourly_rate * 0.1; // Night differential (10% premium)
$night_overtime_rate = $overtime_rate * 0.1; // Night differential on overtime (10% premium)
$restday_premium_rate = $hourly_rate * 0.3; // Rest day premium (30%)
$special_holiday_rate = $hourly_rate * 0.3; // Special holiday premium (30%)
$legal_holiday_rate = $hourly_rate * 1.0; // Legal holiday premium (100%)

// For absences, deduct the full daily rate
$absence_deduction = $days_absent * $daily_rate;

// Apply late deduction only for days when employee was present
// Late deduction should NEVER be applied to absent days
$late_deduction = ($total_late_minutes / 60) * $hourly_rate; // Convert minutes to hours

// For semi-monthly payments, adjust the salary to half of monthly
$salary_multiplier = ($employee['payment_frequency'] == 'Semi-Monthly') ? 0.5 : 1;
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

// Get total approved expenses amount
$reimbursement_amount = $expenses['total_reimbursement'] ?? 0;
$expense_count = $expenses['expense_count'] ?? 0;

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
if ($employee['payment_frequency'] == 'Semi-Monthly') {
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

// Convert late minutes to hours, minutes format for display
$total_hours = floor($total_hours_worked);
$total_minutes = floor(($total_hours_worked - $total_hours) * 60);
?>

<?php include 'components/header.php'; ?>
<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Payslip Header -->
        <div class="flex flex-col items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">Payslip</h1>
            <p class="text-gray-600">Pay Period: <?php echo $pay_period_text; ?></p>
        </div>

        <!-- Payslip Card -->
        <div id="payslip-content" class="bg-white shadow-xl rounded-xl border border-gray-200 overflow-hidden">
            <!-- Payslip Header with company logo placeholder -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold">Company Name</h2>
                        <p class="text-sm text-blue-100">123 Business Avenue, City, Country</p>
                    </div>
                    <div>
                        <!-- Add company logo here if available -->
                        <div
                            class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-blue-600 font-bold">
                            LOGO</div>
                    </div>
                </div>
            </div>

            <!-- Employee Information Section -->
            <div class="px-8 py-6 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center space-x-4 mb-4">
                    <!-- Employee photo -->
                    <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 border-2 border-blue-500">
                        <?php if (!empty($employee['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($employee['image']); ?>"
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
                        <p class="text-blue-600"><?php echo htmlspecialchars($employee['job_position']); ?> •
                            <?php echo htmlspecialchars($employee['department']); ?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Personal
                            Information</h4>
                        <div class="space-y-2">
                            <p><span class="font-medium text-gray-600">Employee ID:</span> <span
                                    class="text-gray-800">#<?php echo str_pad($employee['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </p>
                            <p><span class="font-medium text-gray-600">Email:</span> <span
                                    class="text-gray-800"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></span>
                            </p>
                            <p><span class="font-medium text-gray-600">Date Hired:</span> <span
                                    class="text-gray-800"><?php echo htmlspecialchars($employee['date_hired'] ?? 'N/A'); ?></span>
                            </p>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Payment Details
                        </h4>
                        <p><span class="font-medium text-gray-600">Payment Method:</span> <span
                                class="text-gray-800">Direct Deposit</span></p>
                        <p><span class="font-medium text-gray-600">Pay Date:</span> <span
                                class="text-gray-800"><?php echo date('F d, Y'); ?></span></p>
                        <p><span class="font-medium text-gray-600">Payment Frequency:</span> <span
                                class="text-gray-800"><?php echo $employee['payment_frequency']; ?></span></p>
                        <p><span class="font-medium text-gray-600">Work Schedule:</span> <span
                                class="text-gray-800"><?php echo $employee['work_days_per_month']; ?> days/month</span></p>
                        <?php if ($employee['work_days_per_month'] == 26 && !empty($employee['weekend_workday'])): ?>
                        <p><span class="font-medium text-gray-600">Weekend Day:</span> <span
                                class="text-gray-800"><?php echo $employee['weekend_workday']; ?></span></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Work Summary</h4>
                        <p><span class="font-medium text-gray-600">Attendance:</span> 
                            <span class="text-green-600"><?php echo $days_present; ?> days present</span> / 
                            <span class="text-red-600"><?php echo $days_absent; ?> days absent</span>
                        </p>
                        <p><span class="font-medium text-gray-600">Total Hours Worked:</span>
                            <?php echo "$total_hours hrs $total_minutes mins"; ?>
                        </p>
                        <p><span class="font-medium text-gray-600">Late Time:</span>
                            <?php echo "$late_hours_display hrs $late_minutes_display mins"; ?>
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Rate Information</h4>
                        <p><span class="font-medium text-gray-600">Daily Rate:</span> ₱<?php echo number_format($daily_rate, 2); ?></p>
                        <p><span class="font-medium text-gray-600">Hourly Rate:</span> ₱<?php echo number_format($hourly_rate, 2); ?></p>
                        <p><span class="font-medium text-gray-600">Overtime Rate:</span> ₱<?php echo number_format($overtime_rate, 2); ?></p>
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

                    <!-- Regular Pay -->
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Regular Pay</div>
                        <div>Base Salary</div>
                        <div class="text-right">₱<?php echo number_format($base_salary, 2); ?></div>
                    </div>

                    <!-- Absences Deduction -->
                    <?php if ($days_absent > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Absence Deduction</div>
                        <div><?php echo $days_absent; ?> days × ₱<?php echo number_format($daily_rate, 2); ?></div>
                        <div class="text-right text-red-600">-₱<?php echo number_format($absence_deduction, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Late Deduction -->
                    <?php if ($total_late_minutes > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Late Deduction</div>
                        <div>
                            <?php echo "$late_hours_display hrs $late_minutes_display mins"; ?> × 
                            ₱<?php echo number_format($hourly_rate, 2); ?>
                        </div>
                        <div class="text-right text-red-600">-₱<?php echo number_format($late_deduction, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Overtime -->
                    <?php if ($total_overtime_hours > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Overtime</div>
                        <div>
                            <?php echo number_format($total_overtime_hours, 2); ?> hrs ×
                            ₱<?php echo number_format($overtime_rate, 2); ?>
                        </div>
                        <div class="text-right">₱<?php echo number_format($overtime_pay, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Night Differential -->
                    <?php if ($total_night_hours > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Night Differential</div>
                        <div>
                            <?php echo number_format($total_night_hours, 2); ?> hrs ×
                            ₱<?php echo number_format($hourly_rate * 0.1, 2); ?> (10%)
                        </div>
                        <div class="text-right">₱<?php echo number_format($night_diff_pay, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Night Overtime -->
                    <?php if ($total_night_overtime_hours > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Night Overtime</div>
                        <div><?php echo number_format($total_night_overtime_hours, 2); ?> hrs ×
                            ₱<?php echo number_format($overtime_rate * 0.1, 2); ?> (10%)</div>
                        <div class="text-right">₱<?php echo number_format($night_ot_pay, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Holiday Hours -->
                    <?php if ($total_holiday_hours > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Holiday Hours</div>
                        <div><?php echo number_format($total_holiday_hours, 2); ?> hrs ×
                            ₱<?php echo number_format($hourly_rate, 2); ?> (100%)</div>
                        <div class="text-right">₱<?php echo number_format($holiday_pay, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Rest Day Hours -->
                    <?php if ($total_restday_hours > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Rest Day Hours</div>
                        <div>
                            <?php echo number_format($total_restday_hours, 2); ?> hrs ×
                            <?php if($days_per_month == 26 && $employee['weekend_workday'] == 'Saturday'): ?>
                                ₱<?php echo number_format($hourly_rate, 2); ?> (Already in base)
                            <?php else: ?>
                                ₱<?php echo number_format($hourly_rate * 0.3, 2); ?> (30% Premium)
                            <?php endif; ?>
                        </div>
                        <div class="text-right">₱<?php echo number_format($restday_pay, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Special Holiday -->
                    <?php if ($total_special_holiday_hours > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Special Holiday</div>
                        <div><?php echo number_format($total_special_holiday_hours, 2); ?> hrs ×
                            ₱<?php echo number_format($hourly_rate * 0.3, 2); ?> (30%)</div>
                        <div class="text-right">₱<?php echo number_format($special_holiday_pay, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Legal Holiday -->
                    <?php if ($total_legal_holiday_hours > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Legal Holiday</div>
                        <div><?php echo number_format($total_legal_holiday_hours, 2); ?> hrs ×
                            ₱<?php echo number_format($hourly_rate, 2); ?> (100%)</div>
                        <div class="text-right">₱<?php echo number_format($legal_holiday_pay, 2); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Reimbursements -->
                    <?php if ($reimbursement_amount > 0): ?>
                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Reimbursements</div>
                        <div><?php echo $expense_count; ?> items</div>
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
                    <?php if ($sss > 0): ?>
                    <div class="flex justify-between py-2 border-b border-gray-200 border-dashed">
                        <span>SSS Contribution</span>
                        <span>₱<?php echo number_format($sss, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($philhealth > 0): ?>
                    <div class="flex justify-between py-2 border-b border-gray-200 border-dashed">
                        <span>PhilHealth</span>
                        <span>₱<?php echo number_format($philhealth, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pagibig > 0): ?>
                    <div class="flex justify-between py-2 border-b border-gray-200 border-dashed">
                        <span>Pag-IBIG</span>
                        <span>₱<?php echo number_format($pagibig, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between pt-3 font-semibold">
                        <span>Total Deductions:</span>
                        <span class="text-red-600">₱<?php echo number_format($total_deductions, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Salary summary section -->
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
                                <span
                                    class="font-medium text-red-600">₱<?php echo number_format($total_deductions, 2); ?></span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-200">
                                <span class="font-bold text-gray-700">Net Pay:</span>
                                <span
                                    class="font-bold text-green-700 text-xl">₱<?php echo number_format($net_salary, 2); ?></span>
                            </div>
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-700 mb-2">Payment Information</h5>
                            <p class="text-sm mb-1"><span class="text-gray-600">Payment Method:</span> Direct Deposit
                            </p>
                            <p class="text-sm mb-1"><span class="text-gray-600">Pay Date:</span>
                                <?php echo date('F d, Y'); ?></p>
                            <p class="text-sm mb-1"><span class="text-gray-600">Pay Period:</span>
                                <?php echo $pay_period_text; ?></p>
                            <p class="text-sm"><span class="text-gray-600">Daily Rate:</span>
                                ₱<?php echo number_format($daily_rate, 2); ?> (<?php echo $days_per_month; ?>-day month)</p>
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

        <!-- Actions -->
        <div class="flex justify-center mt-8 space-x-4">
            <a href="dashboard.php"
                class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition transform hover:-translate-y-1 hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
            <button onclick="printPayslip()"
                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2-2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Payslip
            </button>
            <button onclick="downloadPDF()"
                class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition transform hover:-translate-y-1 hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download PDF
            </button>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>

<script>
    // Print Payslip Function
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
        printWindow.document.write('<div class="header"><h1>Payslip - ' + '<?php echo $current_period; ?>' + '</h1>');
        printWindow.document.write('<p>Employee: ' + '<?php echo htmlspecialchars($employee['full_name']); ?>' + '</p></div>');
        printWindow.document.write(printContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        setTimeout(function () {
            printWindow.print();
        }, 500);
    }

    // Download PDF Function (this is a placeholder - you'll need a PDF library implementation)
    function downloadPDF() {
        alert("PDF download functionality requires a server-side PDF generation library. Please implement with a library like FPDF or TCPDF.");
        // In a real implementation, you would use a library like jsPDF or make a server request to generate PDF
    }
</script>