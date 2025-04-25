<!-- filepath: c:\xampp\htdocs\project\hr\payslip.php -->
<?php
session_start();
include '../config.php'; // Include database configuration
include 'check_permission.php';
// Check if the user is logged in as HR admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

requirePermission('manage_payslips');

// Define the current pay period (e.g., "April 2025")
$current_period = date('F Y');

// Fetch all employees
$sql = "SELECT * FROM employees";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die("No employees found.");
}

// Define the current month for attendance
$current_month = date('Y-m');
?>

<?php include '../components/header.php'; ?>
<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">Success!</p>
                <p>Payroll processed successfully. <?php echo $_GET['processed'] ?? 0; ?> expense reimbursements have been
                    processed.</p>
            </div>
        <?php endif; ?>

        <!-- Payslip Header -->
        <div class="flex flex-col items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">All Employee Payslips</h1>
            <p class="text-gray-600">Pay Period: <?php echo $current_period; ?></p>
        </div>
        <!-- Back to Dashboard button -->
        <div class="fixed bottom-6 left-6">
            <a href="hr_dashboard.php"
                class="bg-blue-600 text-white px-4 py-3 rounded-full shadow-lg hover:bg-blue-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <div class="flex justify-end mb-6">
            <button onclick="printAllPayslips()"
                class="bg-green-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-green-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2-2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print All Payslips (Horizontal Table)
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
                        SUM(is_late) AS days_late,
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

                // Fetch employee preferences
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

                // Set defaults if no preferences found
                $work_days_per_month = $preferences ? $preferences['work_days_per_month'] : 22;
                $payment_frequency = $preferences ? $preferences['payment_frequency'] : 'Semi-Monthly';
                $weekend_workday = $preferences ? $preferences['weekend_workday'] : null;

                // Determine if this is first or second half of month (for Semi-Monthly payments)
                $current_day = date('d');
                $is_first_half = $current_day <= 15;

                // Calculate working days for the month or half-month
                $total_working_days = $work_days_per_month;
                if ($payment_frequency == 'Semi-Monthly') {
                    $total_working_days = $work_days_per_month / 2;
                }

                // Calculate days absent (working days - days present)
                $days_absent = $total_working_days - $days_present;
                $days_absent = max(0, $days_absent); // Ensure no negative values
            
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

                // Salary calculations based on employee preferences
                $basic_salary = $employee['basic_salary']; // Monthly salary
            
                // For semi-monthly payments, adjust the salary to half of monthly
                $salary_multiplier = ($payment_frequency == 'Semi-Monthly') ? 0.5 : 1;
                $period_salary = $basic_salary * $salary_multiplier;

                // Calculate daily rate based on preferred work days
                $daily_rate = $basic_salary / $work_days_per_month;

                // Calculate hourly rate (8 working hours per day)
                $hourly_rate = $daily_rate / 8;

                // Calculate overtime and premium rates
                $overtime_rate = $hourly_rate * 1.25; // Overtime rate (25% premium)
                $night_diff_rate = $hourly_rate * 0.1; // Night differential (10% premium)
                $night_overtime_rate = $overtime_rate * 0.1; // Night differential on overtime
                $restday_premium_rate = $hourly_rate * 0.3; // Rest day premium (30% premium)
                $special_holiday_rate = $hourly_rate * 0.3; // Special holiday premium (30% premium)
                $legal_holiday_rate = $hourly_rate * 1.0; // Legal holiday premium (100% premium)
            
                // Calculate absences deduction (days absent * daily rate)
                $absence_deduction = $days_absent * $daily_rate;

                // Calculate late deduction (based on hourly rate)
                $late_deduction = ($total_late_minutes / 60) * $hourly_rate;

                // Calculate regular pay (base salary minus absences)
                $regular_pay = $period_salary - $absence_deduction;

                // Calculate premium pays
                $overtime_pay = $overtime_rate * $total_overtime_hours;
                $night_diff_pay = $night_diff_rate * $total_night_hours;
                $night_ot_pay = ($overtime_rate + $night_overtime_rate) * $total_night_overtime_hours;

                // For 26-day employees, calculate weekend rates based on preference
                if ($work_days_per_month == 26 && $weekend_workday) {
                    if ($weekend_workday == 'Saturday' || $weekend_workday == 'Sunday') {
                        // One weekend day is already in base pay for 26-day employees
                        $restday_pay = 0;
                    } else if ($weekend_workday == 'Both') {
                        // Both weekend days are in base pay
                        $restday_pay = 0;
                    }
                } else {
                    // For 22-day employees, all weekend work gets rest day premium
                    $restday_pay = $restday_premium_rate * $total_restday_hours;
                }

                // Calculate holiday pays
                $special_holiday_pay = $special_holiday_rate * $total_special_holiday_hours;
                $legal_holiday_pay = $legal_holiday_rate * $total_legal_holiday_hours;

                // Calculate gross salary
                $gross_salary = $regular_pay - $late_deduction +
                    $overtime_pay + $night_diff_pay + $night_ot_pay +
                    $restday_pay + $special_holiday_pay + $legal_holiday_pay +
                    $reimbursement_amount;

                // Deductions - SSS, PhilHealth, and Pag-IBIG
                // Use tiered contributions based on salary brackets
                $monthly_equivalent = $period_salary / $salary_multiplier; // Convert to monthly for brackets
            
                // SSS contribution (tiered)
                if ($monthly_equivalent <= 10000) {
                    $sss = 400 * $salary_multiplier;
                } else if ($monthly_equivalent <= 20000) {
                    $sss = 800 * $salary_multiplier;
                } else {
                    $sss = 1200 * $salary_multiplier;
                }

                // PhilHealth (3% of monthly salary, split with employer)
                $philhealth_rate = 0.03;
                $philhealth = min(max($monthly_equivalent * $philhealth_rate / 2, 300), 1800) * $salary_multiplier;

                // Pag-IBIG (2% with cap)
                $pagibig = min($monthly_equivalent * 0.02, 100) * $salary_multiplier;

                // Apply contributions only on first half for semi-monthly
                if ($payment_frequency == 'Semi-Monthly' && !$is_first_half) {
                    $sss = 0;
                    $philhealth = 0;
                    $pagibig = 0;
                }

                $total_deductions = $sss + $philhealth + $pagibig;

                // Calculate net salary
                $net_salary = $gross_salary - $total_deductions;
                ?>
                <!-- Payslip Card -->
                <div class="bg-white shadow-lg rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($employee['full_name']); ?>
                        </h2>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($employee['job_position']); ?> •
                            <?php echo htmlspecialchars($employee['department']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php echo $payment_frequency; ?> •
                            <?php echo $work_days_per_month; ?> days/month •
                            <?php echo $days_present; ?> days present
                        </p>
                    </div>
                    <div class="p-6 bg-gray-50">
                        <p><strong>Period Salary:</strong> ₱<?php echo number_format($period_salary, 2); ?></p>

                        <?php if ($absence_deduction > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Absences (<?php echo $days_absent; ?> days):</strong></span>
                                <span class="text-red-600 font-medium">-
                                    ₱<?php echo number_format($absence_deduction, 2); ?></span>
                            </p>
                        <?php endif; ?>

                        <?php if ($late_deduction > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Late (<?php echo $total_late_minutes; ?> mins):</strong></span>
                                <span class="text-red-600 font-medium">-
                                    ₱<?php echo number_format($late_deduction, 2); ?></span>
                            </p>
                        <?php endif; ?>

                        <?php if ($overtime_pay > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Overtime (<?php echo number_format($total_overtime_hours, 2); ?>
                                        hrs):</strong></span>
                                <span class="text-green-600 font-medium">+
                                    ₱<?php echo number_format($overtime_pay, 2); ?></span>
                            </p>
                        <?php endif; ?>

                        <?php if ($night_diff_pay > 0 || $night_ot_pay > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Night Differential:</strong></span>
                                <span class="text-green-600 font-medium">+
                                    ₱<?php echo number_format($night_diff_pay + $night_ot_pay, 2); ?></span>
                            </p>
                        <?php endif; ?>

                        <?php if ($reimbursement_amount > 0): ?>
                            <p class="flex justify-between items-center mt-1">
                                <span><strong>Reimbursements (<?php echo $expense_count; ?>):</strong></span>
                                <span class="text-green-600 font-medium">+
                                    ₱<?php echo number_format($reimbursement_amount, 2); ?></span>
                            </p>
                        <?php endif; ?>

                        <p class="mt-3 pt-2 border-t"><strong>Gross Pay:</strong>
                            ₱<?php echo number_format($gross_salary, 2); ?></p>

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
                                <span
                                    class="font-bold text-lg text-blue-700">₱<?php echo number_format($net_salary, 2); ?></span>
                            </p>
                        </div>

                        <button onclick="viewPayslip(<?php echo $employee['id']; ?>)"
                            class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                            View Details
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="payslipModal"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50 overflow-auto">
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

                setTimeout(function () {
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
    document.getElementById('payslipModal').addEventListener('click', function (event) {
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