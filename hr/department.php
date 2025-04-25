<!-- filepath: c:\xampp\htdocs\project\hr\department.php -->
<?php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Handle department operations
$success_message = "";
$error_message = "";

// Add new department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $dept_name = trim($_POST['department_name']);
    $dept_description = trim($_POST['department_description']);
    $dept_manager_id = isset($_POST['department_manager']) ? intval($_POST['department_manager']) : null;

    if (empty($dept_name)) {
        $error_message = "Department name cannot be empty.";
    } elseif (empty($dept_manager_id)) {
        $error_message = "Please select a manager for the department.";
    } else {
        // Check if department already exists
        $check_sql = "SELECT COUNT(*) as count FROM departments WHERE name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $dept_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->fetch_assoc()['count'] > 0;

        if ($exists) {
            $error_message = "Department with this name already exists.";
        } else {
            // Insert new department
            $insert_sql = "INSERT INTO departments (name, description, manager_id) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssi", $dept_name, $dept_description, $dept_manager_id);

            if ($insert_stmt->execute()) {
                // If manager is selected, update their department
                if ($dept_manager_id) {
                    $update_sql = "UPDATE employees SET department = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $dept_name, $dept_manager_id);
                    $update_stmt->execute();
                }

                $success_message = "New department '{$dept_name}' created successfully!";
            } else {
                $error_message = "Failed to create department: " . $conn->error;
            }
        }
    }
}

// Update department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_department'])) {
    $dept_id = intval($_POST['department_id']);
    $dept_name = trim($_POST['department_name']);
    $dept_description = trim($_POST['department_description']);
    $dept_manager_id = isset($_POST['department_manager']) ? intval($_POST['department_manager']) : null;
    $old_dept_name = trim($_POST['old_department_name']);

    if (empty($dept_name)) {
        $error_message = "Department name cannot be empty.";
    } else {
        // Check if department name exists (excluding current department)
        $check_sql = "SELECT COUNT(*) as count FROM departments WHERE name = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $dept_name, $dept_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->fetch_assoc()['count'] > 0;

        if ($exists) {
            $error_message = "Department with this name already exists.";
        } else {
            // Update department
            $update_sql = "UPDATE departments SET name = ?, description = ?, manager_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssii", $dept_name, $dept_description, $dept_manager_id, $dept_id);

            if ($update_stmt->execute()) {
                // Update all employees with old department name
                if ($old_dept_name !== $dept_name) {
                    $update_emp_sql = "UPDATE employees SET department = ? WHERE department = ?";
                    $update_emp_stmt = $conn->prepare($update_emp_sql);
                    $update_emp_stmt->bind_param("ss", $dept_name, $old_dept_name);
                    $update_emp_stmt->execute();
                }

                // If manager is selected, update their department
                if ($dept_manager_id) {
                    $update_manager_sql = "UPDATE employees SET department = ? WHERE id = ?";
                    $update_manager_stmt = $conn->prepare($update_manager_sql);
                    $update_manager_stmt->bind_param("si", $dept_name, $dept_manager_id);
                    $update_manager_stmt->execute();
                }

                $success_message = "Department '{$dept_name}' updated successfully!";
            } else {
                $error_message = "Failed to update department: " . $conn->error;
            }
        }
    }
}

// Delete department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_department'])) {
    $dept_id = intval($_POST['department_id']);
    $dept_name = trim($_POST['department_name']);
    $reassign_to = isset($_POST['reassign_to']) && !empty($_POST['reassign_to']) ? trim($_POST['reassign_to']) : null;

    // Count employees in this department
    $count_sql = "SELECT COUNT(*) as count FROM employees WHERE department = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $dept_name);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $emp_count = $count_result->fetch_assoc()['count'];

    if ($emp_count > 0 && empty($reassign_to)) {
        $error_message = "Cannot delete department with {$emp_count} employees. Please specify a department to reassign them to.";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            if ($emp_count > 0) {
                // Reassign employees to new department
                $reassign_sql = "UPDATE employees SET department = ? WHERE department = ?";
                $reassign_stmt = $conn->prepare($reassign_sql);
                $reassign_stmt->bind_param("ss", $reassign_to, $dept_name);
                $reassign_stmt->execute();
            }

            // Delete department
            $delete_sql = "DELETE FROM departments WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $dept_id);
            $delete_stmt->execute();

            $conn->commit();
            $success_message = "Department '{$dept_name}' deleted successfully! " .
                ($emp_count > 0 ? "{$emp_count} employees have been reassigned to '{$reassign_to}'." : "");
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error deleting department: " . $e->getMessage();
        }
    }
}

