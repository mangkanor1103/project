<?php
session_start();
include '../config.php';

// Check if the user is logged in as HR admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Check if updated_at column exists in expenses table
$column_exists = false;
try {
    $column_check = $conn->query("SHOW COLUMNS FROM expenses LIKE 'updated_at'");
    $column_exists = ($column_check && $column_check->num_rows > 0);
    
    // If column doesn't exist, try to create it
    if (!$column_exists) {
        $alter_query = "ALTER TABLE expenses ADD COLUMN updated_at datetime NULL AFTER created_at";
        if ($conn->query($alter_query)) {
            $column_exists = true;
            $success_message = "Database updated: added 'updated_at' column to expenses table.";
        }
    }
} catch (Exception $e) {
    // If we can't add the column, we'll just continue without using it
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $expense_id = $_POST['expense_id'];
    $admin_id = $_SESSION['admin_id'];
    $status = $_POST['status'];
    $comments = $_POST['comments'] ?? '';
    
    // Update the expense status - Without using updated_at if it doesn't exist
    if ($column_exists) {
        $update_sql = "UPDATE expenses SET 
                      status = ?, 
                      rejection_reason = ?, 
                      approved_by = ?, 
                      approved_date = NOW(),
                      updated_at = NOW()
                      WHERE id = ?";
    } else {
        $update_sql = "UPDATE expenses SET 
                      status = ?, 
                      rejection_reason = ?, 
                      approved_by = ?, 
                      approved_date = NOW()
                      WHERE id = ?";
    }
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssis", $status, $comments, $admin_id, $expense_id);
    
    if ($stmt->execute()) {
        $success_message = "The expense has been successfully " . strtolower($status) . ".";
    } else {
        $error_message = "Failed to update the expense status: " . $conn->error;
    }
}

// Mark as reimbursed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_reimbursed'])) {
    $expense_ids = $_POST['expense_ids'] ?? [];
    
    if (!empty($expense_ids)) {
        $placeholders = implode(',', array_fill(0, count($expense_ids), '?'));
        $types = str_repeat('i', count($expense_ids));
        
        // Use updated_at only if the column exists
        if ($column_exists) {
            $update_sql = "UPDATE expenses SET 
                          status = 'Reimbursed', 
                          reimbursed_date = NOW(),
                          updated_at = NOW()
                          WHERE id IN ($placeholders) AND status = 'Approved'";
        } else {
            $update_sql = "UPDATE expenses SET 
                          status = 'Reimbursed', 
                          reimbursed_date = NOW()
                          WHERE id IN ($placeholders) AND status = 'Approved'";
        }
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param($types, ...$expense_ids);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $success_message = "$affected_rows expense(s) marked as reimbursed successfully.";
        } else {
            $error_message = "Failed to mark expenses as reimbursed: " . $conn->error;
        }
    } else {
        $error_message = "No expenses selected for reimbursement.";
    }
}

// Get all expenses with employee details, ordered by status and date
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';

$expense_sql = "SELECT e.*, emp.full_name, emp.department, 
               emp.id as emp_number 
               FROM expenses e
               JOIN employees emp ON e.employee_id = emp.id
               WHERE 1=1";

$params = [];
$types = "";

