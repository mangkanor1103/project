<!-- filepath: c:\xampp\htdocs\project\manager\dash.php -->
<?php
session_start();
include '../config.php';
include '../components/header.php'; // Include your header

// Check if manager is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'Manager') {
    header("Location: ../index.php");
    exit();
}

// Fetch selected date or default to today's date
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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

<main class="bg-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Attendance Dashboard</h1>
            <a href="../logout.php"
                class="bg-red-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-red-700 transition">
                Logout
            </a>
        </div>

        <!-- Modern Calendar -->
        <div class="flex justify-center mb-6">
            <form method="GET" class="flex items-center space-x-4">
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>"
                    class="p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                    View Attendance
                </button>
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Attendance for <?php echo htmlspecialchars($selected_date); ?></h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Night Hours</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($attendance_records)): ?>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['employee_id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($record['full_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['time_in'] ?? 'N/A'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['time_out'] ?? 'N/A'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['hours_worked'] ?? '0'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['overtime_hours'] ?? '0.00'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['night_hours'] ?? '0.00'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['is_absent'] ? 'Yes' : 'No'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button onclick="openModal(<?php echo htmlspecialchars(json_encode($record)); ?>)"
                                        class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">No attendance records found for <?php echo htmlspecialchars($selected_date); ?>.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Attendance Modal -->
<div id="attendance-modal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Attendance Details</h2>
        <div id="modal-content" class="space-y-4">
            <!-- Dynamic content will be injected here -->
        </div>
        <div class="flex justify-end space-x-2 mt-4">
            <button onclick="closeModal()"
                class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md shadow-md hover:bg-gray-300 transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // Open modal and populate content
    function openModal(record) {
        const modal = document.getElementById('attendance-modal');
        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = `
            <p><strong>Employee ID:</strong> ${record.employee_id}</p>
            <p><strong>Full Name:</strong> ${record.full_name}</p>
            <p><strong>Date:</strong> ${record.date}</p>
            <p><strong>Time In:</strong> ${record.time_in || 'N/A'}</p>
            <p><strong>Time Out:</strong> ${record.time_out || 'N/A'}</p>
            <p><strong>Hours Worked:</strong> ${record.hours_worked || '0'}</p>
            <p><strong>Overtime Hours:</strong> ${record.overtime_hours || '0.00'}</p>
            <p><strong>Night Hours:</strong> ${record.night_hours || '0.00'}</p>
            <p><strong>Absent:</strong> ${record.is_absent ? 'Yes' : 'No'}</p>
        `;
        modal.classList.remove('hidden');
    }

    // Close modal
    function closeModal() {
        const modal = document.getElementById('attendance-modal');
        modal.classList.add('hidden');
    }
</script>

<?php include '../components/footer.php'; ?>