<?php
session_start();
include 'config.php'; // Include database configuration

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch employee details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Calculate salary components
$date = date('Y-m'); // Current month
$attendance_sql = "SELECT SUM(hours_worked) AS total_hours, SUM(overtime_hours) AS total_overtime FROM attendance WHERE employee_id = ? AND date LIKE ?";
$attendance_stmt = $conn->prepare($attendance_sql);
$like_date = $date . '%';
$attendance_stmt->bind_param("is", $user_id, $like_date);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result()->fetch_assoc();

$total_hours = $attendance_result['total_hours'] ?? 0;
$total_overtime = $attendance_result['total_overtime'] ?? 0;

// Salary calculations
$basic_salary = $employee['basic_salary'];
$hourly_rate = $basic_salary / 160; // Assuming 160 work hours in a month
$overtime_rate = $hourly_rate * 1.5;

$regular_pay = $hourly_rate * $total_hours;
$overtime_pay = $overtime_rate * $total_overtime;
$gross_salary = $regular_pay + $overtime_pay;

// Deductions
$sss = 0.045 * $basic_salary; // Example SSS contribution (4.5%)
$philhealth = 0.03 * $basic_salary; // Example PhilHealth contribution (3%)
$pagibig = 100; // Fixed Pag-IBIG contribution
$total_deductions = $sss + $philhealth + $pagibig;

// Net salary
$net_salary = $gross_salary - $total_deductions;
?>

<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-8">Payslip for <?php echo htmlspecialchars($employee['full_name']); ?></h1>
        
        <!-- Payslip Card -->
        <div id="payslip-content" class="bg-white shadow-lg rounded-lg p-6 max-w-4xl mx-auto">
            <h2 class="text-xl font-bold text-gray-700 mb-4">Payslip Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Employee Information -->
                <div>
                    <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee['id']); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($employee['full_name']); ?></p>
                    <p><strong>Job Position:</strong> <?php echo htmlspecialchars($employee['job_position']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
                </div>
                <!-- Salary Information -->
                <div>
                    <p><strong>Basic Salary:</strong> ₱<?php echo number_format($basic_salary, 2); ?></p>
                    <p><strong>Hours Worked:</strong> <?php echo number_format($total_hours, 2); ?> hours</p>
                    <p><strong>Overtime Hours:</strong> <?php echo number_format($total_overtime, 2); ?> hours</p>
                    <p><strong>Gross Salary:</strong> ₱<?php echo number_format($gross_salary, 2); ?></p>
                </div>
            </div>
            
            <!-- Deductions -->
            <div class="mt-6">
                <h3 class="text-lg font-bold text-gray-700 mb-2">Deductions</h3>
                <ul>
                    <li><strong>SSS Contribution:</strong> ₱<?php echo number_format($sss, 2); ?></li>
                    <li><strong>PhilHealth Contribution:</strong> ₱<?php echo number_format($philhealth, 2); ?></li>
                    <li><strong>Pag-IBIG Contribution:</strong> ₱<?php echo number_format($pagibig, 2); ?></li>
                </ul>
                <p class="mt-2"><strong>Total Deductions:</strong> ₱<?php echo number_format($total_deductions, 2); ?></p>
            </div>
            
            <!-- Net Salary -->
            <div class="mt-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                <p><strong>Net Salary:</strong> ₱<?php echo number_format($net_salary, 2); ?></p>
            </div>
        </div>

        <!-- Actions -->
        <div class="text-center mt-8 space-x-4">
            <a href="dashboard.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition transform hover:-translate-y-1 hover:scale-105">
                Back to Dashboard
            </a>
            <button onclick="printPayslip()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:scale-105">
                Print Payslip
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
    printWindow.document.write('<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>