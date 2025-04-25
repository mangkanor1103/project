<?php
session_start();
include '../config.php';
include 'check_permission.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

requirePermission('manage_job_positions');

// Handle job position operations
$success_message = "";
$error_message = "";

// Create the job_positions table if it doesn't exist
$table_check_sql = "SHOW TABLES LIKE 'job_positions'";
$table_exists = $conn->query($table_check_sql)->num_rows > 0;

if (!$table_exists) {
    $create_table_sql = "
    CREATE TABLE `job_positions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `position_name` varchar(100) NOT NULL,
      `position_description` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `position_name` (`position_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($create_table_sql) === TRUE) {
        // Insert sample job positions
        $sample_positions = [
            ['Staff', 'Entry-level employee position'],
            ['Team Lead', 'Leads a small team of staff members'],
            ['Supervisor', 'Oversees operations and staff in a department'],
            ['Manager', 'Manages department operations and personnel'],
            ['Senior Manager', 'Oversees multiple managers or complex departments'],
            ['Director', 'Directs overall strategy for a business unit'],
            ['Executive', 'Senior leadership role with company-wide authority']
        ];

        $insert_sql = "INSERT INTO job_positions (position_name, position_description) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);

        foreach ($sample_positions as $position) {
            $stmt->bind_param("ss", $position[0], $position[1]);
            $stmt->execute();
        }
    } else {
        $error_message = "Error creating job_positions table: " . $conn->error;
    }
}

// Add new job position
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_position'])) {
    $position_name = trim($_POST['position_name']);
    $position_description = trim($_POST['position_description']);

    if (empty($position_name)) {
        $error_message = "Position name cannot be empty.";
    } else {
        // Check if position already exists
        $check_sql = "SELECT COUNT(*) as count FROM job_positions WHERE position_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $position_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->fetch_assoc()['count'] > 0;

        if ($exists) {
            $error_message = "Job position with this name already exists.";
        } else {
            // Insert new position
            $insert_sql = "INSERT INTO job_positions (position_name, position_description) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ss", $position_name, $position_description);

            if ($insert_stmt->execute()) {
                $success_message = "New job position '{$position_name}' created successfully!";
            } else {
                $error_message = "Error creating job position: " . $conn->error;
            }
        }
    }
}

// Update job position
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_position'])) {
    $position_id = intval($_POST['position_id']);
    $position_name = trim($_POST['position_name']);
    $position_description = trim($_POST['position_description']);

    if (empty($position_name)) {
        $error_message = "Position name cannot be empty.";
    } else {
        // Check if the position name already exists for a different ID
        $check_sql = "SELECT COUNT(*) as count FROM job_positions WHERE position_name = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $position_name, $position_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->fetch_assoc()['count'] > 0;

        if ($exists) {
            $error_message = "Job position with this name already exists.";
        } else {
            // Get the old position name for reference
            $old_name_sql = "SELECT position_name FROM job_positions WHERE id = ?";
            $old_name_stmt = $conn->prepare($old_name_sql);
            $old_name_stmt->bind_param("i", $position_id);
            $old_name_stmt->execute();
            $old_result = $old_name_stmt->get_result();
            $old_position_name = $old_result->fetch_assoc()['position_name'];

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Update the position in the job_positions table
                $update_sql = "UPDATE job_positions SET position_name = ?, position_description = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssi", $position_name, $position_description, $position_id);
                $update_stmt->execute();

                // Update all employees with this job position
                $update_emp_sql = "UPDATE employees SET job_position = ? WHERE job_position = ?";
                $update_emp_stmt = $conn->prepare($update_emp_sql);
                $update_emp_stmt->bind_param("ss", $position_name, $old_position_name);
                $update_emp_stmt->execute();

                // Commit transaction
                $conn->commit();
                $success_message = "Job position updated from '{$old_position_name}' to '{$position_name}' successfully!";
            } catch (Exception $e) {
                // Rollback on failure
                $conn->rollback();
                $error_message = "Error updating job position: " . $e->getMessage();
            }
        }
    }
}

