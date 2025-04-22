<?php
session_start();
include 'config.php'; // Include database configuration

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
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

// Define standard work hours
$morning_start_time = '08:00:00'; 
$lunch_start_time = '12:00:00';
$afternoon_start_time = '13:00:00';
$regular_end_time = '17:00:00';
$night_diff_start_time = '22:00:00'; // Night differential starts at 10 PM

// Current time components
$current_hour = date('H');
$current_time = date('H:i:s');
$current_time_timestamp = strtotime($current_time);

// Time thresholds in timestamps
$morning_cutoff = strtotime('10:00:00'); // Cutoff for morning time-in (10 AM)
$morning_start = strtotime($morning_start_time);
$lunch_start = strtotime($lunch_start_time);
$afternoon_start = strtotime($afternoon_start_time);
$regular_end = strtotime($regular_end_time);
$night_diff_start = strtotime($night_diff_start_time);

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
        // Morning Time In (First shift start)
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance) {
            $error_message = "You have already logged your time-in for today.";
        } else {
            // Check if it's too late for a regular time-in (after 10 AM)
            $current_timestamp = strtotime($time_now);
            if ($current_timestamp > $morning_cutoff) {
                // Allow late check-in but flag it for HR review
                $is_late = 1;
                $insert_sql = "INSERT INTO attendance (employee_id, date, time_in, is_holiday, is_special_event, is_late) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("issiib", $user_id, $date, $time_now, $is_holiday, $is_special_event, $is_late);
                $insert_stmt->execute();
                $success_message = "Late time-in recorded at $time_now. Please note this will be flagged for HR review.";
            } else {
                // Normal time-in before cutoff
                $insert_sql = "INSERT INTO attendance (employee_id, date, time_in, is_holiday, is_special_event) 
                              VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("issii", $user_id, $date, $time_now, $is_holiday, $is_special_event);
                $insert_stmt->execute();
                $success_message = "Morning time-in logged successfully at $time_now!";
            }
        }
    } 
    elseif (isset($_POST['lunch_out'])) {
        // Pre-lunch Time Out (First shift end)
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance && $attendance['lunch_out'] === null) {
            $time_in = strtotime($attendance['time_in']);
            $lunch_out = strtotime($time_now);

            // Ensure lunch_out is after time_in
            if ($lunch_out > $time_in) {
                // Calculate morning shift hours
                $morning_shift_seconds = $lunch_out - $time_in;
                $morning_hours = $morning_shift_seconds / 3600; // Convert to hours

                // Update attendance with lunch_out
                $update_sql = "UPDATE attendance SET lunch_out = ?, morning_hours = ? WHERE employee_id = ? AND date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sdis", $time_now, $morning_hours, $user_id, $date);
                $update_stmt->execute();
                
                $success_message = "Lunch break started at $time_now!";
            } else {
                $error_message = "Lunch out time cannot be earlier than morning time-in.";
            }
        } else {
            $error_message = "You need to log your morning time-in first, or you have already logged your lunch break.";
        }
    }
    elseif (isset($_POST['lunch_in'])) {
        // Post-lunch Time In (Second shift start)
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance && $attendance['lunch_out'] !== null && $attendance['lunch_in'] === null) {
            $lunch_out = strtotime($attendance['lunch_out']);
            $lunch_in = strtotime($time_now);

            // Ensure lunch_in is after lunch_out
            if ($lunch_in > $lunch_out) {
                // Calculate lunch duration
                $lunch_duration_seconds = $lunch_in - $lunch_out;
                $lunch_duration_hours = $lunch_duration_seconds / 3600; // Convert to hours

                // Update attendance with lunch_in
                $update_sql = "UPDATE attendance SET lunch_in = ?, lunch_duration = ? WHERE employee_id = ? AND date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sdis", $time_now, $lunch_duration_hours, $user_id, $date);
                $update_stmt->execute();
                
                $success_message = "Afternoon shift started at $time_now!";
            } else {
                $error_message = "Lunch in time cannot be earlier than lunch out time.";
            }
        } else {
            $error_message = "You need to log your lunch out first, or you have already logged your lunch in.";
        }
    }
    elseif (isset($_POST['time_out'])) {
        // End of Day Time Out (Second shift end)
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance && $attendance['lunch_in'] !== null && $attendance['time_out'] === null) {
            $lunch_in = strtotime($attendance['lunch_in']);
            $time_out = strtotime($time_now);

            // Ensure time_out is after lunch_in
            if ($time_out > $lunch_in) {
                // Calculate afternoon shift hours
                $afternoon_shift_seconds = $time_out - $lunch_in;
                $afternoon_hours = $afternoon_shift_seconds / 3600; // Convert to hours
                
                // Calculate total working hours (morning + afternoon)
                $morning_hours = $attendance['morning_hours'] ?? 0;
                $total_hours = $morning_hours + $afternoon_hours;
                
                // Standard work day is 8 hours (8AM-12PM, 1PM-5PM)
                $overtime_hours = 0;
                $is_overtime = 0;
                
                // Check if clock out is after regular end time (5PM/17:00)
                if ($time_out > $regular_end) {
                    // Calculate overtime (hours worked past 5PM)
                    $overtime_seconds = $time_out - $regular_end;
                    $overtime_hours = $overtime_seconds / 3600;
                    $is_overtime = 1;
                }
                
                // Night differential hours (10PM to 6AM)
                $night_hours = 0;
                $night_overtime_hours = 0;
                $is_night_diff = 0;
                
                // Check for night hours (10PM to 6AM)
                $night_start = strtotime("$date $night_diff_start_time"); // 10PM
                $night_end = strtotime("$date 06:00:00 +1 day");          // 6AM next day
                $time_in = strtotime($attendance['time_in']);
                
                // Calculate night differential hours
                if ($time_out > $night_start) {
                    $night_diff_seconds = $time_out - $night_start;
                    $night_hours = $night_diff_seconds / 3600;
                    $is_night_diff = 1;
                    
                    // If night hours are also overtime hours (after 5PM)
                    $night_overtime_hours = min($night_hours, $overtime_hours);
                }
                
                // Update attendance with time_out and calculated hours
                $update_sql = "UPDATE attendance 
                              SET time_out = ?, 
                                  afternoon_hours = ?,
                                  hours_worked = ?, 
                                  overtime_hours = ?, 
                                  night_hours = ?, 
                                  night_overtime_hours = ?,
                                  is_overtime = ?,
                                  is_night_diff = ?
                              WHERE employee_id = ? AND date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param(
                    "sdddddiiis", 
                    $time_now,
                    $afternoon_hours,
                    $total_hours,
                    $overtime_hours, 
                    $night_hours, 
                    $night_overtime_hours,
                    $is_overtime,
                    $is_night_diff,
                    $user_id, 
                    $date
                );
                $update_stmt->execute();
                
                // Customize the success message based on overtime/night diff
                if ($is_overtime && $is_night_diff) {
                    $success_message = "Time-out logged at $time_now with " . 
                                      number_format($overtime_hours, 2) . " overtime hours and " . 
                                      number_format($night_hours, 2) . " night differential hours.";
                } elseif ($is_overtime) {
                    $success_message = "Time-out logged at $time_now with " . 
                                      number_format($overtime_hours, 2) . " overtime hours.";
                } elseif ($is_night_diff) {
                    $success_message = "Time-out logged at $time_now with " . 
                                      number_format($night_hours, 2) . " night differential hours.";
                } else {
                    $success_message = "Time-out logged successfully at $time_now! Total hours worked: " . 
                                      number_format($total_hours, 2);
                }
                
            } else {
                $error_message = "Time-out cannot be earlier than lunch in.";
            }
        } else {
            $error_message = "You need to complete your lunch break first, or you have already logged your time-out.";
        }
    }
    // Overtime Time In
    elseif (isset($_POST['overtime_in'])) {
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance && $attendance['time_out'] !== null && $attendance['overtime_in'] === null) {
            $time_out = strtotime($attendance['time_out']);
            $overtime_in = strtotime($time_now);

            // Ensure overtime_in is after time_out within reasonable timeframe (max 1 hour gap)
            $gap_seconds = $overtime_in - $time_out;
            $gap_hours = $gap_seconds / 3600; // Convert to hours
            
            if ($gap_hours <= 1) { // Allow maximum 1 hour gap between regular timesheet and overtime
                // Update attendance with overtime_in
                $update_sql = "UPDATE attendance SET overtime_in = ? WHERE employee_id = ? AND date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sis", $time_now, $user_id, $date);
                $update_stmt->execute();
                
                $success_message = "Overtime shift started at $time_now!";
            } else {
                $error_message = "Overtime must be logged within 1 hour of regular time-out.";
            }
        } else {
            if (!$attendance) {
                $error_message = "You need to log your regular hours first before logging overtime.";
            } elseif ($attendance['time_out'] === null) {
                $error_message = "You need to complete your regular shift before logging overtime.";
            } else {
                $error_message = "You have already logged your overtime start.";
            }
        }
    }
    // Overtime Time Out
    elseif (isset($_POST['overtime_out'])) {
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance && $attendance['overtime_in'] !== null && $attendance['overtime_out'] === null) {
            $overtime_in = strtotime($attendance['overtime_in']);
            $overtime_out = strtotime($time_now);

            // Ensure overtime_out is after overtime_in
            if ($overtime_out > $overtime_in) {
                // Calculate overtime hours
                $overtime_seconds = $overtime_out - $overtime_in;
                $explicit_overtime_hours = $overtime_seconds / 3600; // Convert to hours
                
                // Add to existing overtime hours (if any)
                $existing_overtime = $attendance['overtime_hours'] ?? 0;
                $total_overtime_hours = $existing_overtime + $explicit_overtime_hours;
                
                // Calculate night differential for overtime (after 10 PM)
                $night_hours = 0;
                $night_overtime_hours = 0;
                $is_night_diff = $attendance['is_night_diff'] ?? 0;
                
                // Check for night hours (10PM to 6AM)
                $night_start = strtotime("$date $night_diff_start_time"); // 10PM
                $night_end = strtotime("$date 06:00:00 +1 day");          // 6AM next day
                
                // Calculate night differential hours for the overtime period
                if ($overtime_in < $night_end && $overtime_out > $night_start) {
                    // Determine overlap with night differential hours
                    $night_start_time = max($overtime_in, $night_start);
                    $night_end_time = min($overtime_out, $night_end);
                    
                    if ($night_end_time > $night_start_time) {
                        $night_diff_seconds = $night_end_time - $night_start_time;
                        $night_diff_hours = $night_diff_seconds / 3600;
                        
                        // Add to existing night hours
                        $existing_night_hours = $attendance['night_hours'] ?? 0;
                        $night_hours = $existing_night_hours + $night_diff_hours;
                        
                        // Night overtime is the intersection of night hours and overtime hours
                        $existing_night_overtime = $attendance['night_overtime_hours'] ?? 0;
                        $night_overtime_hours = $existing_night_overtime + $night_diff_hours;
                        
                        $is_night_diff = 1;
                    }
                }
                
                // Update attendance with overtime hours and night differential
                $update_sql = "UPDATE attendance 
                              SET overtime_out = ?, 
                                  overtime_hours = ?, 
                                  night_hours = ?, 
                                  night_overtime_hours = ?,
                                  is_overtime = 1,
                                  is_night_diff = ?
                              WHERE employee_id = ? AND date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param(
                    "sdddis", 
                    $time_now,
                    $total_overtime_hours, 
                    $night_hours, 
                    $night_overtime_hours,
                    $is_night_diff,
                    $user_id, 
                    $date
                );
                $update_stmt->execute();
                
                // Customize the success message based on overtime/night diff
                if ($is_night_diff) {
                    $success_message = "Overtime completed at $time_now. Total overtime: " . 
                                       number_format($total_overtime_hours, 2) . " hrs, with " . 
                                       number_format($night_diff_hours, 2) . " hrs of night differential.";
                } else {
                    $success_message = "Overtime completed at $time_now. Total overtime: " . 
                                       number_format($total_overtime_hours, 2) . " hrs.";
                }
            } else {
                $error_message = "Overtime end time cannot be earlier than start time.";
            }
        } else {
            if (!$attendance) {
                $error_message = "You need to log your regular hours first before completing overtime.";
            } elseif ($attendance['overtime_in'] === null) {
                $error_message = "You need to start overtime before logging overtime completion.";
            } else {
                $error_message = "You have already logged your overtime completion.";
            }
        }
    }
}

