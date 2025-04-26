<?php
// filepath: c:\xampp\htdocs\project\hr\insurance.php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Create insurance table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS insurance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    monthly_cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new insurance plan
    if (isset($_POST['add_insurance'])) {
        $plan_name = $_POST['plan_name'];
        $monthly_cost = $_POST['monthly_cost'];
        
        $sql = "INSERT INTO insurance (plan_name, monthly_cost) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sd", $plan_name, $monthly_cost);
        
        if ($stmt->execute()) {
            $success_message = "Insurance plan added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    }
    
    // Update existing insurance plan
    else if (isset($_POST['update_insurance'])) {
        $id = $_POST['id'];
        $plan_name = $_POST['plan_name'];
        $monthly_cost = $_POST['monthly_cost'];
        
        $sql = "UPDATE insurance SET plan_name = ?, monthly_cost = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdi", $plan_name, $monthly_cost, $id);
        
        if ($stmt->execute()) {
            $success_message = "Insurance plan updated successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    }
}

// Delete insurance plan
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if the insurance is assigned to any employee before deleting
    $check_sql = "SELECT COUNT(*) as count FROM employee_insurance WHERE insurance_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $error_message = "Cannot delete: This insurance plan is assigned to " . $row['count'] . " employee(s).";
    } else {
        $sql = "DELETE FROM insurance WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Insurance plan deleted successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    }
}

// Get data for edit form
$edit_id = null;
$edit_plan_name = '';
$edit_monthly_cost = '';

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $sql = "SELECT * FROM insurance WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $edit_plan_name = $row['plan_name'];
        $edit_monthly_cost = $row['monthly_cost'];
    }
}

// Fetch all insurance plans
$sql = "SELECT * FROM insurance ORDER BY plan_name";
$result = $conn->query($sql);

include '../components/header.php';
?>

<main class="bg-gradient-to-br from-emerald-50 to-teal-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Insurance Plans</h1>
                <p class="text-gray-600 mt-1">Manage employee insurance plans</p>
            </div>
            <a href="hr_dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Add/Edit Insurance Form -->
            <div class="bg-white rounded-lg shadow-md p-6 md:col-span-1 h-fit">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">
                    <?php echo $edit_id ? 'Edit Insurance Plan' : 'Add New Insurance Plan'; ?>
                </h2>
                
                <form method="POST" action="insurance.php">
                    <?php if($edit_id): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="plan_name" class="block text-sm font-medium text-gray-700 mb-1">Plan Name</label>
                        <input 
                            type="text" 
                            name="plan_name" 
                            id="plan_name" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50"
                            value="<?php echo htmlspecialchars($edit_plan_name); ?>"
                            required
                        >
                    </div>
                    
                    <div class="mb-4">
                        <label for="monthly_cost" class="block text-sm font-medium text-gray-700 mb-1">Monthly Cost (₱)</label>
                        <input 
                            type="number" 
                            name="monthly_cost" 
                            id="monthly_cost" 
                            step="0.01" 
                            min="0"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50"
                            value="<?php echo htmlspecialchars($edit_monthly_cost); ?>"
                            required
                        >
                    </div>
                    
                    <?php if($edit_id): ?>
                        <div class="flex items-center justify-between">
                            <button type="submit" name="update_insurance" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-4 rounded-md">
                                Update Plan
                            </button>
                            <a href="insurance.php" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                        </div>
                    <?php else: ?>
                        <button type="submit" name="add_insurance" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-4 rounded-md w-full">
                            Add Insurance Plan
                        </button>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Insurance Plans List -->
            <div class="bg-white rounded-lg shadow-md p-6 md:col-span-2">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Insurance Plans</h2>
                
                <?php if($result && $result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Plan Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Monthly Cost
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created At
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($row['plan_name']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                ₱<?php echo number_format($row['monthly_cost'], 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="?edit=<?php echo $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Edit
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['plan_name']); ?>')" class="text-red-600 hover:text-red-900">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-gray-500">No insurance plans found. Add your first plan using the form.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    function confirmDelete(id, planName) {
        if (confirm(`Are you sure you want to delete the insurance plan "${planName}"?`)) {
            window.location.href = `insurance.php?delete=${id}`;
        }
    }
</script>

<?php include '../components/footer.php'; ?>