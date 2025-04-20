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

<main class="bg-gradient-to-b from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Back to Dashboard Link -->
        <div class="mb-8">
            <a href="dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <div class="flex flex-col items-center mb-10">
            <h1 class="text-4xl font-bold text-blue-700 mb-2">Attendance Tracker</h1>
            <div class="h-1 w-24 bg-blue-600 rounded-full mb-2"></div>
            <p class="text-gray-600 text-lg">Track your daily attendance records</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Profile & Clock -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6">
                    <!-- Employee Profile Section -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-full overflow-hidden bg-white border-2 border-white">
                                <?php if (!empty($employee['image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($employee['image']); ?>" 
                                        class="w-full h-full object-cover" 
                                        alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <h2 class="text-xl font-bold"><?php echo htmlspecialchars($employee['full_name']); ?></h2>
                                <p class="text-blue-100"><?php echo htmlspecialchars($employee['job_position']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Real-Time Clock -->
                    <div class="p-6 text-center">
                        <h3 class="text-sm uppercase text-gray-500 font-semibold tracking-wide mb-4">Current Time</h3>
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200 shadow-inner">
                            <div id="current-date" class="text-gray-700 mb-1 text-sm"></div>
                            <div id="real-time" class="text-3xl font-bold text-blue-700 font-mono tracking-wider"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Status Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="bg-gray-50 p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">Today's Status</h3>
                    </div>
                    
                    <div class="p-6">
                        <?php if ($today_attendance): ?>
                            <div class="flex items-center mb-4">
                                <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                <span class="text-gray-700 font-medium">Present Today</span>
                            </div>
                            <div class="space-y-3">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <span class="text-sm text-gray-500">Time In:</span>
                                    <span class="block text-lg font-semibold text-gray-800">
                                        <?php echo date('h:i A', strtotime($today_attendance['time_in'])); ?>
                                    </span>
                                </div>
                            
                                <?php if ($today_attendance['time_out']): ?>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="text-sm text-gray-500">Time Out:</span>
                                        <span class="block text-lg font-semibold text-gray-800">
                                            <?php echo date('h:i A', strtotime($today_attendance['time_out'])); ?>
                                        </span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="text-sm text-gray-500">Hours Worked:</span>
                                        <span class="block text-lg font-semibold text-green-600">
                                            <?php
                                            $hours = floor($today_attendance['hours_worked']);
                                            $minutes = round(($today_attendance['hours_worked'] - $hours) * 60);
                                            echo "$hours hrs $minutes mins";
                                            ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-blue-50 rounded-lg p-3">
                                        <span class="text-sm text-blue-600">Currently Working</span>
                                        <div class="flex items-center mt-1">
                                            <div class="animate-ping mr-2 h-2 w-2 rounded-full bg-blue-600"></div>
                                            <span class="text-gray-700" id="work-duration"></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-500">You haven't logged in for today</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Attendance Actions & History -->
            <div class="lg:col-span-2">
                <!-- Recent Attendance History Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="bg-gray-50 p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">Recent Attendance History</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime Hours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Night Hours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Holiday Hours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($recent_attendance->num_rows > 0): ?>
                                    <?php while ($row = $recent_attendance->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo date('M d, Y (D)', strtotime($row['date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '—'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '—'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $row['hours_worked'] ? number_format($row['hours_worked'], 2) . ' hrs' : '—'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $row['overtime_hours'] ? number_format($row['overtime_hours'], 2) . ' hrs' : '—'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $row['night_hours'] ? number_format($row['night_hours'], 2) . ' hrs' : '—'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $row['holiday_hours'] ? number_format($row['holiday_hours'], 2) . ' hrs' : '—'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $row['is_absent'] ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Absent</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Present</span>'; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No attendance records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>