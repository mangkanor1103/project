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

// Handle leave request submission
$success_message = $error_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'] ?? null;
    $custom_leave_type = $_POST['custom_leave_type'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $reason = $_POST['reason'] ?? null;

    // Use the custom leave type if "Other" is selected
    if ($leave_type === "Other" && !empty($custom_leave_type)) {
        $leave_type = $custom_leave_type;
    }

    // Validate inputs
    if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
        $error_message = "All fields are required.";
    } elseif ($start_date > $end_date) {
        $error_message = "The start date cannot be after the end date.";
    } else {
        // Insert leave request into the database
        $insert_sql = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("issss", $user_id, $leave_type, $start_date, $end_date, $reason);

        if ($insert_stmt->execute()) {
            // Set success message for JavaScript
            $success_message = "Your leave request has been submitted successfully!";
        } else {
            // Set error message for JavaScript
            $error_message = "There was an error submitting your leave request. Please try again.";
        }
    }
}

// Fetch leave requests submitted by the user
$leave_requests_sql = "SELECT id, leave_type, start_date, end_date, reason, status, created_at FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC";
$leave_requests_stmt = $conn->prepare($leave_requests_sql);
$leave_requests_stmt->bind_param("i", $user_id);
$leave_requests_stmt->execute();
$leave_requests_result = $leave_requests_stmt->get_result();
?>

<?php include 'components/header.php'; ?>

