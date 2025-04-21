<?php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

$success_message = "";
$error_message = "";

// Set default date to today
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';

// Process attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $employee_id = $_POST['employee_id'];
    $attendance_date = $_POST['date'];
    $time_in = $_POST['time_in'] ?: null;
    $time_out = $_POST['time_out'] ?: null;
    $is_absent = isset($_POST['is_absent']) ? 1 : 0;
    
    // Calculate hours worked if not absent
    $hours_worked = 0;
    $overtime_hours = 0;
    $night_hours = 0;
    $night_overtime_hours = 0;
    
    if (!$is_absent && !empty($time_in) && !empty($time_out)) {
        // Convert time strings to DateTime objects
        $time_in_obj = new DateTime($time_in);
        $time_out_obj = new DateTime($time_out);
        
        // Handle overnight shifts
        if ($time_out_obj < $time_in_obj) {
            $time_out_obj->modify('+1 day');
        }
        
        // Calculate the time difference in hours
        $interval = $time_in_obj->diff($time_out_obj);
        $hours_worked = $interval->h + ($interval->i / 60);
        
        // Calculate overtime hours (assuming 8-hour regular workday)
        if ($hours_worked > 8) {
            $overtime_hours = $hours_worked - 8;
            $hours_worked = 8;
        }
        
        // Calculate night hours (10 PM to 6 AM)
        $night_start = new DateTime('22:00');
        $night_end = new DateTime('06:00');
        $night_end->modify('+1 day');
        
        // Check if shift spans night hours
        if ($time_in_obj < $night_end || $time_out_obj > $night_start) {
            // Determine overlap with night shift
            $night_shift_start = max($time_in_obj, $night_start);
            $night_shift_end = min($time_out_obj, $night_end);
            
            if ($night_shift_start < $night_shift_end) {
                $night_interval = $night_shift_start->diff($night_shift_end);
                $night_hours = $night_interval->h + ($night_interval->i / 60);
                
                // Adjust regular and night overtime hours
                if ($hours_worked + $overtime_hours > 8) {
                    if ($night_hours > $overtime_hours) {
                        $night_overtime_hours = $overtime_hours;
                        $night_hours -= $overtime_hours;
                    } else {
                        $night_overtime_hours = $night_hours;
                        $night_hours = 0;
                    }
                }
            }
        }
    }
    
    // Check if attendance record exists for this employee and date
    $check_sql = "SELECT id FROM attendance WHERE employee_id = ? AND date = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $employee_id, $attendance_date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // Begin transaction
    $conn->begin_transaction();
    try {
        if ($check_result->num_rows > 0) {
            // Update existing record
            $attendance_id = $check_result->fetch_assoc()['id'];
            $update_sql = "UPDATE attendance SET 
                          time_in = ?, 
                          time_out = ?, 
                          hours_worked = ?, 
                          overtime_hours = ?,
                          night_hours = ?,
                          night_overtime_hours = ?,
                          is_absent = ?
                          WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssdddiii", 
                $time_in, 
                $time_out, 
                $hours_worked,
                $overtime_hours,
                $night_hours,
                $night_overtime_hours,
                $is_absent,
                $attendance_id
            );
            
            if ($update_stmt->execute()) {
                $conn->commit();
                $success_message = "Attendance record updated successfully!";
            } else {
                throw new Exception($conn->error);
            }
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO attendance 
                          (employee_id, date, time_in, time_out, hours_worked, overtime_hours, night_hours, night_overtime_hours, is_absent) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("isssddddi", 
                $employee_id, 
                $attendance_date, 
                $time_in, 
                $time_out, 
                $hours_worked,
                $overtime_hours,
                $night_hours,
                $night_overtime_hours,
                $is_absent
            );
            
            if ($insert_stmt->execute()) {
                $conn->commit();
                $success_message = "Attendance record added successfully!";
            } else {
                throw new Exception($conn->error);
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to save attendance record: " . $e->getMessage();
    }
}

