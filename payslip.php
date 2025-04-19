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

// Fetch employee details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    die("Employee record not found.");
}

// Fetch attendance details for the current month
$current_month = date('Y-m');
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
$attendance_stmt->bind_param("is", $user_id, $like_date);
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

// Deductions
$sss = 525; // Fixed SSS contribution
$philhealth = 250; // Fixed PhilHealth contribution
$pagibig = 100; // Fixed Pag-IBIG contribution
$total_deductions = $sss + $philhealth + $pagibig;

// Calculate gross and net salary
$gross_salary = $regular_pay + $overtime_pay;
$net_salary = $gross_salary - $total_deductions;

?>


<?php include 'components/header.php'; ?>
<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Payslip Header -->
        <div class="flex flex-col items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">Payslip</h1>
            <p class="text-gray-600">Pay Period: <?php echo $current_period; ?></p>
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
                            <?php echo htmlspecialchars($employee['full_name']); ?></h3>
                        <p class="text-blue-600"><?php echo htmlspecialchars($employee['job_position']); ?> •
                            <?php echo htmlspecialchars($employee['department']); ?></p>
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

                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Regular Hours</div>
                        <div><?php echo number_format($total_hours_worked, 2); ?> hrs × ₱<?php echo number_format($hourly_rate, 2); ?></div>
                        <div class="text-right">₱<?php echo number_format($regular_pay, 2); ?></div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-200 border-dashed">
                        <div>Overtime</div>
                        <div><?php echo number_format($total_overtime_hours, 2); ?> hrs × ₱<?php echo number_format($overtime_rate, 2); ?></div>
                        <div class="text-right">₱<?php echo number_format($overtime_pay, 2); ?></div>
                    </div>

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

                        <!-- Space for tax breakdown if needed -->
                        <div class="bg-gray-100 rounded-lg p-4">
                            <h5 class="font-medium text-gray-700 mb-2">Year to Date Summary</h5>
                            <div class="text-sm">
                                <div class="flex justify-between mb-1">
                                    <span>Gross YTD:</span>
                                    <span>₱<?php echo number_format($gross_salary * 12, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Deductions YTD:</span>
                                    <span>₱<?php echo number_format($total_deductions * 12, 2); ?></span>
                                </div>
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

        <!-- Actions -->
        <div class="flex justify-center mt-8 space-x-4">
            <a href="dashboard.php"
                class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition transform hover:-translate-y-1 hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
            <button onclick="printPayslip()"
                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
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