<main class="bg-gradient-to-b from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Back to Dashboard Link -->
        <div class="mb-8">
            <a href="dashboard.php"
                class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <div class="flex flex-col items-center mb-10">
            <h1 class="text-4xl font-bold text-blue-700 mb-2">Leave Management</h1>
            <div class="h-1 w-24 bg-blue-600 rounded-full mb-2"></div>
            <p class="text-gray-600 text-lg">Request and track your time off</p>
        </div>

        <!-- Leave Request Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <!-- Annual Leave -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl shadow-md border border-green-200">
                <div class="flex items-center mb-4">
                    <div class="bg-green-500 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-green-800 text-lg font-semibold">Annual Leave</h3>
                        <p class="text-green-600 text-sm">Vacation days</p>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-3xl font-bold text-green-700">10</span>
                    <span class="text-sm text-green-600">days remaining</span>
                </div>
            </div>

            <!-- Sick Leave -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow-md border border-blue-200">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-500 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-blue-800 text-lg font-semibold">Sick Leave</h3>
                        <p class="text-blue-600 text-sm">Health related</p>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-3xl font-bold text-blue-700">5</span>
                    <span class="text-sm text-blue-600">days remaining</span>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="bg-gradient-to-br from-amber-50 to-amber-100 p-6 rounded-xl shadow-md border border-amber-200">
                <div class="flex items-center mb-4">
                    <div class="bg-amber-500 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-amber-800 text-lg font-semibold">Pending</h3>
                        <p class="text-amber-600 text-sm">Awaiting approval</p>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <?php
                    $pending_count = 0;
                    // Count pending requests
                    if ($leave_requests_result) {
                        $leave_requests_result->data_seek(0);
                        while ($row = $leave_requests_result->fetch_assoc()) {
                            if ($row['status'] === 'Pending') {
                                $pending_count++;
                            }
                        }
                        $leave_requests_result->data_seek(0); // Reset pointer
                    }
                    ?>
                    <span class="text-3xl font-bold text-amber-700"><?php echo $pending_count; ?></span>
                    <span class="text-sm text-amber-600">requests</span>
                </div>
            </div>
        </div>

        <!-- Button to Trigger Modal -->
        <div class="mb-10 flex justify-center">
            <button onclick="toggleModal()"
                class="group bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-4 rounded-lg shadow-md hover:from-blue-700 hover:to-blue-800 transform transition duration-300 hover:-translate-y-1 hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 group-hover:animate-bounce" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="text-lg font-medium">New Leave Request</span>
            </button>
        </div>

        <!-- Leave Request Modal -->
        <div id="leaveRequestModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 px-4">
            <div class="bg-white w-full max-w-lg p-8 rounded-xl shadow-2xl transform transition-transform duration-300">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-blue-700">New Leave Request</h2>
                    <button onclick="toggleModal()"
                        class="text-gray-500 hover:text-gray-800 focus:outline-none transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="leave.php" method="POST" class="space-y-6">
                    <!-- Leave Type -->
                    <div>
                        <label for="leave_type" class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                        <select id="leave_type" name="leave_type"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-shadow bg-gray-50"
                            required onchange="toggleCustomLeaveTypeField(this.value)">
                            <option value="">Select Leave Type</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Vacation Leave">Vacation Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Maternity Leave">Maternity Leave</option>
                            <option value="Paternity Leave">Paternity Leave</option>
                            <option value="Other">Other (Specify)</option>
                        </select>
                    </div>

                    <!-- Custom Leave Type -->
                    <div id="custom_leave_type_field" style="display: none;">
                        <label for="custom_leave_type" class="block text-sm font-medium text-gray-700 mb-1">Specify
                            Leave Type</label>
                        <input type="text" id="custom_leave_type" name="custom_leave_type"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-shadow bg-gray-50">
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start
                                Date</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input type="date" id="start_date" name="start_date"
                                    class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-shadow bg-gray-50"
                                    required>
                            </div>
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input type="date" id="end_date" name="end_date"
                                    class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-shadow bg-gray-50"
                                    required>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for
                            Leave</label>
                        <textarea id="reason" name="reason" rows="4"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-shadow bg-gray-50"
                            required placeholder="Please provide details about your leave request..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="toggleModal()"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-600">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave Request List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4">
                <h2 class="text-xl font-semibold text-white">Your Leave Requests</h2>
            </div>

            <?php if ($leave_requests_result && $leave_requests_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Leave Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Submitted On</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $leave_requests_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php
                                            $icon = '';
                                            $color = '';
                                            if (strpos(strtolower($row['leave_type']), 'sick') !== false) {
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>';
                                                $color = 'text-red-600';
                                            } elseif (strpos(strtolower($row['leave_type']), 'vacation') !== false) {
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                                                $color = 'text-green-600';
                                            } elseif (strpos(strtolower($row['leave_type']), 'emergency') !== false) {
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
                                                $color = 'text-amber-600';
                                            } elseif (strpos(strtolower($row['leave_type']), 'maternity') !== false || strpos(strtolower($row['leave_type']), 'paternity') !== false) {
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>';
                                                $color = 'text-purple-600';
                                            } else {
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>';
                                                $color = 'text-blue-600';
                                            }
                                            ?>
                                            <div
                                                class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center <?php echo $color; ?>">
                                                <?php echo $icon; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($row['leave_type']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php
                                            $start = new DateTime($row['start_date']);
                                            $end = new DateTime($row['end_date']);
                                            $interval = $start->diff($end);
                                            $days = $interval->days + 1; // Include both start and end date
                                    
                                            echo date('M d', strtotime($row['start_date']));
                                            if ($row['start_date'] != $row['end_date']) {
                                                echo " - " . date('M d', strtotime($row['end_date']));
                                            }
                                            ?>
                                        </div>
                                        <div class="text-xs text-gray-500"><?php echo $days . ($days > 1 ? ' days' : ' day'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-900 truncate max-w-xs"
                                            title="<?php echo htmlspecialchars($row['reason']); ?>">
                                            <?php echo htmlspecialchars(substr($row['reason'], 0, 50) . (strlen($row['reason']) > 50 ? '...' : '')); ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($row['status'] === 'Approved'): ?>
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                                Approved
                                            </span>
                                        <?php elseif ($row['status'] === 'Rejected'): ?>
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Rejected
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-lg text-gray-600 font-medium mb-2">No leave requests yet</p>
                    <p class="text-gray-500">You haven't submitted any leave requests. Click "New Leave Request" to get
                        started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Show or hide the custom leave type field based on the selected leave type
    function toggleCustomLeaveTypeField(leaveType) {
        const customLeaveTypeField = document.getElementById('custom_leave_type_field');
        customLeaveTypeField.style.display = (leaveType === "Other") ? 'block' : 'none';
    }

    // Show or hide the modal with animation
    function toggleModal() {
        const modal = document.getElementById('leaveRequestModal');

        if (modal.classList.contains('hidden')) {
            // Show modal
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden'); // Prevent scrolling

            // Add animation
            setTimeout(() => {
                const modalContent = modal.querySelector('div');
                modalContent.classList.add('scale-100');
                modalContent.classList.remove('scale-95');
            }, 10);
        } else {
            // Hide modal with animation
            const modalContent = modal.querySelector('div');
            modalContent.classList.add('scale-95');
            modalContent.classList.remove('scale-100');

            // Wait for animation to finish
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }
    }

    // Initialize date pickers with min values
    document.addEventListener('DOMContentLoaded', function () {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').min = today;

        document.getElementById('start_date').addEventListener('change', function () {
            // Set min value of end_date to be the start_date
            document.getElementById('end_date').min = this.value;

            // If end date is before start date, reset it
            if (document.getElementById('end_date').value < this.value) {
                document.getElementById('end_date').value = this.value;
            }
        });
    });

    // Display success or error message
    <?php if ($success_message): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $success_message; ?>',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    <?php elseif ($error_message): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $error_message; ?>',
            confirmButtonText: 'OK'
        });
    <?php endif; ?>
</script>