// Get today's attendance record if exists
$check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $user_id, $date);
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
                <!-- Employee Profile Section -->
                <!-- [Keep existing profile card code] -->
                
                <!-- Real-Time Clock -->
                <!-- [Keep existing clock card code] -->
                
                <!-- Attendance Status Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6">
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
                                <!-- Morning Shift -->
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <span class="text-sm text-gray-500">Morning Time In:</span>
                                    <span class="block text-lg font-semibold text-gray-800">
                                        <?php echo date('h:i A', strtotime($today_attendance['time_in'])); ?>
                                    </span>
                                </div>
                                
                                <?php if ($today_attendance['lunch_out']): ?>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="text-sm text-gray-500">Lunch Break Start:</span>
                                        <span class="block text-lg font-semibold text-gray-800">
                                            <?php echo date('h:i A', strtotime($today_attendance['lunch_out'])); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($today_attendance['lunch_in']): ?>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="text-sm text-gray-500">Afternoon Time In:</span>
                                        <span class="block text-lg font-semibold text-gray-800">
                                            <?php echo date('h:i A', strtotime($today_attendance['lunch_in'])); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($today_attendance['time_out']): ?>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="text-sm text-gray-500">Time Out:</span>
                                        <span class="block text-lg font-semibold text-gray-800">
                                            <?php echo date('h:i A', strtotime($today_attendance['time_out'])); ?>
                                        </span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="text-sm text-gray-500">Total Hours Worked:</span>
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Morning Time In Button -->
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="time_in" 
                                        class="w-full flex items-center justify-center py-4 px-2 rounded-xl transition-all duration-300 
                                        <?php echo $today_attendance ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600 text-white shadow-lg hover:shadow-green-200'; ?>"
                                        <?php echo $today_attendance ? 'disabled' : ''; ?>>
                                    <div class="text-center">
                                        <span class="font-bold block text-sm">Morning In</span>
                                        <span class="text-xs">8:00 AM</span>
                                    </div>
                                </button>
                            </form>
                            
                            <!-- Lunch Out Button -->
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="lunch_out" 
                                        class="w-full flex items-center justify-center py-4 px-2 rounded-xl transition-all duration-300 
                                        <?php echo (!$today_attendance || $today_attendance['lunch_out']) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-yellow-500 hover:bg-yellow-600 text-white shadow-lg hover:shadow-yellow-200'; ?>"
                                        <?php echo (!$today_attendance || $today_attendance['lunch_out']) ? 'disabled' : ''; ?>>
                                    <div class="text-center">
                                        <span class="font-bold block text-sm">Lunch Out</span>
                                        <span class="text-xs">12:00 PM</span>
                                    </div>
                                </button>
                            </form>
                            
                            <!-- Lunch In Button -->
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="lunch_in" 
                                        class="w-full flex items-center justify-center py-4 px-2 rounded-xl transition-all duration-300 
                                        <?php echo (!$today_attendance || !$today_attendance['lunch_out'] || $today_attendance['lunch_in']) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white shadow-lg hover:shadow-blue-200'; ?>"
                                        <?php echo (!$today_attendance || !$today_attendance['lunch_out'] || $today_attendance['lunch_in']) ? 'disabled' : ''; ?>>
                                    <div class="text-center">
                                        <span class="font-bold block text-sm">Afternoon In</span>
                                        <span class="text-xs">1:00 PM</span>
                                    </div>
                                </button>
                            </form>
                            
                            <!-- Time Out Button -->
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="time_out" 
                                        class="w-full flex items-center justify-center py-4 px-2 rounded-xl transition-all duration-300 
                                        <?php echo (!$today_attendance || !$today_attendance['lunch_in'] || $today_attendance['time_out']) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-red-500 hover:bg-red-600 text-white shadow-lg hover:shadow-red-200'; ?>"
                                        <?php echo (!$today_attendance || !$today_attendance['lunch_in'] || $today_attendance['time_out']) ? 'disabled' : ''; ?>>
                                    <div class="text-center">
                                        <span class="font-bold block text-sm">Time Out</span>
                                        <span class="text-xs">4:00 PM</span>
                                    </div>
                                </button>
                            </form>

                            <!-- Overtime In Button -->
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="overtime_in" 
                                        class="w-full flex items-center justify-center py-4 px-2 rounded-xl transition-all duration-300 
                                        <?php echo (!$today_attendance || !$today_attendance['time_out'] || (isset($today_attendance['overtime_in']) && $today_attendance['overtime_in'])) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-purple-500 hover:bg-purple-600 text-white shadow-lg hover:shadow-purple-200'; ?>"
                                        <?php echo (!$today_attendance || !$today_attendance['time_out'] || (isset($today_attendance['overtime_in']) && $today_attendance['overtime_in'])) ? 'disabled' : ''; ?>>
                                    <div class="text-center">
                                        <span class="font-bold block text-sm">Overtime In</span>
                                        <span class="text-xs">After Regular Hours</span>
                                    </div>
                                </button>
                            </form>

                            <!-- Overtime Out Button -->
                            <form action="attendance.php" method="POST">
                                <button type="submit" name="overtime_out" 
                                        class="w-full flex items-center justify-center py-4 px-2 rounded-xl transition-all duration-300 
                                        <?php echo (!$today_attendance || !isset($today_attendance['overtime_in']) || !$today_attendance['overtime_in'] || (isset($today_attendance['overtime_out']) && $today_attendance['overtime_out'])) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-indigo-500 hover:bg-indigo-600 text-white shadow-lg hover:shadow-indigo-200'; ?>"
                                        <?php echo (!$today_attendance || !isset($today_attendance['overtime_in']) || !$today_attendance['overtime_in'] || (isset($today_attendance['overtime_out']) && $today_attendance['overtime_out'])) ? 'disabled' : ''; ?>>
                                    <div class="text-center">
                                        <span class="font-bold block text-sm">Overtime Out</span>
                                        <span class="text-xs">End of Overtime</span>
                                    </div>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance History Card -->
                <!-- [Keep existing attendance history table with updated columns] -->
            </div>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>

<script>
// Real-Time Clock
function updateClock() {
    // [Keep existing clock JavaScript]
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