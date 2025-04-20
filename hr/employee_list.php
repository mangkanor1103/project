<!-- filepath: c:\xampp\htdocs\project\hr\employee_list.php -->
<?php
session_start();
include '../config.php';
include '../components/header.php'; // Include your header

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Handle delete employee request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee'])) {
    $employee_id = intval($_POST['employee_id']);
    $delete_sql = "DELETE FROM employees WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $employee_id);
    if ($stmt->execute()) {
        $success_message = "Employee deleted successfully!";
    } else {
        $error_message = "Failed to delete employee.";
    }
}

// Handle edit employee request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $employee_id = intval($_POST['employee_id']);
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $home_address = trim($_POST['home_address']);
    $job_position = trim($_POST['job_position']);
    $department = trim($_POST['department']);
    $employee_type = $_POST['employee_type'];
    $date_hired = $_POST['date_hired'];
    $work_schedule = trim($_POST['work_schedule']);
    $sss_number = trim($_POST['sss_number']);
    $philhealth_number = trim($_POST['philhealth_number']);
    $pagibig_number = trim($_POST['pagibig_number']);
    $tin = trim($_POST['tin']);
    $status = $_POST['status'];
    $salary_type = $_POST['salary_type'];
    $basic_salary = $_POST['basic_salary'];
    $overtime_bonus = isset($_POST['overtime_bonus']) ? 1 : 0;
    $emergency_name = trim($_POST['emergency_name']);
    $emergency_relationship = trim($_POST['emergency_relationship']);
    $emergency_contact = trim($_POST['emergency_contact']);

    $update_sql = "UPDATE employees SET 
        full_name = ?, dob = ?, gender = ?, contact_number = ?, email = ?, home_address = ?, 
        job_position = ?, department = ?, employee_type = ?, date_hired = ?, work_schedule = ?, 
        sss_number = ?, philhealth_number = ?, pagibig_number = ?, tin = ?, status = ?, 
        salary_type = ?, basic_salary = ?, overtime_bonus = ?, emergency_name = ?, 
        emergency_relationship = ?, emergency_contact = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param(
        "sssssssssssssssssdisssi",
        $full_name, $dob, $gender, $contact_number, $email, $home_address,
        $job_position, $department, $employee_type, $date_hired, $work_schedule,
        $sss_number, $philhealth_number, $pagibig_number, $tin, $status,
        $salary_type, $basic_salary, $overtime_bonus, $emergency_name,
        $emergency_relationship, $emergency_contact, $employee_id
    );
    if ($stmt->execute()) {
        $success_message = "Employee updated successfully!";
    } else {
        $error_message = "Failed to update employee.";
    }
}

// Fetch employees
$employees = [];
$sql = "SELECT * FROM employees ORDER BY id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $employees = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="bg-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Employee List</h1>
            <a href="hr_dashboard.php"
                class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md shadow-md hover:bg-gray-300 transition">
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <p class="text-green-600 mb-4"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <p class="text-red-600 mb-4"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $employee['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['job_position']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['department']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex justify-end space-x-2">
                                        <!-- Edit Button -->
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($employee)); ?>)"
                                            class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                                            Edit
                                        </button>
                                        <!-- Delete Button -->
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                            <button type="submit" name="delete_employee"
                                                class="bg-red-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-red-700 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-3xl overflow-y-auto max-h-screen">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Employee</h2>
        <form method="POST">
            <input type="hidden" id="edit_employee_id" name="employee_id">
            <!-- Add fields dynamically -->
            <div id="editFieldsContainer"></div>
            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" onclick="closeEditModal()"
                    class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md shadow-md hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" name="edit_employee"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(employee) {
        document.getElementById('edit_employee_id').value = employee.id;
        const fields = [
            { id: 'full_name', label: 'Full Name', type: 'text', value: employee.full_name },
            { id: 'dob', label: 'Date of Birth', type: 'date', value: employee.dob },
            { id: 'gender', label: 'Gender', type: 'select', value: employee.gender, options: ['Male', 'Female', 'Other'] },
            { id: 'contact_number', label: 'Contact Number', type: 'text', value: employee.contact_number },
            { id: 'email', label: 'Email', type: 'email', value: employee.email },
            { id: 'home_address', label: 'Home Address', type: 'textarea', value: employee.home_address },
            { id: 'job_position', label: 'Job Position', type: 'text', value: employee.job_position },
            { id: 'department', label: 'Department', type: 'text', value: employee.department },
            { id: 'employee_type', label: 'Employee Type', type: 'select', value: employee.employee_type, options: ['Regular', 'Probationary', 'Contractual'] },
            { id: 'date_hired', label: 'Date Hired', type: 'date', value: employee.date_hired },
            { id: 'work_schedule', label: 'Work Schedule', type: 'text', value: employee.work_schedule },
            { id: 'sss_number', label: 'SSS Number', type: 'text', value: employee.sss_number },
            { id: 'philhealth_number', label: 'PhilHealth Number', type: 'text', value: employee.philhealth_number },
            { id: 'pagibig_number', label: 'Pag-IBIG Number', type: 'text', value: employee.pagibig_number },
            { id: 'tin', label: 'TIN', type: 'text', value: employee.tin },
            { id: 'status', label: 'Status', type: 'select', value: employee.status, options: ['Single', 'Married', 'Widowed', 'Divorced'] },
            { id: 'salary_type', label: 'Salary Type', type: 'select', value: employee.salary_type, options: ['Fixed', 'Hourly', 'Commission'] },
            { id: 'basic_salary', label: 'Basic Salary', type: 'number', value: employee.basic_salary },
            { id: 'overtime_bonus', label: 'Overtime Bonus', type: 'checkbox', value: employee.overtime_bonus },
            { id: 'emergency_name', label: 'Emergency Contact Name', type: 'text', value: employee.emergency_name },
            { id: 'emergency_relationship', label: 'Emergency Contact Relationship', type: 'text', value: employee.emergency_relationship },
            { id: 'emergency_contact', label: 'Emergency Contact Number', type: 'text', value: employee.emergency_contact }
        ];

        const container = document.getElementById('editFieldsContainer');
        container.innerHTML = '';
        fields.forEach(field => {
            let input;
            if (field.type === 'select') {
                input = `<select id="edit_${field.id}" name="${field.id}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">`;
                field.options.forEach(option => {
                    input += `<option value="${option}" ${option === field.value ? 'selected' : ''}>${option}</option>`;
                });
                input += '</select>';
            } else if (field.type === 'textarea') {
                input = `<textarea id="edit_${field.id}" name="${field.id}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">${field.value}</textarea>`;
            } else if (field.type === 'checkbox') {
                input = `<input type="checkbox" id="edit_${field.id}" name="${field.id}" ${field.value ? 'checked' : ''} class="block mt-1">`;
            } else {
                input = `<input type="${field.type}" id="edit_${field.id}" name="${field.id}" value="${field.value}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">`;
            }

            container.innerHTML += `
                <div class="mb-4">
                    <label for="edit_${field.id}" class="block text-sm font-medium text-gray-700">${field.label}</label>
                    ${input}
                </div>
            `;
        });

        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>

<?php include '../components/footer.php'; ?>