<?php
session_start();
include '../config.php';

// Check if the HR Admin is logged in
if (!isset($_SESSION['hr_loggedin']) || $_SESSION['hr_loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Process approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $status = 'Approved';
    } elseif ($action === 'reject') {
        $status = 'Rejected';
    } else {
        $status = null;
    }

    if ($status) {
        $update_sql = "UPDATE leave_requests SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $status, $request_id);

        if ($stmt->execute()) {
            $message = "Leave request has been " . strtolower($status) . " successfully.";
        } else {
            $message = "Error updating leave request. Please try again.";
        }
    }
}

// Fetch pending leave requests
$sql = "SELECT lr.*, e.full_name 
        FROM leave_requests lr 
        JOIN employees e ON lr.employee_id = e.id 
        WHERE lr.status = 'Pending'";
$result = $conn->query($sql);

?>

<?php include '../components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-8">Approve Leave Requests</h1>

        <!-- Back to Dashboard Button -->
        <div class="text-center mb-6">
            <a href="hr_dashboard.php" class="bg-gray-600 text-white px-6 py-3 rounded hover:bg-gray-700 transition">
                Back to Dashboard
            </a>
        </div>

        <?php if (isset($message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <table class="min-w-full bg-white border-collapse border border-gray-300 shadow-lg rounded-lg">
                <thead>
                    <tr>
                        <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Employee Name</th>
                        <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Leave Type</th>
                        <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Start Date</th>
                        <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">End Date</th>
                        <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Reason</th>
                        <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['leave_type']); ?></td>
                            <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['start_date']); ?></td>
                            <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['end_date']); ?></td>
                            <td class="px-6 py-4 border-b text-sm text-gray-700"><?php echo htmlspecialchars($row['reason']); ?></td>
                            <td class="px-6 py-4 border-b text-sm text-gray-700">
                                <form action="approval.php" method="POST" class="inline">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition transform hover:scale-105">
                                        Approve
                                    </button>
                                </form>
                                <form action="approval.php" method="POST" class="inline">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition transform hover:scale-105">
                                        Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center text-gray-700 text-lg">No pending leave requests.</p>
        <?php endif; ?>
    </div>
</main>

<?php include '../components/footer.php'; ?>