if (!empty($filter_status)) {
    $expense_sql .= " AND e.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_department)) {
    $expense_sql .= " AND emp.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

$expense_sql .= " ORDER BY 
                  CASE 
                    WHEN e.status = 'Pending' THEN 1 
                    WHEN e.status = 'Approved' THEN 2
                    WHEN e.status = 'Rejected' THEN 3
                    WHEN e.status = 'Reimbursed' THEN 4
                  END, 
                  e.created_at DESC";

$stmt = $conn->prepare($expense_sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$expense_result = $stmt->get_result();

// Get departments for filter
$dept_sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
$dept_result = $conn->query($dept_sql);
$departments = [];
while ($dept_row = $dept_result->fetch_assoc()) {
    $departments[] = $dept_row['department'];
}

// Get summary statistics
$stats_sql = "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
             SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
             SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
             SUM(CASE WHEN status = 'Reimbursed' THEN 1 ELSE 0 END) as reimbursed,
             SUM(amount) as total_amount,
             SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
             SUM(CASE WHEN status = 'Approved' THEN amount ELSE 0 END) as approved_amount,
             SUM(CASE WHEN status = 'Reimbursed' THEN amount ELSE 0 END) as reimbursed_amount
             FROM expenses";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<?php include '../components/header.php'; ?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">Expense Approval Dashboard</h1>
            <a href="hr_dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-gray-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to HR Dashboard
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Pending Expenses -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Pending Approval</h3>
                        <p class="text-3xl font-bold text-yellow-500"><?php echo $stats['pending'] ?? 0; ?></p>
                        <p class="text-sm text-gray-500">PHP <?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Approved Expenses -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Approved</h3>
                        <p class="text-3xl font-bold text-green-500"><?php echo $stats['approved'] ?? 0; ?></p>
                        <p class="text-sm text-gray-500">PHP <?php echo number_format($stats['approved_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Reimbursed Expenses -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Reimbursed</h3>
                        <p class="text-3xl font-bold text-blue-500"><?php echo $stats['reimbursed'] ?? 0; ?></p>
                        <p class="text-sm text-gray-500">PHP <?php echo number_format($stats['reimbursed_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Total Expenses -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-gray-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Total Expenses</h3>
                        <p class="text-3xl font-bold text-gray-700"><?php echo $stats['total'] ?? 0; ?></p>
                        <p class="text-sm text-gray-500">PHP <?php echo number_format($stats['total_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white shadow-md rounded-lg p-4 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $filter_status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $filter_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="Reimbursed" <?php echo $filter_status === 'Reimbursed' ? 'selected' : ''; ?>>Reimbursed</option>
                    </select>
                </div>
                
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select id="department" name="department" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $filter_department === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                        Apply Filters
                    </button>
                    <a href="expenses.php" class="ml-2 text-sm text-blue-600 hover:text-blue-800">Clear All</a>
                </div>
            </form>
        </div>

        <!-- Expense List -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Expense Requests</h2>
            </div>
            
            <?php if ($expense_result->num_rows > 0): ?>
                <!-- Form for bulk reimbursement -->
                <form method="POST" action="" id="reimbursementForm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 shadow-sm">
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($expense = $expense_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($expense['status'] === 'Approved'): ?>
                                                <input type="checkbox" name="expense_ids[]" value="<?php echo $expense['id']; ?>" class="expense-checkbox rounded border-gray-300 text-blue-600 shadow-sm">
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($expense['full_name']); ?></div>
                                            <div class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($expense['emp_number']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($expense['department']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></div>
                                            <div class="text-xs text-gray-500">Submitted: <?php echo date('M d, Y', strtotime($expense['created_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($expense['expense_type']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            PHP <?php echo number_format($expense['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            $status_class = '';
                                            switch ($expense['status']) {
                                                case 'Approved':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    break;
                                                case 'Rejected':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    break;
                                                case 'Pending':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'Reimbursed':
                                                    $status_class = 'bg-blue-100 text-blue-800';
                                                    break;
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($expense['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($expense['receipt_file'])): ?>
                                                <a href="../uploads/receipts/<?php echo htmlspecialchars($expense['receipt_file']); ?>" 
                                                   target="_blank" class="text-blue-600 hover:text-blue-800">
                                                    View Receipt
                                                </a>
                                            <?php else: ?>
                                                None
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <?php if ($expense['status'] === 'Pending'): ?>
                                                <button type="button" 
                                                    onclick="showActionModal(<?php echo $expense['id']; ?>, '<?php echo htmlspecialchars($expense['full_name']); ?>', '<?php echo htmlspecialchars($expense['expense_type']); ?>', '<?php echo number_format($expense['amount'], 2); ?>')"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    Review
                                                </button>
                                            <?php else: ?>
                                                <button type="button" 
                                                    onclick="showDetailsModal(<?php echo $expense['id']; ?>, '<?php echo htmlspecialchars($expense['full_name']); ?>', '<?php echo htmlspecialchars($expense['expense_type']); ?>', '<?php echo number_format($expense['amount'], 2); ?>', '<?php echo htmlspecialchars($expense['description']); ?>', '<?php echo htmlspecialchars($expense['status']); ?>', '<?php echo htmlspecialchars($expense['rejection_reason'] ?? ''); ?>')"
                                                    class="text-gray-600 hover:text-gray-900">
                                                    Details
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <button type="submit" name="mark_reimbursed" id="markReimbursedBtn" class="bg-green-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Mark Selected as Reimbursed
                        </button>
                        <span class="ml-3 text-sm text-gray-500" id="selectedCount">0 selected</span>
                    </div>
                </form>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg">No expense requests found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Action Modal -->
    <div id="actionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" aria-modal="true" role="dialog">
        <div class="relative top-20 mx-auto p-5 border w-96 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Review Expense Request</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500" id="modalContent"></p>
                    
                    <div class="mt-4 text-left">
                        <form method="POST" action="" id="actionForm">
                            <input type="hidden" name="expense_id" id="expense_id">
                            <input type="hidden" name="action" value="update_status">
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                                    Decision
                                </label>
                                <select name="status" id="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="Approved">Approve</option>
                                    <option value="Rejected">Reject</option>
                                </select>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="comments">
                                    Comments
                                </label>
                                <textarea name="comments" id="comments" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" placeholder="Optional comments..."></textarea>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <button type="button" onclick="closeModal('actionModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                    Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" aria-modal="true" role="dialog">
        <div class="relative top-20 mx-auto p-5 border w-96 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mb-4" id="detailsTitle">Expense Details</h3>
                <div class="mt-2 px-2 py-3">
                    <div class="space-y-3" id="detailsContent"></div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="button" onclick="closeModal('detailsModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Show action modal
    function showActionModal(id, name, type, amount) {
        document.getElementById('expense_id').value = id;
        document.getElementById('modalTitle').innerText = 'Review Expense Request';
        document.getElementById('modalContent').innerHTML = `
            <div class="text-left mb-4">
                <p><strong>Employee:</strong> ${name}</p>
                <p><strong>Type:</strong> ${type}</p>
                <p><strong>Amount:</strong> PHP ${amount}</p>
            </div>
            <p>Please review and provide your decision below:</p>
        `;
        document.getElementById('actionModal').classList.remove('hidden');
    }
    
    // Show details modal
    function showDetailsModal(id, name, type, amount, description, status, comments) {
        let statusClass = '';
        switch (status) {
            case 'Approved':
                statusClass = 'bg-green-100 text-green-800';
                break;
            case 'Rejected':
                statusClass = 'bg-red-100 text-red-800';
                break;
            case 'Pending':
                statusClass = 'bg-yellow-100 text-yellow-800';
                break;
            case 'Reimbursed':
                statusClass = 'bg-blue-100 text-blue-800';
                break;
            default:
                statusClass = 'bg-gray-100 text-gray-800';
        }
        
        document.getElementById('detailsTitle').innerText = `Expense #${id} Details`;
        document.getElementById('detailsContent').innerHTML = `
            <p class="flex justify-between"><span class="font-semibold">Employee:</span> <span>${name}</span></p>
            <p class="flex justify-between"><span class="font-semibold">Type:</span> <span>${type}</span></p>
            <p class="flex justify-between"><span class="font-semibold">Amount:</span> <span>PHP ${amount}</span></p>
            <p><span class="font-semibold">Description:</span></p>
            <p class="bg-gray-50 p-2 rounded text-sm">${description}</p>
            <p class="flex justify-between"><span class="font-semibold">Status:</span> 
               <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">${status}</span>
            </p>
            ${comments ? `
                <p><span class="font-semibold">Comments:</span></p>
                <p class="bg-gray-50 p-2 rounded text-sm">${comments}</p>
            ` : ''}
        `;
        document.getElementById('detailsModal').classList.remove('hidden');
    }
    
    // Close modal
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        const expenseCheckboxes = document.querySelectorAll('.expense-checkbox');
        const markReimbursedBtn = document.getElementById('markReimbursedBtn');
        const selectedCountEl = document.getElementById('selectedCount');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                expenseCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateSelectedCount();
            });
        }
        
        // Handle individual checkboxes
        expenseCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });
        
        // Update selected count
        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('.expense-checkbox:checked').length;
            if (selectedCountEl) {
                selectedCountEl.textContent = `${checkedCount} selected`;
            }
            
            if (markReimbursedBtn) {
                markReimbursedBtn.disabled = checkedCount === 0;
            }
        }
        
        // Initialize
        updateSelectedCount();
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const actionModal = document.getElementById('actionModal');
            const detailsModal = document.getElementById('detailsModal');
            
            if (event.target === actionModal) {
                closeModal('actionModal');
            }
            
            if (event.target === detailsModal) {
                closeModal('detailsModal');
            }
        });
    });
</script>

<?php include '../components/footer.php'; ?>