// Process bulk attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $attendance_date = $_POST['mark_date'];
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : [];
    $attendance_status = $_POST['attendance_status'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        $success_count = 0;
        
        foreach ($employee_ids as $emp_id) {
            // Check if record exists
            $check_sql = "SELECT id FROM attendance WHERE employee_id = ? AND date = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("is", $emp_id, $attendance_date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            $is_absent = 0;
            $time_in = null;
            $time_out = null;
            $hours_worked = 0;
            
            if ($attendance_status === 'present') {
                // For present, set default work hours 8:00 to 17:00
                $time_in = '08:00:00';
                $time_out = '17:00:00';
                $hours_worked = 8;
            } elseif ($attendance_status === 'absent') {
                $is_absent = 1;
            }
            
            if ($check_result->num_rows > 0) {
                // Update existing record
                $attendance_id = $check_result->fetch_assoc()['id'];
                $update_sql = "UPDATE attendance SET time_in = ?, time_out = ?, hours_worked = ?, is_absent = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssdii", $time_in, $time_out, $hours_worked, $is_absent, $attendance_id);
                
                if ($update_stmt->execute()) {
                    $success_count++;
                }
            } else {
                // Insert new record
                $insert_sql = "INSERT INTO attendance (employee_id, date, time_in, time_out, hours_worked, is_absent) 
                               VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("isssdi", $emp_id, $attendance_date, $time_in, $time_out, $hours_worked, $is_absent);
                
                if ($insert_stmt->execute()) {
                    $success_count++;
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        $success_message = "$success_count employees marked as " . ucfirst($attendance_status) . " successfully!";
    } catch (Exception $e) {
        // Rollback on failure
        $conn->rollback();
        $error_message = "Failed to mark attendance: " . $e->getMessage();
    }
}

// Process mark all non-recorded employees as absent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_absent'])) {
    $attendance_date = $_POST['mark_date'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Find all employees without attendance records for this date
        $find_sql = "SELECT e.id FROM employees e 
                    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ? 
                    WHERE a.id IS NULL";
        $find_params = [$attendance_date];
        $find_types = "s";
        
        if (!empty($filter_department)) {
            $find_sql .= " AND e.department = ?";
            $find_params[] = $filter_department;
            $find_types .= "s";
        }
        
        $find_stmt = $conn->prepare($find_sql);
        $find_stmt->bind_param($find_types, ...$find_params);
        $find_stmt->execute();
        $find_result = $find_stmt->get_result();
        
        $success_count = 0;
        
        while ($row = $find_result->fetch_assoc()) {
            $emp_id = $row['id'];
            
            // Insert absent record
            $insert_sql = "INSERT INTO attendance (employee_id, date, is_absent) VALUES (?, ?, 1)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("is", $emp_id, $attendance_date);
            
            if ($insert_stmt->execute()) {
                $success_count++;
            }
        }
        
        // Commit transaction
        $conn->commit();
        $success_message = "$success_count employees marked as absent successfully!";
    } catch (Exception $e) {
        // Rollback on failure
        $conn->rollback();
        $error_message = "Failed to mark employees as absent: " . $e->getMessage();
    }
}

// Get departments for filter
$departments = [];
$dept_sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
$dept_result = $conn->query($dept_sql);
if ($dept_result && $dept_result->num_rows > 0) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Get attendance statistics for the selected date
// Count total employees
$total_sql = "SELECT COUNT(*) as count FROM employees";
if (!empty($filter_department)) {
    $total_sql .= " WHERE department = '$filter_department'";
}
$total_result = $conn->query($total_sql);
$total_employees = $total_result && $total_result->num_rows > 0 ? $total_result->fetch_assoc()['count'] : 0;

// Count present employees (those with attendance records and not absent)
$present_sql = "SELECT COUNT(DISTINCT a.employee_id) as count 
               FROM attendance a 
               JOIN employees e ON a.employee_id = e.id
               WHERE a.date = ? AND a.is_absent = 0";
$params = [$selected_date];
$types = "s";

