<?php
// Password to hash
$password = "admin123"; // Replace this with the plaintext password
// Generate hashed password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashedPassword;
?>

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

// Set rates (as per the image)
$basic_rate_per_day = 610; // Basic daily rate
$hourly_rate = $basic_rate_per_day / 8; // Hourly rate

// Rate multipliers
$overtime_multiplier = 1.25; // 125% of basic rate
$night_diff_multiplier = 0.10; // 10% of basic rate
$night_overtime_multiplier = 1.10; // 110% of overtime rate
$restday_multiplier = 1.30; // 130% of basic rate
$restday_night_diff_multiplier = 0.10; // 10% of rest day rate
$restday_overtime_multiplier = 1.30; // 130% of rest day rate
$restday_night_overtime_multiplier = 1.10; // 110% of rest day OT rate
$legal_holiday_multiplier = 2.00; // 200% of basic rate
$legal_holiday_night_diff_multiplier = 0.10; // 10% of legal holiday rate
$legal_holiday_overtime_multiplier = 1.30; // 130% of legal holiday rate
$legal_holiday_night_overtime_multiplier = 1.10; // 110% of legal holiday OT rate

// Calculate pay components using attendance data
$total_hours_worked = $attendance['total_hours_worked'] ?? 0;
$total_overtime_hours = $attendance['total_overtime_hours'] ?? 0;
$total_night_hours = $attendance['total_night_hours'] ?? 0;
$total_night_overtime_hours = $attendance['total_night_overtime_hours'] ?? 0;
$total_holiday_hours = $attendance['total_holiday_hours'] ?? 0;
$total_restday_hours = $attendance['total_restday_hours'] ?? 0;
$total_special_holiday_hours = $attendance['total_special_holiday_hours'] ?? 0;
$total_legal_holiday_hours = $attendance['total_legal_holiday_hours'] ?? 0;

// Regular pay
$regular_pay = $hourly_rate * $total_hours_worked;

// Overtime pay
$overtime_pay = $hourly_rate * $overtime_multiplier * $total_overtime_hours;

// Night differential pay
$night_diff_pay = $hourly_rate * $night_diff_multiplier * $total_night_hours;

// Night overtime pay
$night_overtime_pay = $hourly_rate * $overtime_multiplier * $night_overtime_multiplier * $total_night_overtime_hours;

// Rest day pay
$restday_pay = $hourly_rate * $restday_multiplier * $total_restday_hours;

// Rest day night differential pay
$restday_night_diff_pay = $hourly_rate * $restday_multiplier * $restday_night_diff_multiplier * $total_night_hours;

// Rest day overtime pay
$restday_overtime_pay = $hourly_rate * $restday_multiplier * $restday_overtime_multiplier * $total_overtime_hours;

// Rest day night overtime pay
$restday_night_overtime_pay = $hourly_rate * $restday_multiplier * $restday_overtime_multiplier * $restday_night_overtime_multiplier * $total_night_overtime_hours;

// Legal holiday pay
$legal_holiday_pay = $hourly_rate * $legal_holiday_multiplier * $total_legal_holiday_hours;

// Legal holiday night differential pay
$legal_holiday_night_diff_pay = $hourly_rate * $legal_holiday_multiplier * $legal_holiday_night_diff_multiplier * $total_night_hours;

// Legal holiday overtime pay
$legal_holiday_overtime_pay = $hourly_rate * $legal_holiday_multiplier * $legal_holiday_overtime_multiplier * $total_overtime_hours;

// Legal holiday night overtime pay
$legal_holiday_night_overtime_pay = $hourly_rate * $legal_holiday_multiplier * $legal_holiday_overtime_multiplier * $legal_holiday_night_overtime_multiplier * $total_night_overtime_hours;

// Calculate gross salary
$gross_salary = $regular_pay + $overtime_pay + $night_diff_pay + $night_overtime_pay +
    $restday_pay + $restday_night_diff_pay + $restday_overtime_pay + $restday_night_overtime_pay +
    $legal_holiday_pay + $legal_holiday_night_diff_pay + $legal_holiday_overtime_pay + $legal_holiday_night_overtime_pay;

// Deductions
$sss = 525; // Fixed SSS contribution
$philhealth = 250; // Fixed PhilHealth contribution
$pagibig = 100; // Fixed Pag-IBIG contribution
$total_deductions = $sss + $philhealth + $pagibig;

// Calculate net salary
$net_salary = $gross_salary - $total_deductions;

?>