// Assign employees to department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_employees'])) {
    $dept_name = trim($_POST['department_name']);
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : [];

    if (empty($employee_ids)) {
        $error_message = "No employees selected for assignment.";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Update each employee's department
            $update_sql = "UPDATE employees SET department = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);

            $success_count = 0;
            foreach ($employee_ids as $emp_id) {
                $update_stmt->bind_param("si", $dept_name, $emp_id);
                if ($update_stmt->execute()) {
                    $success_count++;
                }
            }

            // Commit transaction
            $conn->commit();
            $success_message = "{$success_count} employees assigned to '{$dept_name}' successfully!";
        } catch (Exception $e) {
            // Rollback on failure
            $conn->rollback();
            $error_message = "Failed to assign employees: " . $e->getMessage();
        }
    }
}

// Fetch all departments with manager and employee count
$departments = [];
$dept_sql = "SELECT d.id, d.name, d.description, d.manager_id, 
            e.full_name as manager_name, e.job_position as manager_position,
            (SELECT COUNT(*) FROM employees WHERE department = d.name) as employee_count
            FROM departments d
            LEFT JOIN employees e ON d.manager_id = e.id
            ORDER BY d.name";
$dept_result = $conn->query($dept_sql);

if ($dept_result && $dept_result->num_rows > 0) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = [
            'id' => $row['id'],
            'department_name' => $row['name'],
            'description' => $row['description'],
            'manager_id' => $row['manager_id'],
            'manager_name' => $row['manager_name'],
            'manager_position' => $row['manager_position'],
            'employee_count' => $row['employee_count']
        ];
    }
}

// Fetch potential managers (employees with manager or higher position)
$managers = [];
$manager_sql = "SELECT id, full_name, job_position FROM employees WHERE 
                job_position IN ('Manager', 'Senior Manager', 'Director', 'Executive')
                ORDER BY full_name";
$manager_result = $conn->query($manager_sql);
if ($manager_result && $manager_result->num_rows > 0) {
    while ($row = $manager_result->fetch_assoc()) {
        $managers[] = $row;
    }
}
?>