if (!empty($filter_department)) {
    $present_sql .= " AND e.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$present_stmt = $conn->prepare($present_sql);
$present_stmt->bind_param($types, ...$params);
$present_stmt->execute();
$present_result = $present_stmt->get_result();
$present_count = $present_result && $present_result->num_rows > 0 ? $present_result->fetch_assoc()['count'] : 0;

// Count explicitly marked absent employees
$absent_sql = "SELECT COUNT(DISTINCT a.employee_id) as count 
              FROM attendance a 
              JOIN employees e ON a.employee_id = e.id
              WHERE a.date = ? AND a.is_absent = 1";
$params = [$selected_date];
$types = "s";

if (!empty($filter_department)) {
    $absent_sql .= " AND e.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$absent_stmt = $conn->prepare($absent_sql);
$absent_stmt->bind_param($types, ...$params);
$absent_stmt->execute();
$absent_result = $absent_stmt->get_result();
$marked_absent_count = $absent_result && $absent_result->num_rows > 0 ? $absent_result->fetch_assoc()['count'] : 0;

// Count employees on approved leave
$leave_sql = "SELECT COUNT(DISTINCT lr.employee_id) as count 
             FROM leave_requests lr 
             JOIN employees e ON lr.employee_id = e.id
             WHERE ? BETWEEN lr.start_date AND lr.end_date 
             AND lr.status = 'Approved'";
$params = [$selected_date];
$types = "s";

if (!empty($filter_department)) {
    $leave_sql .= " AND e.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$leave_stmt = $conn->prepare($leave_sql);
$leave_stmt->bind_param($types, ...$params);
$leave_stmt->execute();
$leave_result = $leave_stmt->get_result();
$leave_count = $leave_result && $leave_result->num_rows > 0 ? $leave_result->fetch_assoc()['count'] : 0;

// Count employees without attendance records (considered absent)
$no_record_count = $total_employees - ($present_count + $marked_absent_count + $leave_count);

// Total absent = explicitly marked + those without records (excluding on leave)
$total_absent_count = $marked_absent_count + $no_record_count;

// Count on-time and late employees
$time_sql = "SELECT 
             SUM(CASE WHEN TIME(a.time_in) <= '08:30:00' THEN 1 ELSE 0 END) as on_time,
             SUM(CASE WHEN TIME(a.time_in) > '08:30:00' THEN 1 ELSE 0 END) as late
             FROM attendance a 
             JOIN employees e ON a.employee_id = e.id
             WHERE a.date = ? AND a.is_absent = 0 AND a.time_in IS NOT NULL";
$params = [$selected_date];
$types = "s";

if (!empty($filter_department)) {
    $time_sql .= " AND e.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$time_stmt = $conn->prepare($time_sql);
$time_stmt->bind_param($types, ...$params);
$time_stmt->execute();
$time_result = $time_stmt->get_result();
$on_time_count = 0;
$late_count = 0;
if ($time_result && $time_result->num_rows > 0) {
    $time_counts = $time_result->fetch_assoc();
    $on_time_count = $time_counts['on_time'] ?? 0;
    $late_count = $time_counts['late'] ?? 0;
}

// Get employees without attendance records for today (considered absent)
$no_record_sql = "SELECT e.* 
                 FROM employees e 
                 LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
                 LEFT JOIN leave_requests lr ON e.id = lr.employee_id 
                    AND ? BETWEEN lr.start_date AND lr.end_date 
                    AND lr.status = 'Approved'
                 WHERE a.id IS NULL AND lr.id IS NULL"; // Exclude employees on leave
$params = [$selected_date, $selected_date];
$types = "ss";

if (!empty($filter_department)) {
    $no_record_sql .= " AND e.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$no_record_sql .= " ORDER BY e.department, e.full_name";

$no_record_stmt = $conn->prepare($no_record_sql);
$no_record_stmt->bind_param($types, ...$params);
$no_record_stmt->execute();
$no_record_result = $no_record_stmt->get_result();

// Get employees on approved leave
$on_leave_sql = "SELECT e.*, lr.leave_type 
                FROM employees e 
                JOIN leave_requests lr ON e.id = lr.employee_id
                WHERE ? BETWEEN lr.start_date AND lr.end_date 
                AND lr.status = 'Approved'";
$params = [$selected_date];
$types = "s";

if (!empty($filter_department)) {
    $on_leave_sql .= " AND e.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$on_leave_sql .= " ORDER BY e.department, e.full_name";

$on_leave_stmt = $conn->prepare($on_leave_sql);
$on_leave_stmt->bind_param($types, ...$params);
$on_leave_stmt->execute();
$on_leave_result = $on_leave_stmt->get_result();

// Get attendance records for the selected date
$attendance_sql = "SELECT a.*, e.full_name, e.department,
                  CASE WHEN lr.id IS NOT NULL THEN 1 ELSE 0 END AS on_leave,
                  lr.leave_type
                  FROM attendance a
                  JOIN employees e ON a.employee_id = e.id
                  LEFT JOIN leave_requests lr ON a.employee_id = lr.employee_id 
                     AND ? BETWEEN lr.start_date AND lr.end_date 
                     AND lr.status = 'Approved'
                  WHERE a.date = ?";
$params = [$selected_date, $selected_date];
$types = "ss";

if (!empty($filter_department)) {
    $attendance_sql .= " AND e.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$attendance_sql .= " ORDER BY e.department, e.full_name";

$attendance_stmt = $conn->prepare($attendance_sql);
$attendance_stmt->bind_param($types, ...$params);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
?>

<?php include '../components/header.php'; ?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">Attendance Management</h1>
            <a href="hr_dashboard.php"
                class="bg-gray-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-gray-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Date and Department Filter -->
        <div class="bg-white shadow-md rounded-lg p-4 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" id="date" name="date" value="<?php echo $selected_date; ?>"
                        class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select id="department" name="department"
                        class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" 
                                <?php echo $filter_department === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Attendance Summary -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Attendance Summary - <?php echo date('F d, Y', strtotime($selected_date)); ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-md">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Total Employees</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $total_employees; ?></p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Present</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $present_count; ?></p>
                            <p class="text-xs text-gray-500"><?php echo round(($present_count / $total_employees) * 100, 1); ?>% of total</p>
                        </div>
                        <div class="bg-green-100 p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Absent (Total)</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $total_absent_count; ?></p>
                            <p class="text-xs text-gray-500"><?php echo round(($total_absent_count / $total_employees) * 100, 1); ?>% of total</p>
                        </div>
                        <div class="bg-red-100 p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-md">
                    <p class="text-sm text-gray-500">On Leave</p>
                    <p class="text-xl font-semibold text-gray-800"><?php echo $leave_count; ?></p>
                    <p class="text-xs text-gray-500"><?php echo round(($leave_count / $total_employees) * 100, 1); ?>% of total</p>
                </div>
                <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-md">
                    <p class="text-sm text-gray-500">Late</p>
                    <p class="text-xl font-semibold text-gray-800"><?php echo $late_count; ?></p>
                    <p class="text-xs text-gray-500"><?php echo $present_count > 0 ? round(($late_count / $present_count) * 100, 1) : 0; ?>% of present</p>
                </div>
                <div class="bg-gray-50 border-l-4 border-gray-500 p-4 rounded-r-md">
                    <p class="text-sm text-gray-500">Marked Absent</p>
                    <p class="text-xl font-semibold text-gray-800"><?php echo $marked_absent_count; ?></p>
                    <p class="text-xs text-gray-500"><?php echo round(($marked_absent_count / $total_employees) * 100, 1); ?>% of total</p>
                </div>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-md">
                    <p class="text-sm text-gray-500">No Records (Considered Absent)</p>
                    <p class="text-xl font-semibold text-gray-800"><?php echo $no_record_count; ?></p>
                    <p class="text-xs text-gray-500"><?php echo round(($no_record_count / $total_employees) * 100, 1); ?>% of total</p>
                </div>
            </div>
        </div>

        <!-- Attendance Management Tabs -->
        <div class="mb-6">
            <div class="flex border-b border-gray-200">
                <button onclick="showTab('today')" class="tab-button px-6 py-3 text-blue-600 border-b-2 border-blue-600 font-medium">Today's Attendance</button>
                <button onclick="showTab('record')" class="tab-button px-6 py-3 text-gray-500 font-medium">Mark Attendance</button>
                <button onclick="showTab('add')" class="tab-button px-6 py-3 text-gray-500 font-medium">Add Individual Attendance</button>
            </div>
        </div>

        <!-- Today's Attendance Tab -->
        <div id="today-tab" class="tab-content bg-white shadow-lg rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Attendance Records for <?php echo date('F d, Y', strtotime($selected_date)); ?></h2>
            
            <?php if ($attendance_result->num_rows > 0): ?>
                <!-- Employees with attendance records -->
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Recorded Attendance</h3>
                <div class="overflow-x-auto mb-8">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $attendance_result->fetch_assoc()): ?>
                                <?php 
                                $status = 'Present';
                                $status_class = 'bg-green-100 text-green-800';
                                
                                if ($row['is_absent'] == 1) {
                                    $status = 'Absent';
                                    $status_class = 'bg-red-100 text-red-800';
                                } elseif (isset($row['on_leave']) && $row['on_leave'] == 1) {
                                    $status = 'On Leave';
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                } elseif (!empty($row['time_in']) && strtotime($row['time_in']) > strtotime('08:30:00')) {
                                    $status = 'Late';
                                    $status_class = 'bg-orange-100 text-orange-800';
                                }
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($row['department']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo $status; ?>
                                            <?php if (isset($row['on_leave']) && $row['on_leave'] == 1 && !empty($row['leave_type'])): ?> 
                                                (<?php echo htmlspecialchars($row['leave_type']); ?>)
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo ($row['is_absent'] == 1 || (isset($row['on_leave']) && $row['on_leave'] == 1)) ? '-' : $row['time_in']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo ($row['is_absent'] == 1 || (isset($row['on_leave']) && $row['on_leave'] == 1)) ? '-' : $row['time_out']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo ($row['is_absent'] == 1 || (isset($row['on_leave']) && $row['on_leave'] == 1)) ? '-' : number_format($row['hours_worked'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo ($row['is_absent'] == 1 || (isset($row['on_leave']) && $row['on_leave'] == 1)) ? '-' : number_format($row['overtime_hours'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="editAttendance(<?php echo $row['id']; ?>, <?php echo $row['employee_id']; ?>, '<?php echo $row['date']; ?>', '<?php echo $row['time_in']; ?>', '<?php echo $row['time_out']; ?>', <?php echo $row['is_absent']; ?>)"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ($no_record_result->num_rows > 0): ?>
                <!-- Employees without attendance records - considered absent -->
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Employees Without Attendance Records (Considered Absent)</h3>
                <div class="overflow-x-auto mb-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($emp = $no_record_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($emp['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($emp['department']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($emp['job_position']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Absent (Not Recorded)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="addAttendance(<?php echo $emp['id']; ?>, '<?php echo $selected_date; ?>')"
                                            class="text-blue-600 hover:text-blue-900">
                                            Add Attendance
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Button to mark all no-record employees as absent -->
                <div class="mt-4 mb-6 flex justify-end">
                    <form method="POST" action="">
                        <input type="hidden" name="mark_date" value="<?php echo $selected_date; ?>">
                        <button type="submit" name="mark_all_absent" 
                            class="bg-red-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-red-700 transition">
                            Mark All Unrecorded Employees as Absent
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($on_leave_result->num_rows > 0): ?>
                <!-- Employees on leave -->
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Employees on Leave</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($emp = $on_leave_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($emp['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($emp['department']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($emp['job_position']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            <?php echo htmlspecialchars($emp['leave_type']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ($attendance_result->num_rows === 0 && $no_record_result->num_rows === 0 && $on_leave_result->num_rows === 0): ?>
                <div class="text-center py-8 text-gray-500">
                    No employees found for the selected filters.
                </div>
            <?php endif; ?>
        </div>

        <!-- Mark Attendance Tab -->
        <div id="record-tab" class="tab-content hidden bg-white shadow-lg rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Bulk Attendance Marking</h2>
            
            <?php
            // Fetch employees for bulk attendance
            $bulk_filter = !empty($filter_department) ? "WHERE department = '$filter_department'" : "";
            $bulk_sql = "SELECT id, full_name, department, job_position FROM employees $bulk_filter ORDER BY department, full_name";
            $bulk_result = $conn->query($bulk_sql);
            ?>
            
            <?php if ($bulk_result && $bulk_result->num_rows > 0): ?>
                <form method="POST" action="">
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="mark_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" id="mark_date" name="mark_date" required value="<?php echo $selected_date; ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="attendance_status" class="block text-sm font-medium text-gray-700 mb-1">Mark as</label>
                            <select id="attendance_status" name="attendance_status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4 flex justify-between items-center">
                        <div>
                            <button type="button" id="selectAllButton" class="text-sm text-blue-600 hover:text-blue-800">
                                Select All
                            </button>
                            <span class="mx-2 text-gray-400">|</span>
                            <button type="button" id="deselectAllButton" class="text-sm text-blue-600 hover:text-blue-800">
                                Deselect All
                            </button>
                        </div>
                        <span class="text-sm text-gray-500" id="selectionCounter">0 employees selected</span>
                    </div>
                    
                    <div class="border border-gray-300 rounded-md p-4 max-h-96 overflow-y-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="w-10 px-4 py-2"></th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($emp = $bulk_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-4 py-2">
                                            <input type="checkbox" name="employee_ids[]" value="<?php echo $emp['id']; ?>"
                                                class="employee-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($emp['department']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($emp['job_position']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <button type="submit" name="mark_attendance"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                        Mark Attendance for Selected Employees
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    No employees found for the selected department.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Add Individual Attendance Tab -->
        <div id="add-tab" class="tab-content hidden bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Add/Edit Individual Attendance</h2>
            <form method="POST" action="" id="attendanceForm">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                        <select id="employee_id" name="employee_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select Employee --</option>
                            <?php
                            // Reset the bulk result pointer
                            if ($bulk_result) {
                                $bulk_result->data_seek(0);
                                while ($emp = $bulk_result->fetch_assoc()) {
                                    echo "<option value=\"{$emp['id']}\">{$emp['full_name']} ({$emp['department']})</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" id="attendance_date" name="date" required
                            value="<?php echo $selected_date; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Attendance Status</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_absent" id="is_absent" value="1"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2">Absent</span>
                            </label>
                            <div id="leaveStatusDisplay" class="text-sm text-gray-500"></div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="time_in" class="block text-sm font-medium text-gray-700 mb-1">Time In</label>
                        <input type="time" id="time_in" name="time_in"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="time_out" class="block text-sm font-medium text-gray-700 mb-1">Time Out</label>
                        <input type="time" id="time_out" name="time_out"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" name="submit_attendance"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                        Save Attendance Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // Tab switching functionality
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Deactivate all tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
            button.classList.add('text-gray-500');
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').classList.remove('hidden');
        
        // Activate selected tab button
        document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.remove('text-gray-500');
        document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
    }

    // Edit attendance record
    function editAttendance(id, employeeId, date, timeIn, timeOut, isAbsent) {
        showTab('add');
        
        // Set form values
        document.getElementById('employee_id').value = employeeId;
        document.getElementById('attendance_date').value = date;
        document.getElementById('time_in').value = timeIn;
        document.getElementById('time_out').value = timeOut;
        document.getElementById('is_absent').checked = isAbsent === 1;
        
        // Check for leave status
        checkLeaveStatus(employeeId, date);
        
        // Update form state based on attendance status
        updateFormState();
        
        // Scroll to the form
        document.getElementById('add-tab').scrollIntoView({
            behavior: 'smooth'
        });
    }

    // Add attendance for employee without record
    function addAttendance(employeeId, date) {
        showTab('add');
        
        // Set form values
        document.getElementById('employee_id').value = employeeId;
        document.getElementById('attendance_date').value = date;
        document.getElementById('time_in').value = '';
        document.getElementById('time_out').value = '';
        document.getElementById('is_absent').checked = false;
        
        // Check for leave status
        checkLeaveStatus(employeeId, date);
        
        // Scroll to the form
        document.getElementById('add-tab').scrollIntoView({
            behavior: 'smooth'
        });
    }

    // Check leave status
    function checkLeaveStatus(employeeId, date) {
        const leaveStatusDisplay = document.getElementById('leaveStatusDisplay');
        leaveStatusDisplay.textContent = "Checking leave status...";
        
        // AJAX call to check leave status would go here
        // For now, we'll just reset it
        leaveStatusDisplay.textContent = "";
    }

    // Handle status checkboxes to update form state
    function updateFormState() {
        const isAbsent = document.getElementById('is_absent').checked;
        const timeIn = document.getElementById('time_in');
        const timeOut = document.getElementById('time_out');
        
        if (isAbsent) {
            timeIn.disabled = true;
            timeOut.disabled = true;
            timeIn.value = '';
            timeOut.value = '';
        } else {
            timeIn.disabled = false;
            timeOut.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const isAbsentCheckbox = document.getElementById('is_absent');
        
        if (isAbsentCheckbox) {
            isAbsentCheckbox.addEventListener('change', updateFormState);
        }
        
        // Handle bulk employee selection
        const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
        const selectAllButton = document.getElementById('selectAllButton');
        const deselectAllButton = document.getElementById('deselectAllButton');
        const selectionCounter = document.getElementById('selectionCounter');
        
        function updateSelectionCounter() {
            if (selectionCounter) {
                let count = document.querySelectorAll('.employee-checkbox:checked').length;
                selectionCounter.textContent = count + (count === 1 ? ' employee' : ' employees') + ' selected';
            }
        }
        
        if (selectAllButton) {
            selectAllButton.addEventListener('click', function() {
                employeeCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSelectionCounter();
            });
        }
        
        if (deselectAllButton) {
            deselectAllButton.addEventListener('click', function() {
                employeeCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelectionCounter();
            });
        }
        
        employeeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectionCounter);
        });
        
        // Initial update
        updateSelectionCounter();
        updateFormState();
    });
</script>

<?php include '../components/footer.php'; ?>