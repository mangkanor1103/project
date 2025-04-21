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

// Fetch today's date
$date = date('Y-m-d');

// Check if today is a holiday or event
$event_sql = "SELECT event_name, event_type FROM calendar_events WHERE date = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("s", $date);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$event = $event_result->fetch_assoc();

$is_holiday = false;
$is_special_event = false;

if ($event) {
    if ($event['event_type'] === 'Holiday') {
        $is_holiday = true;
    } elseif ($event['event_type'] === 'Event') {
        $is_special_event = true;
    }
}

// Handle attendance submission
$success_message = $error_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_now = date('H:i:s'); // Current time

    if (isset($_POST['time_in'])) {
        // Log Time In
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance) {
            $error_message = "You have already logged your time-in for today.";
        } else {
            $insert_sql = "INSERT INTO attendance (employee_id, date, time_in, is_holiday, is_special_event) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issii", $user_id, $date, $time_now, $is_holiday, $is_special_event);
            $insert_stmt->execute();
            $success_message = "Time-in logged successfully at $time_now!";
        }
    } elseif (isset($_POST['time_out'])) {
        // Log Time Out
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance && $attendance['time_out'] === null) {
            $time_in = strtotime($attendance['time_in']);
            $time_out = strtotime($time_now);

            // Ensure time_out is after time_in
            if ($time_out > $time_in) {
                $seconds_diff = $time_out - $time_in;
                $hours_worked = $seconds_diff / 3600; // Convert seconds to hours

                // Calculate overtime, night hours, and other metrics
                $overtime_hours = max(0, $hours_worked - 8); // Overtime is any work beyond 8 hours
                $night_hours = 0;
                $night_overtime_hours = 0;

                // Check for night hours (10 PM to 6 AM)
                $night_start = strtotime("$date 22:00:00");
                $night_end = strtotime("$date 06:00:00 +1 day");

                if ($time_in < $night_end) {
                    $night_hours += min($time_out, $night_end) - max($time_in, strtotime("$date 00:00:00"));
                }
                if ($time_out > $night_start) {
                    $night_hours += min($time_out, strtotime("$date 23:59:59")) - max($time_in, $night_start);
                }
                $night_hours = $night_hours / 3600; // Convert seconds to hours
                $night_overtime_hours = max(0, $night_hours - 8);

                // Update attendance with time-out, hours worked, and metrics
                $update_sql = "
                    UPDATE attendance 
                    SET time_out = ?, hours_worked = ?, overtime_hours = ?, night_hours = ?, night_overtime_hours = ?, is_holiday = ?, is_special_event = ? 
                    WHERE employee_id = ? AND date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param(
                    "sdddiiiss", // Corrected type definition string
                    $time_now, 
                    $hours_worked, 
                    $overtime_hours, 
                    $night_hours, 
                    $night_overtime_hours, 
                    $is_holiday, 
                    $is_special_event, 
                    $user_id, 
                    $date
                );
                $update_stmt->execute();

                $success_message = "Time-out logged successfully at $time_now!";
            } else {
                $error_message = "Time-out cannot be earlier than time-in.";
            }
        } else {
            $error_message = "You need to log your time-in first, or you have already logged your time-out.";
        }
    }
}

// Mark absence if no time-in or time-out recorded by the end of the day
date_default_timezone_set('Asia/Manila'); // Set timezone
$current_hour = intval(date('H'));
$today = date('Y-m-d');

if ($current_hour >= 23 && empty($today_attendance)) {
    $mark_absent_sql = "
        INSERT INTO attendance (employee_id, date, is_absent) VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE is_absent = 1";
    $mark_absent_stmt = $conn->prepare($mark_absent_sql);
    $mark_absent_stmt->bind_param("is", $user_id, $today);
    $mark_absent_stmt->execute();
}

// Get today's attendance record if exists
$check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $user_id, $today);
$check_stmt->execute();
$today_attendance = $check_stmt->get_result()->fetch_assoc();

// Get recent attendance records (last 7 days)
$recent_sql = "SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 7";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_attendance = $recent_stmt->get_result();
?>

<?php include 'components/header.php'; ?>

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
                <!-- Attendance Actions Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6">
                    <div class="bg-gray-50 p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">Log Your Attendance</h3>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="time_in" 
                                        class="w-full flex items-center justify-center py-4 px-6 rounded-xl transition-all duration-300 
                                        <?php echo $today_attendance ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white shadow-lg hover:shadow-green-200'; ?>"
                                        <?php echo $today_attendance ? 'disabled' : ''; ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <div class="text-left">
                                        <span class="font-bold block">Time In</span>
                                        <span class="text-xs">Start your workday</span>
                                    </div>
                                </button>
                            </form>
                            
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="time_out" 
                                        class="w-full flex items-center justify-center py-4 px-6 rounded-xl transition-all duration-300 
                                        <?php echo (!$today_attendance || $today_attendance['time_out']) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white shadow-lg hover:shadow-red-200'; ?>"
                                        <?php echo (!$today_attendance || $today_attendance['time_out']) ? 'disabled' : ''; ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <div class="text-left">
                                        <span class="font-bold block">Time Out</span>
                                        <span class="text-xs">End your workday</span>
                                    </div>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
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

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Real-Time Clock
function updateClock() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = now.toLocaleDateString('en-US', options);
    const formattedTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

    document.getElementById('current-date').textContent = formattedDate;
    document.getElementById('real-time').textContent = formattedTime;
    
    // Update work duration if clocked in but not out
    <?php if ($today_attendance && !$today_attendance['time_out']): ?>
            const startTime = new Date('<?php echo date('Y-m-d') . " " . $today_attendance['time_in']; ?>');
            const timeDiff = now - startTime;
            const hours = Math.floor(timeDiff / (1000 * 60 * 60));
            const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);
        
            document.getElementById('work-duration').textContent = `${hours}h ${minutes}m ${seconds}s`;
    <?php endif; ?>
}

// Update the clock every second
setInterval(updateClock, 1000);
updateClock();

// SweetAlert2 Messages
<?php if (!empty($success_message)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $success_message; ?>',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        });
<?php endif; ?>

<?php if (!empty($error_message)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $error_message; ?>',
            confirmButtonText: 'OK'
        });
<?php endif; ?>
</script>