<?php include '../components/header.php'; ?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-yellow-600">Department Management</h1>
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

        <!-- Department Management Tabs -->
        <div class="mb-6">
            <div class="flex border-b border-gray-200">
                <button onclick="showTab('add')"
                    class="tab-button px-6 py-3 text-yellow-600 border-b-2 border-yellow-600 font-medium">Add
                    Department</button>
                <button onclick="showTab('list')" class="tab-button px-6 py-3 text-gray-500 font-medium">Department
                    List</button>
                <button onclick="showTab('assign')" class="tab-button px-6 py-3 text-gray-500 font-medium">Assign
                    Employees</button>
            </div>
        </div>

        <!-- Add New Department Form -->
        <div id="add-tab" class="tab-content bg-white shadow-lg rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Create New Department</h2>
            <form method="POST" action="" name="add_department_form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="department_name" class="block text-sm font-medium text-gray-700 mb-1">Department
                            Name</label>
                        <input type="text" id="department_name" name="department_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                    <div>
                        <label for="department_description"
                            class="block text-sm font-medium text-gray-700 mb-1">Description (for reference
                            only)</label>
                        <input type="text" id="department_description" name="department_description"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                    <div>
                        <label for="department_manager" class="block text-sm font-medium text-gray-700 mb-1">Department
                            Manager<span class="text-red-500">*</span></label>
                        <select id="department_manager" name="department_manager" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">-- Select Manager --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['id']; ?>">
                                    <?php echo htmlspecialchars($manager['full_name']); ?>
                                    (<?php echo htmlspecialchars($manager['job_position']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-red-500 mt-1">A manager must be assigned to create a department</p>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" name="add_department"
                        class="bg-yellow-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-yellow-700 transition">
                        Create Department
                    </button>
                </div>
            </form>
        </div>

        <!-- Departments List -->
        <div id="list-tab" class="tab-content hidden bg-white shadow-lg rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Department List</h2>
            <?php if (empty($departments)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">No departments found. Create your first department using the form above.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Department Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Manager</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Employees</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($departments as $dept): ?>
                                <tr id="dept-row-<?php echo $dept['id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                        <?php echo htmlspecialchars($dept['description'] ?? ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (!empty($dept['manager_name'])): ?>
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($dept['manager_name']); ?>
                                                (<?php echo htmlspecialchars($dept['manager_position']); ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-500">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo $dept['employee_count']; ?> employees
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button
                                            onclick="editDepartment(<?php echo $dept['id']; ?>, '<?php echo addslashes($dept['department_name']); ?>', '<?php echo addslashes($dept['description'] ?? ''); ?>', <?php echo $dept['manager_id'] ?? 'null'; ?>)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Edit
                                        </button>
                                        <button
                                            onclick="confirmDelete(<?php echo $dept['id']; ?>, '<?php echo addslashes($dept['department_name']); ?>', <?php echo $dept['employee_count']; ?>)"
                                            class="text-red-600 hover:text-red-900" <?php echo ($dept['employee_count'] > 0) ? 'title="This will require reassigning employees"' : ''; ?>>
                                            Delete
                                        </button>
                                        <button onclick="viewEmployees('<?php echo addslashes($dept['department_name']); ?>')"
                                            class="text-yellow-600 hover:text-yellow-900 ml-3">
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
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Assign Employees to Department</h2>

            <?php if (empty($departments)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">No departments available. Please create a department first.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-6">
                        <label for="assign_department_name" class="block text-sm font-medium text-gray-700 mb-1">Select
                            Department</label>
                        <select id="assign_department_name" name="department_name" required
                            class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                    (<?php echo $dept['employee_count']; ?> employees)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Department filter options -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter Employees</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="employee_filter" value="all" checked
                                    class="text-yellow-600 focus:border-yellow-300 focus:ring focus:ring-yellow-200 focus:ring-opacity-50">
                                <span class="ml-2">All Employees</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="employee_filter" value="unassigned"
                                    class="text-yellow-600 focus:border-yellow-300 focus:ring focus:ring-yellow-200 focus:ring-opacity-50">
                                <span class="ml-2">Unassigned Only</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="employee_filter" value="assigned"
                                    class="text-yellow-600 focus:border-yellow-300 focus:ring focus:ring-yellow-200 focus:ring-opacity-50">
                                <span class="ml-2">Assigned Only</span>
                            </label>
                        </div>
                    </div>

                    <!-- Search box -->
                    <div class="mb-6">
                        <label for="employee_search" class="block text-sm font-medium text-gray-700 mb-1">Search
                            Employees</label>
                        <input type="text" id="employee_search" placeholder="Search by name, position, or department..."
                            class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
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
                                            Position</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Current Department</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="employeeCheckboxList">
                                    <?php
                                    // Fetch all employees
                                    $all_employees_sql = "SELECT id, full_name, job_position, department FROM employees ORDER BY full_name";
                                    $all_employees_result = $conn->query($all_employees_sql);

                                    if ($all_employees_result && $all_employees_result->num_rows > 0):
                                        while ($emp = $all_employees_result->fetch_assoc()):
                                            $current_dept = empty($emp['department']) ? '(Unassigned)' : $emp['department'];
                                            $is_unassigned = empty($emp['department']);
                                            ?>
                                            <tr class="employee-row <?php echo $is_unassigned ? 'unassigned' : 'assigned'; ?>"
                                                data-name="<?php echo strtolower(htmlspecialchars($emp['full_name'])); ?>"
                                                data-position="<?php echo strtolower(htmlspecialchars($emp['job_position'])); ?>"
                                                data-department="<?php echo strtolower(htmlspecialchars($current_dept)); ?>">
                                                <td class="px-4 py-2">
                                                    <input type="checkbox" name="employee_ids[]" value="<?php echo $emp['id']; ?>"
                                                        class="employee-checkbox rounded border-gray-300 text-yellow-600 shadow-sm focus:border-yellow-300 focus:ring focus:ring-yellow-200 focus:ring-opacity-50">
                                                </td>
                                                <td class="px-4 py-2"><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                                <td class="px-4 py-2">
                                                    <span
                                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars($emp['job_position']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <?php if ($is_unassigned): ?>
                                                        <span class="text-gray-500 italic"><?php echo $current_dept; ?></span>
                                                    <?php else: ?>
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            <?php echo htmlspecialchars($current_dept); ?>
                                                        </span>
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
                            class="bg-yellow-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-yellow-700 transition">
                            Assign Selected Employees
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Edit Department</h3>
            <form method="POST" action="" id="editForm">
                <input type="hidden" id="edit_department_id" name="department_id">
                <input type="hidden" id="edit_old_department_name" name="old_department_name">
                <div class="mb-4">
                    <label for="edit_department_name" class="block text-sm font-medium text-gray-700 mb-1">Department
                        Name</label>
                    <input type="text" id="edit_department_name" name="department_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div class="mb-4">
                    <label for="edit_department_description"
                        class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" id="edit_department_description" name="department_description"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div class="mb-4">
                    <label for="edit_department_manager" class="block text-sm font-medium text-gray-700 mb-1">Department
                        Manager</label>
                    <select id="edit_department_manager" name="department_manager"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                        <option value="">-- Select Manager --</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager['id']; ?>">
                                <?php echo htmlspecialchars($manager['full_name']); ?>
                                (<?php echo htmlspecialchars($manager['job_position']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" name="update_department"
                        class="bg-yellow-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-yellow-700 transition">
                        Update Department
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Department</h3>
            <p id="deleteConfirmText" class="mb-4 text-gray-600">Are you sure you want to delete this department?</p>

            <form method="POST" action="" id="deleteForm">
                <input type="hidden" id="delete_department_id" name="department_id">
                <input type="hidden" id="delete_department_name" name="department_name">

                <div id="reassignSection" class="mb-4 hidden">
                    <label for="reassign_to" class="block text-sm font-medium text-gray-700 mb-1">Reassign employees
                        to:</label>
                    <select id="reassign_to" name="reassign_to" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">All employees in the deleted department will be moved to this
                        department.</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" name="delete_department"
                        class="bg-red-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-red-700 transition">
                        Delete Department
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
                <h3 class="text-xl font-bold text-gray-900" id="viewEmployeesTitle">Department Employees</h3>
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
            button.classList.remove('text-yellow-600', 'border-b-2', 'border-yellow-600');
            button.classList.add('text-gray-500');
        });

        // Show selected tab
        document.getElementById(tabName + '-tab').classList.remove('hidden');

        // Activate selected tab button
        document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.remove('text-gray-500');
        document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('text-yellow-600', 'border-b-2', 'border-yellow-600');
    }

    function editDepartment(deptId, deptName, deptDescription, managerId) {
        document.getElementById('edit_department_id').value = deptId;
        document.getElementById('edit_old_department_name').value = deptName;
        document.getElementById('edit_department_name').value = deptName;
        document.getElementById('edit_department_description').value = deptDescription || '';

        // Set manager dropdown value
        const managerSelect = document.getElementById('edit_department_manager');
        if (managerId) {
            managerSelect.value = managerId;
        } else {
            managerSelect.value = '';
        }

        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(deptId, deptName, employeeCount) {
        document.getElementById('delete_department_id').value = deptId;
        document.getElementById('delete_department_name').value = deptName;

        // Filter out the current department from reassign options
        const reassignSelect = document.getElementById('reassign_to');
        for (let i = 0; i < reassignSelect.options.length; i++) {
            if (reassignSelect.options[i].value === deptName) {
                reassignSelect.options[i].disabled = true;
            } else {
                reassignSelect.options[i].disabled = false;
            }
        }

        if (employeeCount > 0) {
            document.getElementById('deleteConfirmText').innerText =
                `Are you sure you want to delete the department "${deptName}"? This department has ${employeeCount} employees that need to be reassigned.`;
            document.getElementById('reassignSection').classList.remove('hidden');
            reassignSelect.required = true;
        } else {
            document.getElementById('deleteConfirmText').innerText =
                `Are you sure you want to delete the department "${deptName}"?`;
            document.getElementById('reassignSection').classList.add('hidden');
            reassignSelect.required = false;
        }

        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    function viewEmployees(deptName) {
        const employeesList = document.getElementById('employeesList');
        employeesList.innerHTML = '<p class="text-center py-4 text-gray-500">Loading employees...</p>';

        document.getElementById('viewEmployeesTitle').innerText = `${deptName} - Employees`;
        document.getElementById('viewEmployeesModal').classList.remove('hidden');

        // Fetch employees for this department
        fetch(`get_department_employees.php?department_name=${encodeURIComponent(deptName)}`)
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

        // Filter employees by status (all, assigned, unassigned)
        function filterEmployees() {
            let filterValue = document.querySelector('input[name="employee_filter"]:checked').value;
            let searchText = employeeSearch.value.toLowerCase().trim();

            employeeRows.forEach(row => {
                const name = row.dataset.name;
                const position = row.dataset.position;
                const department = row.dataset.department;
                const isUnassigned = row.classList.contains('unassigned');

                // Check if the row matches the filter
                let matchesFilter = false;
                if (filterValue === 'all' ||
                    (filterValue === 'unassigned' && isUnassigned) ||
                    (filterValue === 'assigned' && !isUnassigned)) {
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

        // Form validation for department creation
        const addDepartmentForm = document.querySelector('form[name="add_department_form"]');
        if (addDepartmentForm) {
            addDepartmentForm.addEventListener('submit', function (event) {
                const managerField = document.getElementById('department_manager');
                if (!managerField.value) {
                    event.preventDefault();
                    alert('Please select a manager for the department.');
                    managerField.focus();
                }
            });
        }
    });
</script>

<?php include '../components/footer.php'; ?>