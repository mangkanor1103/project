<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Fix for the undefined array key - match the session variable from login.php
$employee_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($employee_id == 0) {
    // If still no valid ID, redirect to login
    session_destroy();
    header("Location: login.php?error=invalid_session");
    exit();
}

$success_message = '';
$error_message = '';

// Get employee details
$employee_query = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($employee_query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee_result = $stmt->get_result();
$employee = $employee_result->fetch_assoc();

// Check if expenses table exists, if not create it
$table_check = $conn->query("SHOW TABLES LIKE 'expenses'");
if ($table_check->num_rows == 0) {
    // Create the expenses table
    $create_table_sql = "CREATE TABLE `expenses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `expense_date` date NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `expense_type` varchar(50) NOT NULL,
        `description` text NOT NULL,
        `receipt_file` varchar(255) DEFAULT NULL,
        `status` enum('Pending','Approved','Rejected','Reimbursed') NOT NULL DEFAULT 'Pending',
        `approved_by` int(11) DEFAULT NULL,
        `approved_date` datetime DEFAULT NULL,
        `reimbursed_date` datetime DEFAULT NULL,
        `comments` text DEFAULT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `employee_id` (`employee_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    try {
        if ($conn->query($create_table_sql)) {
            $success_message = "Expense system initialized successfully. You may now submit expenses.";
        } else {
            $error_message = "Failed to initialize expense system. Please contact IT support.";
        }
    } catch (Exception $e) {
        $error_message = "Error creating expenses table: " . $e->getMessage();
    }
}

// Handle expense submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_expense'])) {
    $expense_date = $_POST['expense_date'];
    $amount = $_POST['amount'];
    $expense_type = $_POST['expense_type'];
    $description = $_POST['description'];
    $receipt_name = '';
    $receipt_path = '';
    
    // Create upload directory if it doesn't exist
    $upload_dir = "uploads/receipts/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle file upload
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'image/jpg'];
        $file_type = $_FILES['receipt']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Create a unique file name
            $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $receipt_name = uniqid('receipt_') . '_' . date('Ymd') . '.' . $file_extension;
            $receipt_path = $upload_dir . $receipt_name;
            
            // Move the uploaded file
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_path)) {
                // File uploaded successfully
            } else {
                $error_message = "Failed to upload receipt file.";
            }
        } else {
            $error_message = "Invalid file type. Only JPG, JPEG, PNG, GIF and PDF files are allowed.";
        }
    } else if ($_FILES['receipt']['error'] != 4) { // Error code 4 means no file was uploaded
        $error_message = "Error uploading file. Error code: " . $_FILES['receipt']['error'];
    }

    // If no errors, insert the expense record
    if (empty($error_message)) {
        $insert_sql = "INSERT INTO expenses (employee_id, expense_date, amount, expense_type, description, receipt_file, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isdsss", $employee_id, $expense_date, $amount, $expense_type, $description, $receipt_name);
        
        if ($stmt->execute()) {
            $success_message = "Expense submitted successfully for approval!";
        } else {
            $error_message = "Failed to submit expense request: " . $conn->error;
        }
    }
}

// Get employee's expense requests with error handling
try {
    $expense_sql = "SELECT * FROM expenses WHERE employee_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($expense_sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $expense_result = $stmt->get_result();
} catch (Exception $e) {
    // This will catch the error if the table doesn't exist
    $expense_result = false;
}
?>

<?php include 'components/header.php'; ?>

<main class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">Expense Reimbursements</h1>
            <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-gray-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Submit New Expense Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Submit New Expense</h2>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="space-y-4">
                            <div>
                                <label for="expense_date" class="block text-sm font-medium text-gray-700 mb-1">Date of Expense</label>
                                <input type="date" id="expense_date" name="expense_date" required max="<?php echo date('Y-m-d'); ?>" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (PHP)</label>
                                <input type="number" id="amount" name="amount" required step="0.01" min="0.01" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0.00">
                            </div>
                            
                            <div>
                                <label for="expense_type" class="block text-sm font-medium text-gray-700 mb-1">Expense Type</label>
                                <select id="expense_type" name="expense_type" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Type</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Meal">Meal</option>
                                    <option value="Office Supplies">Office Supplies</option>
                                    <option value="Communication">Communication</option>
                                    <option value="Travel">Travel</option>
                                    <option value="Training">Training</option>
                                    <option value="Medical">Medical</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea id="description" name="description" required rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Please provide details about this expense..."></textarea>
                            </div>
                            
                            <div>
                                <label for="receipt" class="block text-sm font-medium text-gray-700 mb-1">Upload Receipt</label>
                                <input type="file" id="receipt" name="receipt" required accept="image/jpeg,image/png,image/gif,application/pdf"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Accepted formats: JPG, JPEG, PNG, GIF, PDF (Max: 5MB)</p>
                            </div>
                            
                            <div class="pt-2">
                                <button type="submit" name="submit_expense"
                                    class="w-full bg-teal-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-teal-700 transition">
                                    Submit for Approval
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Expense List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Your Expense Requests</h2>
                    
                    <?php if ($expense_result && $expense_result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($expense = $expense_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($expense['expense_type']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                                                    default:
                                                        $status_class = 'bg-gray-100 text-gray-800';
                                                }
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($expense['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php if (!empty($expense['receipt_file'])): ?>
                                                    <a href="uploads/receipts/<?php echo htmlspecialchars($expense['receipt_file']); ?>" 
                                                       target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View
                                                    </a>
                                                <?php else: ?>
                                                    None
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p>You haven't submitted any expense requests yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Expense Guidelines -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Expense Reimbursement Guidelines</h2>
            <div class="prose max-w-none">
                <ul class="list-disc pl-5 space-y-2">
                    <li>All expense claims must be submitted with a valid receipt or supporting documentation.</li>
                    <li>Expenses must be submitted within 30 days of the expense date.</li>
                    <li>Expenses will be reviewed by HR and typically processed within 5-7 business days.</li>
                    <li>Approved expenses will be reimbursed in the next payroll cycle.</li>
                    <li>Any expense over PHP 5,000 requires additional approval from your department head.</li>
                    <li>For questions regarding your expense reimbursement, please contact the HR department.</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
    // Client-side validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const fileInput = document.getElementById('receipt');
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        form.addEventListener('submit', function(event) {
            // Check file size
            if (fileInput.files.length > 0) {
                if (fileInput.files[0].size > maxSize) {
                    event.preventDefault();
                    alert('The receipt file is too large. Maximum size allowed is 5MB.');
                    return false;
                }
            }
            return true;
        });

        // Preview image functionality could be added here
    });
</script>

<?php include 'components/footer.php'; ?>