// Delete job position
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_position'])) {
    $position_id = intval($_POST['position_id']);
    $reassign_to_id = isset($_POST['reassign_to_id']) ? intval($_POST['reassign_to_id']) : null;

    // Get position name for the position we're deleting
    $name_sql = "SELECT position_name FROM job_positions WHERE id = ?";
    $name_stmt = $conn->prepare($name_sql);
    $name_stmt->bind_param("i", $position_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();

    if ($name_result->num_rows === 0) {
        $error_message = "Position not found.";
    } else {
        $position_name = $name_result->fetch_assoc()['position_name'];

        // Count employees in this position
        $count_sql = "SELECT COUNT(*) as count FROM employees WHERE job_position = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("s", $position_name);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $emp_count = $count_result->fetch_assoc()['count'];

        if ($emp_count > 0 && empty($reassign_to_id)) {
            $error_message = "Cannot delete job position with {$emp_count} employees. Please specify a position to reassign them to.";
        } else {
            // Begin transaction
            $conn->begin_transaction();

            try {
                if ($emp_count > 0) {
                    // Get the name of the position we're reassigning to
                    $reassign_name_sql = "SELECT position_name FROM job_positions WHERE id = ?";
                    $reassign_name_stmt = $conn->prepare($reassign_name_sql);
                    $reassign_name_stmt->bind_param("i", $reassign_to_id);
                    $reassign_name_stmt->execute();
                    $reassign_result = $reassign_name_stmt->get_result();

                    if ($reassign_result->num_rows === 0) {
                        throw new Exception("Reassignment position not found.");
                    }

                    $reassign_position_name = $reassign_result->fetch_assoc()['position_name'];

                    // Reassign employees to new position
                    $reassign_sql = "UPDATE employees SET job_position = ? WHERE job_position = ?";
                    $reassign_stmt = $conn->prepare($reassign_sql);
                    $reassign_stmt->bind_param("ss", $reassign_position_name, $position_name);
                    $reassign_stmt->execute();
                }

                // Delete the position from job_positions table
                $delete_sql = "DELETE FROM job_positions WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $position_id);
                $delete_stmt->execute();

                // Commit transaction
                $conn->commit();

                $success_message = "Job position '{$position_name}' deleted successfully! " .
                    ($emp_count > 0 ? "{$emp_count} employees have been reassigned." : "");
            } catch (Exception $e) {
                // Rollback on failure
                $conn->rollback();
                $error_message = "Error deleting job position: " . $e->getMessage();
            }
        }
    }
}

// Assign employees to job position
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_employees'])) {
    $position_id = intval($_POST['position_id']);
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : [];

    if (empty($employee_ids)) {
        $error_message = "No employees selected for assignment.";
    } else {
        // Get position name
        $name_sql = "SELECT position_name FROM job_positions WHERE id = ?";
        $name_stmt = $conn->prepare($name_sql);
        $name_stmt->bind_param("i", $position_id);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();

        if ($name_result->num_rows === 0) {
            $error_message = "Position not found.";
        } else {
            $position_name = $name_result->fetch_assoc()['position_name'];

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Update each employee's job position
                $update_sql = "UPDATE employees SET job_position = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);

                $success_count = 0;
                foreach ($employee_ids as $emp_id) {
                    $update_stmt->bind_param("si", $position_name, $emp_id);
                    if ($update_stmt->execute()) {
                        $success_count++;
                    }
                }

                // Commit transaction
                $conn->commit();
                $success_message = "{$success_count} employees assigned to '{$position_name}' position successfully!";
            } catch (Exception $e) {
                // Rollback on failure
                $conn->rollback();
                $error_message = "Failed to assign employees: " . $e->getMessage();
            }
        }
    }
}

// Get all job positions
$positions = [];
$pos_sql = "SELECT jp.*, COUNT(e.id) as employee_count 
            FROM job_positions jp
            LEFT JOIN employees e ON jp.position_name = e.job_position
            GROUP BY jp.id
            ORDER BY jp.position_name";
$pos_result = $conn->query($pos_sql);

if ($pos_result) {
    while ($row = $pos_result->fetch_assoc()) {
        $positions[] = $row;
    }
}
?>

