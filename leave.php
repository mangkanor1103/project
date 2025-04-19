<?php
session_start();
include 'config.php'; // Include database configuration

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$leave_requests_sql = "SELECT leave_type, start_date, end_date, reason, status, created_at FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC";
$leave_requests_stmt = $conn->prepare($leave_requests_sql);
$leave_requests_stmt->bind_param("i", $user_id);
$leave_requests_stmt->execute();
$leave_requests_result = $leave_requests_stmt->get_result();
?>

<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-8">Request Leave</h1>

        <!-- Button to Trigger Modal -->
        <div class="text-center mb-8">
            <button onclick="toggleModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:scale-105">
                Request Leave
            </button>
        </div>

        <!-- Leave Request Modal -->
        <div id="leaveRequestModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50">
            <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-blue-600 mb-6 text-center">Request Leave</h2>
                <form action="leave.php" method="POST" class="space-y-6">
                    <!-- Leave Type -->
                    <div>
                        <label for="leave_type" class="block text-sm font-medium text-gray-700">Leave Type</label>
                        <select id="leave_type" name="leave_type" class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none" required onchange="toggleCustomLeaveTypeField(this.value)">
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
                        <label for="custom_leave_type" class="block text-sm font-medium text-gray-700">Specify Leave Type</label>
                        <input type="text" id="custom_leave_type" name="custom_leave_type" class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none" required>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none" required>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700">Reason</label>
                        <textarea id="reason" name="reason" rows="4" class="w-full mt-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none" required></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition transform hover:-translate-y-1 hover:scale-105">
                            Submit Leave Request
                        </button>
                        <button type="button" onclick="toggleModal()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition transform hover:-translate-y-1 hover:scale-105 ml-4">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave Request List -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-blue-600 text-center mb-6">Your Leave Requests</h2>
            <?php if ($leave_requests_result && $leave_requests_result->num_rows > 0): ?>
                <table class="min-w-full bg-white border-collapse border border-gray-300 shadow-lg rounded-lg">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Leave Type</th>
                            <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Start Date</th>
                            <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">End Date</th>
                            <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Reason</th>
                            <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Status</th>
                            <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Submitted On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $leave_requests_result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['start_date']); ?></td>
                                <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['end_date']); ?></td>
                                <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="px-6 py-4 border-b text-sm text-gray-700">
                                    <?php
                                    if ($row['status'] === 'Approved') {
                                        echo '<span class="text-green-600 font-bold">Approved</span>';
                                    } elseif ($row['status'] === 'Rejected') {
                                        echo '<span class="text-red-600 font-bold">Rejected</span>';
                                    } else {
                                        echo '<span class="text-yellow-600 font-bold">Pending</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-gray-700 text-lg">You have no leave requests yet.</p>
            <?php endif; ?>
        </div>

        <!-- Back to Dashboard Button -->
        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-600 text-white px-6 py-3 rounded hover:bg-gray-700 transition transform hover:-translate-y-1 hover:scale-105">
                Back to Dashboard
            </a>
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

// Show or hide the modal
function toggleModal() {
    const modal = document.getElementById('leaveRequestModal');
    modal.classList.toggle('hidden');
}

// Display success or error message
<?php if ($success_message): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '<?php echo $success_message; ?>',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {
        window.location.href = 'dashboard.php';
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