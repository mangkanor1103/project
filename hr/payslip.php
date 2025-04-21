<!-- filepath: c:\xampp\htdocs\project\hr\payslip.php -->
<?php
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2-2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
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

                // Fetch approved expenses for this employee in the current month that should be reimbursed
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

                // Get total approved expenses amount
                $reimbursement_amount = $expenses['total_reimbursement'] ?? 0;

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

                // Gross salary calculation including expense reimbursements
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
                <!-- Payslip Card -->
                <div class="bg-white shadow-lg rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($employee['full_name']); ?></h2>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($employee['job_position']); ?> • <?php echo htmlspecialchars($employee['department']); ?></p>
                    </div>
                    <div class="p-6 bg-gray-50">
                        <p><strong>Basic Salary:</strong> ₱<?php echo number_format($gross_salary - $reimbursement_amount, 2); ?></p>
                        
                        <?php if ($reimbursement_amount > 0): ?>
                            <p class="flex justify-between items-center mt-2">
                                <span><strong>Expense Reimbursements:</strong></span>
                                <span class="text-green-600 font-medium">+ ₱<?php echo number_format($reimbursement_amount, 2); ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <p class="mt-2"><strong>Gross Salary:</strong> ₱<?php echo number_format($gross_salary, 2); ?></p>
                        <p><strong>Total Deductions:</strong> ₱<?php echo number_format($total_deductions, 2); ?></p>
                        
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