<?php include '../components/header.php'; ?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-indigo-600">Job Position Management</h1>
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

        <!-- Job Position Management Tabs -->
        <div class="mb-6">
            <div class="flex border-b border-gray-200">
                <button onclick="showTab('add')"
                    class="tab-button px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium">Add
                    Position</button>
                <button onclick="showTab('list')" class="tab-button px-6 py-3 text-gray-500 font-medium">Position
                    List</button>
                <button onclick="showTab('assign')" class="tab-button px-6 py-3 text-gray-500 font-medium">Assign
                    Employees</button>
            </div>
        </div>

        <!-- Add New Job Position Form -->
        <div id="add-tab" class="tab-content bg-white shadow-lg rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Create New Job Position</h2>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="position_name" class="block text-sm font-medium text-gray-700 mb-1">Position
                            Name</label>
                        <input type="text" id="position_name" name="position_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="position_description"
                            class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <input type="text" id="position_description" name="position_description"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" name="add_position"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-indigo-700 transition">
                        Create Position
                    </button>
                </div>
            </form>
        </div>

        <!-- Job Positions List -->
        <div id="list-tab" class="tab-content hidden bg-white shadow-lg rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Job Position List</h2>
            <?php if (empty($positions)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">No job positions found. Create your first position using the form above.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Position Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Employees</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($positions as $position): ?>
                                <tr id="pos-row-<?php echo $position['id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">
                                        <?php echo htmlspecialchars($position['position_name']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php echo !empty($position['position_description']) ? htmlspecialchars($position['position_description']) : '<span class="text-gray-400 italic">No description</span>'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo $position['employee_count']; ?> employees
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button
                                            onclick="editPosition(<?php echo $position['id']; ?>, '<?php echo addslashes($position['position_name']); ?>', '<?php echo addslashes($position['position_description'] ?? ''); ?>')"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Edit
                                        </button>
                                        <button
                                            onclick="confirmDelete(<?php echo $position['id']; ?>, '<?php echo addslashes($position['position_name']); ?>', <?php echo $position['employee_count']; ?>)"
                                            class="text-red-600 hover:text-red-900" <?php echo ($position['employee_count'] > 0) ? 'title="This will require reassigning employees"' : ''; ?>>
                                            Delete
                                        </button>
                                        <button
                                            onclick="viewEmployeesByPosition('<?php echo addslashes($position['position_name']); ?>')"
                                            class="text-indigo-600 hover:text-indigo-900 ml-3">
                                            View Employees
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assign Employees Tab -->
        <div id="assign-tab" class="tab-content hidden bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Assign Employees to Job Position</h2>

            <?php if (empty($positions)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">No job positions available. Please create a position first.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-6">
                        <label for="assign_position_id" class="block text-sm font-medium text-gray-700 mb-1">Select
                            Position</label>
                        <select id="assign_position_id" name="position_id" required
                            class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Select Position --</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo $position['id']; ?>">
                                    <?php echo htmlspecialchars($position['position_name']); ?>
                                    (<?php echo $position['employee_count']; ?> employees)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Position filter options -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter Employees</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="employee_filter" value="all" checked
                                    class="text-indigo-600 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">All Employees</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="employee_filter" value="unassigned"
                                    class="text-indigo-600 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">Unassigned Only</span>
                            </label>
                        </div>
                    </div>

                    <!-- Search box -->
                    <div class="mb-6">
                        <label for="employee_search" class="block text-sm font-medium text-gray-700 mb-1">Search
                            Employees</label>
                        <input type="text" id="employee_search" placeholder="Search by name, position, or department..."
                            class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Employee selection area -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Select Employees to Assign</h3>
                        <div class="mb-2 flex justify-between items-center">
                            <div>
                                <button type="button" id="selectAllButton"
                                    class="text-sm text-blue-600 hover:text-blue-800">
                                    Select All
                                </button>
                                <span class="mx-2 text-gray-400">|</span>
                                <button type="button" id="deselectAllButton"
                                    class="text-sm text-blue-600 hover:text-blue-800">
                                    Deselect All
                                </button>
                            </div>
                            <span class="text-sm text-gray-500" id="selectionCounter">0 employees selected</span>
                        </div>

                        <!-- Employee list with checkboxes -->
                        <div class="border border-gray-300 rounded-md p-4 max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-10 px-4 py-2"></th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Current Position</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Department</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="employeeCheckboxList">
                                    <?php
                                    // Fetch all employees
                                    $all_employees_sql = "SELECT id, full_name, job_position, department FROM employees ORDER BY full_name";
                                    $all_employees_result = $conn->query($all_employees_sql);

                                    if ($all_employees_result && $all_employees_result->num_rows > 0):
                                        while ($emp = $all_employees_result->fetch_assoc()):
                                            $current_pos = empty($emp['job_position']) ? '(Unassigned)' : $emp['job_position'];
                                            $is_unassigned = empty($emp['job_position']);
                                            ?>
                                            <tr class="employee-row <?php echo $is_unassigned ? 'unassigned' : 'assigned'; ?>"
                                                data-name="<?php echo strtolower(htmlspecialchars($emp['full_name'])); ?>"
                                                data-position="<?php echo strtolower(htmlspecialchars($current_pos)); ?>"
                                                data-department="<?php echo strtolower(htmlspecialchars($emp['department'] ?? '')); ?>">
                                                <td class="px-4 py-2">
                                                    <input type="checkbox" name="employee_ids[]" value="<?php echo $emp['id']; ?>"
                                                        class="employee-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                </td>
                                                <td class="px-4 py-2"><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                                <td class="px-4 py-2">
                                                    <?php if ($is_unassigned): ?>
                                                        <span class="text-gray-500 italic"><?php echo $current_pos; ?></span>
                                                    <?php else: ?>
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            <?php echo htmlspecialchars($current_pos); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <?php if (!empty($emp['department'])): ?>
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            <?php echo htmlspecialchars($emp['department']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 italic">(Unassigned)</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="4" class="px-4 py-2 text-center text-gray-500">No employees found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" name="assign_employees"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-indigo-700 transition">
                            Assign Selected Employees
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Job Position Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Edit Job Position</h3>
            <form method="POST" action="" id="editForm">
                <input type="hidden" id="edit_position_id" name="position_id">
                <div class="mb-4">
                    <label for="edit_position_name" class="block text-sm font-medium text-gray-700 mb-1">Position
                        Name</label>
                    <input type="text" id="edit_position_name" name="position_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="edit_position_description"
                        class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" id="edit_position_description" name="position_description"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" name="update_position"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-indigo-700 transition">
                        Update Position
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Job Position</h3>
            <p id="deleteConfirmText" class="mb-4 text-gray-600">Are you sure you want to delete this position?</p>

            <form method="POST" action="" id="deleteForm">
                <input type="hidden" id="delete_position_id" name="position_id">

                <div id="reassignSection" class="mb-4 hidden">
                    <label for="reassign_to_id" class="block text-sm font-medium text-gray-700 mb-1">Reassign employees
                        to:</label>
                    <select id="reassign_to_id" name="reassign_to_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Select Position --</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?php echo $position['id']; ?>">
                                <?php echo htmlspecialchars($position['position_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">All employees in the deleted position will be moved to this
                        position.</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" name="delete_position"
                        class="bg-red-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-red-700 transition">
                        Delete Position
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Employees Modal -->
    <div id="viewEmployeesModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900" id="viewEmployeesTitle">Position Employees</h3>
                <button onclick="closeViewEmployeesModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div id="employeesList" class="max-h-96 overflow-y-auto">
                <!-- Employee list will be loaded dynamically -->
                <p class="text-center py-4 text-gray-500">Loading employees...</p>
            </div>
            <div class="mt-4 text-right">
                <button type="button" onclick="closeViewEmployeesModal()"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition">
                    Close
                </button>
            </div>
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
            button.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            button.classList.add('text-gray-500');
        });

        // Show selected tab
        document.getElementById(tabName + '-tab').classList.remove('hidden');

        // Activate selected tab button
        document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.remove('text-gray-500');
        document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('text-indigo-600', 'border-b-2', 'border-indigo-600');
    }

    function editPosition(id, name, description = '') {
        document.getElementById('edit_position_id').value = id;
        document.getElementById('edit_position_name').value = name;
        document.getElementById('edit_position_description').value = description;

        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(id, name, employeeCount) {
        document.getElementById('delete_position_id').value = id;

        // Filter out the current position from reassign options
        const reassignSelect = document.getElementById('reassign_to_id');
        for (let i = 0; i < reassignSelect.options.length; i++) {
            reassignSelect.options[i].disabled = (reassignSelect.options[i].value == id);
        }

        if (employeeCount > 0) {
            document.getElementById('deleteConfirmText').innerText =
                `Are you sure you want to delete the position "${name}"? This position has ${employeeCount} employees that need to be reassigned.`;
            document.getElementById('reassignSection').classList.remove('hidden');
            reassignSelect.required = true;
        } else {
            document.getElementById('deleteConfirmText').innerText =
                `Are you sure you want to delete the position "${name}"?`;
            document.getElementById('reassignSection').classList.add('hidden');
            reassignSelect.required = false;
        }

        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    function viewEmployeesByPosition(positionName) {
        const employeesList = document.getElementById('employeesList');
        employeesList.innerHTML = '<p class="text-center py-4 text-gray-500">Loading employees...</p>';

        document.getElementById('viewEmployeesTitle').innerText = `${positionName} - Employees`;
        document.getElementById('viewEmployeesModal').classList.remove('hidden');

        // Fetch employees for this position
        fetch(`get_position_employees.php?position_name=${encodeURIComponent(positionName)}`)
            .then(response => response.text())
            .then(html => {
                employeesList.innerHTML = html;
            })
            .catch(error => {
                employeesList.innerHTML = `<p class="text-center py-4 text-red-500">Error loading employees: ${error.message}</p>`;
            });
    }

    function closeViewEmployeesModal() {
        document.getElementById('viewEmployeesModal').classList.add('hidden');
    }

    // Close modals when clicking outside
    window.onclick = function (event) {
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        const viewEmployeesModal = document.getElementById('viewEmployeesModal');

        if (event.target === editModal) {
            closeEditModal();
        }

        if (event.target === deleteModal) {
            closeDeleteModal();
        }

        if (event.target === viewEmployeesModal) {
            closeViewEmployeesModal();
        }
    };

    // Employee assignment functionality
    document.addEventListener('DOMContentLoaded', function () {
        const employeeFilter = document.getElementsByName('employee_filter');
        const employeeSearch = document.getElementById('employee_search');
        const employeeRows = document.querySelectorAll('.employee-row');
        const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
        const selectAllButton = document.getElementById('selectAllButton');
        const deselectAllButton = document.getElementById('deselectAllButton');
        const selectionCounter = document.getElementById('selectionCounter');

        // Filter employees by status (all, unassigned)
        function filterEmployees() {
            let filterValue = document.querySelector('input[name="employee_filter"]:checked').value;
            let searchText = employeeSearch ? employeeSearch.value.toLowerCase().trim() : '';

            employeeRows.forEach(row => {
                const name = row.dataset.name;
                const position = row.dataset.position;
                const department = row.dataset.department || '';
                const isUnassigned = row.classList.contains('unassigned');

                // Check if the row matches the filter
                let matchesFilter = false;
                if (filterValue === 'all' || (filterValue === 'unassigned' && isUnassigned)) {
                    matchesFilter = true;
                }

                // Check if the row matches the search
                let matchesSearch = false;
                if (searchText === '' ||
                    name.includes(searchText) ||
                    position.includes(searchText) ||
                    department.includes(searchText)) {
                    matchesSearch = true;
                }

                if (matchesFilter && matchesSearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                    // Uncheck hidden rows
                    const checkbox = row.querySelector('.employee-checkbox');
                    if (checkbox && checkbox.checked) {
                        checkbox.checked = false;
                        updateSelectionCounter();
                    }
                }
            });
        }

        // Add event listeners for filtering and searching
        if (employeeFilter.length > 0 && employeeSearch) {
            employeeFilter.forEach(radio => {
                radio.addEventListener('change', filterEmployees);
            });

            employeeSearch.addEventListener('input', filterEmployees);

            // Select/deselect all visible rows
            if (selectAllButton) {
                selectAllButton.addEventListener('click', function () {
                    employeeRows.forEach(row => {
                        if (row.style.display !== 'none') {
                            const checkbox = row.querySelector('.employee-checkbox');
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        }
                    });
                    updateSelectionCounter();
                });
            }

            if (deselectAllButton) {
                deselectAllButton.addEventListener('click', function () {
                    employeeCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    updateSelectionCounter();
                });
            }

            // Update selection counter
            function updateSelectionCounter() {
                if (selectionCounter) {
                    let count = document.querySelectorAll('.employee-checkbox:checked').length;
                    selectionCounter.textContent = count + (count === 1 ? ' employee' : ' employees') + ' selected';
                }
            }

            // Add change event listeners to checkboxes
            employeeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectionCounter);
            });

            // Initial update
            updateSelectionCounter();
        }
    });
</script>

<?php include '../components/footer.php'; ?>