<?php
session_start();
include '../config.php';
include '../components/header.php';

// Check if manager is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'Manager') {
    header("Location: ../index.php");
    exit();
}

// Fetch selected date or default to today's date
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get previous and next day for navigation
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));

// Get attendance stats for selected date
$stats_sql = "SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN is_absent = 1 THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN overtime_hours > 0 THEN 1 ELSE 0 END) as overtime_count
FROM attendance WHERE date = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("s", $selected_date);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Fetch attendance for the selected date
$attendance_sql = "
    SELECT 
        a.id, 
        a.employee_id, 
        e.full_name, 
        a.date, 
        a.time_in, 
        a.time_out, 
        a.hours_worked, 
        a.overtime_hours, 
        a.night_hours, 
        a.is_absent
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.date = ?
    ORDER BY a.time_in ASC";
$stmt = $conn->prepare($attendance_sql);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$attendance_result = $stmt->get_result();
$attendance_records = $attendance_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <main class="container mx-auto px-4 py-8">
        <!-- Enhanced Dashboard Header -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6 border-b-4 border-blue-500">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Attendance Dashboard
                    </h1>
                    <p class="text-gray-600 mt-1">Manage daily attendance records and monitor employee time tracking</p>
                </div>

                <div class="flex items-center space-x-3">
                    <div
                        class="hidden md:flex items-center bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full border border-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                    </div>

                    <a href="../logout.php"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Date Navigation & Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">
            <!-- Enhanced Date Navigation -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Select Date
                </h2>

                <form method="GET" class="mb-4">
                    <div class="flex flex-col md:flex-row md:space-x-4 space-y-3 md:space-y-0">
                        <div class="relative flex-grow">
                            <input type="date" id="date" name="date"
                                value="<?php echo htmlspecialchars($selected_date); ?>"
                                class="pl-10 pr-4 py-3 w-full border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                            View Attendance
                        </button>
                    </div>
                </form>

                <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                    <a href="?date=<?= $prev_date ?>"
                        class="text-blue-600 hover:text-blue-800 flex items-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Previous Day
                    </a>
                    <span class="text-gray-600 font-medium">
                        <?= date('F j, Y', strtotime($selected_date)) ?>
                    </span>
                    <a href="?date=<?= $next_date ?>"
                        class="text-blue-600 hover:text-blue-800 flex items-center transition-colors">
                        Next Day
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Present Employees Card -->
                <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Present</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['present_count'] ?? 0 ?></p>
                        </div>
                        <div class="bg-green-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-sm text-gray-500">Employees who checked in today</span>
                    </div>
                </div>

                <!-- Absent Employees Card -->
                <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-red-500">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Absent</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['absent_count'] ?? 0 ?></p>
                        </div>
                        <div class="bg-red-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-sm text-gray-500">Employees marked as absent</span>
                    </div>
                </div>

                <!-- Overtime Card -->
                <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Overtime</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['overtime_count'] ?? 0 ?></p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-sm text-gray-500">Employees with overtime hours</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Attendance Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <div class="p-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Attendance for <?php echo date('F j, Y', strtotime($selected_date)); ?>
                </h2>
                <div class="text-sm text-gray-500">
                    Total Records: <span class="font-semibold"><?= count($attendance_records) ?></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Employee ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Full Name</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time In</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time Out</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hours Worked</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Overtime</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Night Hours</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($attendance_records)): ?>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                        <?php echo $record['employee_id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php echo htmlspecialchars($record['full_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if ($record['time_in']): ?>
                                            <span class="flex items-center">
                                                <span class="h-2 w-2 bg-green-500 rounded-full mr-2"></span>
                                                <?php echo date('h:i A', strtotime($record['time_in'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="flex items-center text-gray-400">
                                                <span class="h-2 w-2 bg-gray-300 rounded-full mr-2"></span>
                                                N/A
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if ($record['time_out']): ?>
                                            <span class="flex items-center">
                                                <span class="h-2 w-2 bg-blue-500 rounded-full mr-2"></span>
                                                <?php echo date('h:i A', strtotime($record['time_out'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="flex items-center text-gray-400">
                                                <span class="h-2 w-2 bg-gray-300 rounded-full mr-2"></span>
                                                N/A
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo number_format($record['hours_worked'] ?? 0, 2); ?> hrs
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if ($record['overtime_hours'] > 0): ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <?php echo number_format($record['overtime_hours'] ?? 0, 2); ?> hrs
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if ($record['night_hours'] > 0): ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?php echo number_format($record['night_hours'] ?? 0, 2); ?> hrs
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($record['is_absent']): ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Absent
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Present
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button onclick="openModal(<?php echo htmlspecialchars(json_encode($record)); ?>)"
                                            class="inline-flex items-center px-3 py-1.5 border border-blue-600 text-blue-600 bg-white hover:bg-blue-50 rounded-md text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p class="text-lg font-medium">No attendance records found</p>
                                        <p class="text-sm text-gray-400 mt-1">No data available for
                                            <?php echo date('F j, Y', strtotime($selected_date)); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Enhanced Attendance Modal -->
<div id="attendance-modal"
    class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center hidden z-50 transition-opacity duration-300 ease-in-out">
    <div
        class="bg-white rounded-xl shadow-xl p-0 w-full max-w-lg mx-4 transform transition-transform duration-300 scale-100">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-xl">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Attendance Details
            </h2>
            <button onclick="closeModal()"
                class="text-gray-500 hover:text-gray-700 focus:outline-none transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div id="modal-content" class="px-6 py-5 max-h-[70vh] overflow-y-auto">
            <!-- Dynamic content will be injected here -->
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 rounded-b-xl">
            <button onclick="closeModal()"
                class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // Enhanced modal function with better styling and an animation
    function openModal(record) {
        const modal = document.getElementById('attendance-modal');
        const modalContent = document.getElementById('modal-content');

        // Format the time values
        const timeIn = record.time_in ? new Date('2000-01-01T' + record.time_in).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'N/A';
        const timeOut = record.time_out ? new Date('2000-01-01T' + record.time_out).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'N/A';

        // Format the date
        const formattedDate = new Date(record.date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Determine status class based on absence
        const statusClass = record.is_absent
            ? 'bg-red-100 text-red-800'
            : 'bg-green-100 text-green-800';

        const statusLabel = record.is_absent ? 'Absent' : 'Present';

        // Create the modal content with improved styling
        modalContent.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-2 mb-2">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                        ${statusLabel}
                    </div>
                </div>
                
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Employee ID</p>
                    <p class="text-sm font-medium text-gray-800">${record.employee_id}</p>
                </div>
                
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Full Name</p>
                    <p class="text-sm font-medium text-gray-800">${record.full_name}</p>
                </div>
                
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Date</p>
                    <p class="text-sm font-medium text-gray-800">${formattedDate}</p>
                </div>
                
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Hours Worked</p>
                    <p class="text-sm font-semibold text-blue-700">${record.hours_worked || '0.00'}</p>
                </div>
                
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Time In</p>
                    <p class="text-sm font-medium text-gray-800">
                        <span class="inline-flex items-center">
                            ${record.time_in ?
                `<span class="h-2 w-2 bg-green-500 rounded-full mr-2"></span>${timeIn}` :
                `<span class="h-2 w-2 bg-gray-300 rounded-full mr-2"></span>N/A`
            }
                        </span>
                    </p>
                </div>
                
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Time Out</p>
                    <p class="text-sm font-medium text-gray-800">
                        <span class="inline-flex items-center">
                            ${record.time_out ?
                `<span class="h-2 w-2 bg-blue-500 rounded-full mr-2"></span>${timeOut}` :
                `<span class="h-2 w-2 bg-gray-300 rounded-full mr-2"></span>N/A`
            }
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="border-t border-gray-200 mt-5 pt-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Overtime Hours</p>
                        ${parseFloat(record.overtime_hours || 0) > 0 ?
                `<p class="text-sm font-medium text-yellow-600">${record.overtime_hours} hours</p>` :
                `<p class="text-sm text-gray-500">None</p>`
            }
                    </div>
                    
                    <div class="space-y-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Night Differential</p>
                        ${parseFloat(record.night_hours || 0) > 0 ?
                `<p class="text-sm font-medium text-purple-600">${record.night_hours} hours</p>` :
                `<p class="text-sm text-gray-500">None</p>`
            }
                    </div>
                </div>
            </div>
        `;

        // Show the modal with animation
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.transform').classList.add('scale-100');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('attendance-modal');

        // Animate close
        modal.querySelector('.transform').classList.remove('scale-100');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('attendance-modal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<?php include '../components/footer.php'; ?>