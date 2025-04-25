<?php
session_start();
include '../config.php';
include '../components/header.php';
include 'check_permission.php';
// Check if the user is logged in as HR admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

requirePermission('view_employees');
$success_message = $error_message = "";

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
        $full_name,
        $dob,
        $gender,
        $contact_number,
        $email,
        $home_address,
        $job_position,
        $department,
        $employee_type,
        $date_hired,
        $work_schedule,
        $sss_number,
        $philhealth_number,
        $pagibig_number,
        $tin,
        $status,
        $salary_type,
        $basic_salary,
        $overtime_bonus,
        $emergency_name,
        $emergency_relationship,
        $emergency_contact,
        $employee_id
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

// Get departments for filter
$departments = [];
$dept_sql = "SELECT DISTINCT department FROM employees ORDER BY department";
$dept_result = $conn->query($dept_sql);
if ($dept_result && $dept_result->num_rows > 0) {
    while ($row = $dept_result->fetch_assoc()) {
        if (!empty($row['department'])) {
            $departments[] = $row['department'];
        }
    }
}
?>

<main class="bg-gray-100 min-h-screen py-6 sm:py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Employee Management</h1>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">

                <a href="hr_dashboard.php"
                    class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow hover:bg-gray-300 transition flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
                <div class="flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <p><?php echo $success_message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
                <div class="flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <p><?php echo $error_message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Data Table Card -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Table Controls -->
            <div
                class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between space-y-3 sm:space-y-0">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" id="searchInput" placeholder="Search employees..."
                            class="pl-10 pr-3 py-2 w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Department Filter -->
                    <div class="w-full sm:w-48">
                        <select id="departmentFilter"
                            class="w-full rounded-lg border border-gray-300 py-2 px-3 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex items-center">
                    <!-- Entries per page -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Show</span>
                        <select id="entriesPerPage"
                            class="rounded border border-gray-300 py-1 px-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-sm text-gray-600">entries</span>
                    </div>
                </div>
            </div>

            <!-- Table Container with Overflow -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="employeeTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                data-sort="id">
                                ID <span class="sort-icon">↕</span>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                data-sort="name">
                                Full Name <span class="sort-icon">↕</span>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                data-sort="email">
                                Email <span class="sort-icon">↕</span>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                data-sort="position">
                                Position <span class="sort-icon">↕</span>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                data-sort="department">
                                Department <span class="sort-icon">↕</span>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                data-sort="type">
                                Type <span class="sort-icon">↕</span>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="tableBody">
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $employee['id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($employee['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($employee['job_position']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($employee['department']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="<?php
                                        echo $employee['employee_type'] === 'Regular' ? 'bg-green-100 text-green-800' :
                                            ($employee['employee_type'] === 'Probationary' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-blue-100 text-blue-800');
                                        ?> px-2 py-1 text-xs font-medium rounded-full">
                                            <?php echo htmlspecialchars($employee['employee_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <!-- View Button -->
                                            <button
                                                onclick="openViewModal(<?php echo htmlspecialchars(json_encode($employee)); ?>)"
                                                class="text-teal-600 hover:text-teal-800 p-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    <path fill-rule="evenodd"
                                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Edit Button -->
                                            <button
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($employee)); ?>)"
                                                class="text-blue-600 hover:text-blue-800 p-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path
                                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <form method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                                <button type="submit" name="delete_employee"
                                                    class="text-red-600 hover:text-red-800 p-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                        fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No employees found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex flex-col sm:flex-row justify-between items-center">
                    <div class="text-sm text-gray-700 mb-4 sm:mb-0">
                        Showing <span class="font-medium" id="showing-start">1</span> to <span class="font-medium"
                            id="showing-end">10</span> of
                        <span class="font-medium" id="total-entries"><?php echo count($employees); ?></span> entries
                    </div>
                    <div class="inline-flex rounded-md shadow-sm">
                        <button id="prevPage"
                            class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            Previous
                        </button>
                        <div id="pageNumbers" class="hidden sm:flex"></div>
                        <button id="nextPage"
                            class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Improved Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-bold text-gray-800">Edit Employee</h2>
            <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto p-6" style="max-height: calc(90vh - 130px);">
            <form method="POST">
                <input type="hidden" id="edit_employee_id" name="employee_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="editFieldsContainer"></div>

                <div class="flex justify-end space-x-3 mt-8 pt-5 border-t border-gray-200">
                    <button type="button" onclick="closeEditModal()"
                        class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow hover:bg-gray-300 transition">
                        Cancel
                    </button>
                    <button type="submit" name="edit_employee"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Employee Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-bold text-gray-800">Employee Details</h2>
            <button type="button" onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto p-6" style="max-height: calc(90vh - 130px);">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="viewDetailsContainer">
                <!-- Details will be inserted here by JavaScript -->
            </div>

            <div class="mt-8 pt-5 border-t border-gray-200">
                <button type="button" onclick="closeViewModal()"
                    class="w-full bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow hover:bg-gray-300 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables to track table state
    let currentPage = 1;
    let totalPages = 1;
    let itemsPerPage = 10;
    let allEmployees = <?php echo json_encode($employees); ?>;
    let filteredEmployees = [...allEmployees];
    let sortField = 'id';
    let sortDirection = 'asc';

    // Initialize the table when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        initializeTable();

        // Add event listeners for search and filters
        document.getElementById('searchInput').addEventListener('input', filterEmployees);
        document.getElementById('departmentFilter').addEventListener('change', filterEmployees);
        document.getElementById('entriesPerPage').addEventListener('change', changeEntriesPerPage);
        document.getElementById('prevPage').addEventListener('click', goToPrevPage);
        document.getElementById('nextPage').addEventListener('click', goToNextPage);

        // Add event listeners for sorting
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', function () {
                const field = this.dataset.sort;
                if (sortField === field) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortField = field;
                    sortDirection = 'asc';
                }
                sortEmployees();
            });
        });
    });

    function initializeTable() {
        calculateTotalPages();
        renderPagination();
        renderTableRows();
    }

    function filterEmployees() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const department = document.getElementById('departmentFilter').value;

        filteredEmployees = allEmployees.filter(employee => {
            const matchesSearch =
                employee.full_name.toLowerCase().includes(searchTerm) ||
                employee.email.toLowerCase().includes(searchTerm) ||
                employee.job_position.toLowerCase().includes(searchTerm);

            const matchesDepartment = !department || employee.department === department;

            return matchesSearch && matchesDepartment;
        });

        currentPage = 1;
        calculateTotalPages();
        renderPagination();
        renderTableRows();
    }

    function changeEntriesPerPage() {
        itemsPerPage = parseInt(this.value);
        currentPage = 1;
        calculateTotalPages();
        renderPagination();
        renderTableRows();
    }

    function calculateTotalPages() {
        totalPages = Math.max(1, Math.ceil(filteredEmployees.length / itemsPerPage));
    }

    function goToPrevPage() {
        if (currentPage > 1) {
            currentPage--;
            renderPagination();
            renderTableRows();
        }
    }

    function goToNextPage() {
        if (currentPage < totalPages) {
            currentPage++;
            renderPagination();
            renderTableRows();
        }
    }

    function goToPage(page) {
        currentPage = page;
        renderPagination();
        renderTableRows();
    }

    function renderPagination() {
        const pageNumbers = document.getElementById('pageNumbers');
        pageNumbers.innerHTML = '';

        // Update showing text
        const start = (currentPage - 1) * itemsPerPage + 1;
        const end = Math.min(start + itemsPerPage - 1, filteredEmployees.length);
        document.getElementById('showing-start').textContent = filteredEmployees.length > 0 ? start : 0;
        document.getElementById('showing-end').textContent = end;
        document.getElementById('total-entries').textContent = filteredEmployees.length;

        // Disable/enable pagination buttons
        document.getElementById('prevPage').disabled = currentPage === 1;
        document.getElementById('prevPage').classList.toggle('opacity-50', currentPage === 1);
        document.getElementById('nextPage').disabled = currentPage === totalPages;
        document.getElementById('nextPage').classList.toggle('opacity-50', currentPage === totalPages);

        // Generate page numbers (show max 5 pages with current page in the middle when possible)
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);

        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium';

            if (i === currentPage) {
                button.classList.add('z-10', 'bg-teal-50', 'border-teal-500', 'text-teal-600');
            } else {
                button.classList.add('text-gray-500', 'hover:bg-gray-50');
            }

            button.addEventListener('click', () => goToPage(i));
            pageNumbers.appendChild(button);
        }
    }

    function sortEmployees() {
        filteredEmployees.sort((a, b) => {
            let fieldA, fieldB;

            // Map sort fields to employee object properties
            switch (sortField) {
                case 'id':
                    fieldA = parseInt(a.id);
                    fieldB = parseInt(b.id);
                    break;
                case 'name':
                    fieldA = a.full_name.toLowerCase();
                    fieldB = b.full_name.toLowerCase();
                    break;
                case 'email':
                    fieldA = a.email.toLowerCase();
                    fieldB = b.email.toLowerCase();
                    break;
                case 'position':
                    fieldA = a.job_position.toLowerCase();
                    fieldB = b.job_position.toLowerCase();
                    break;
                case 'department':
                    fieldA = a.department.toLowerCase();
                    fieldB = b.department.toLowerCase();
                    break;
                case 'type':
                    fieldA = a.employee_type.toLowerCase();
                    fieldB = b.employee_type.toLowerCase();
                    break;
                default:
                    fieldA = a.id;
                    fieldB = b.id;
            }

            // Compare based on direction
            if (fieldA < fieldB) return sortDirection === 'asc' ? -1 : 1;
            if (fieldA > fieldB) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        // Update table
        renderTableRows();

        // Update sort indicators
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.textContent = '↕';
        });

        const currentSortHeader = document.querySelector(`th[data-sort="${sortField}"]`);
        if (currentSortHeader) {
            const icon = currentSortHeader.querySelector('.sort-icon');
            icon.textContent = sortDirection === 'asc' ? '↑' : '↓';
        }
    }

    function renderTableRows() {
        const tableBody = document.getElementById('tableBody');
        tableBody.innerHTML = '';

        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, filteredEmployees.length);
        const displayedEmployees = filteredEmployees.slice(start, end);

        if (displayedEmployees.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="7" class="px-6 py-4 text-center text-gray-500">No employees found.</td>`;
            tableBody.appendChild(row);
            return;
        }

        displayedEmployees.forEach(employee => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';

            let employeeTypeClass = '';
            switch (employee.employee_type) {
                case 'Regular':
                    employeeTypeClass = 'bg-green-100 text-green-800';
                    break;
                case 'Probationary':
                    employeeTypeClass = 'bg-yellow-100 text-yellow-800';
                    break;
                default:
                    employeeTypeClass = 'bg-blue-100 text-blue-800';
            }

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${employee.id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${employee.full_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${employee.email}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${employee.job_position}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${employee.department}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="${employeeTypeClass} px-2 py-1 text-xs font-medium rounded-full">
                        ${employee.employee_type}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                        <button onclick='openViewModal(${JSON.stringify(employee).replace(/'/g, "\\'")})' 
                            class="text-teal-600 hover:text-teal-800 p-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button onclick='openEditModal(${JSON.stringify(employee).replace(/'/g, "\\'")})'
                            class="text-blue-600 hover:text-blue-800 p-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                            <input type="hidden" name="employee_id" value="${employee.id}">
                            <button type="submit" name="delete_employee" class="text-red-600 hover:text-red-800 p-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

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
                input = `<select id="edit_${field.id}" name="${field.id}" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">`;
                field.options.forEach(option => {
                    input += `<option value="${option}" ${option === field.value ? 'selected' : ''}>${option}</option>`;
                });
                input += '</select>';
            } else if (field.type === 'textarea') {
                input = `<textarea id="edit_${field.id}" name="${field.id}" rows="3" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">${field.value || ''}</textarea>`;
            } else if (field.type === 'checkbox') {
                input = `
                    <div class="flex items-center">
                        <input type="checkbox" id="edit_${field.id}" name="${field.id}" ${parseInt(field.value) ? 'checked' : ''}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="edit_${field.id}" class="ml-2 text-sm text-gray-700">Enable</label>
                    </div>`;
            } else {
                input = `<input type="${field.type}" id="edit_${field.id}" name="${field.id}" value="${field.value || ''}" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">`;
            }

            const fieldElement = document.createElement('div');
            fieldElement.innerHTML = `
                <label for="edit_${field.id}" class="block text-sm font-medium text-gray-700 mb-1">${field.label}</label>
                ${input}
            `;
            container.appendChild(fieldElement);
        });

        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function openViewModal(employee) {
        const container = document.getElementById('viewDetailsContainer');
        container.innerHTML = '';

        // Define field groups for better organization
        const personalFields = [
            { label: 'Full Name', value: employee.full_name },
            { label: 'Email', value: employee.email },
            { label: 'Date of Birth', value: formatDate(employee.dob) },
            { label: 'Gender', value: employee.gender },
            { label: 'Contact Number', value: employee.contact_number },
            { label: 'Home Address', value: employee.home_address },
            { label: 'Status', value: employee.status }
        ];

        const employmentFields = [
            { label: 'Employee ID', value: employee.id },
            { label: 'Job Position', value: employee.job_position },
            { label: 'Department', value: employee.department },
            { label: 'Employee Type', value: employee.employee_type },
            { label: 'Date Hired', value: formatDate(employee.date_hired) },
            { label: 'Work Schedule', value: employee.work_schedule }
        ];

        const benefitsFields = [
            { label: 'SSS Number', value: employee.sss_number },
            { label: 'PhilHealth Number', value: employee.philhealth_number },
            { label: 'Pag-IBIG Number', value: employee.pagibig_number },
            { label: 'TIN', value: employee.tin }
        ];

        const compensationFields = [
            { label: 'Salary Type', value: employee.salary_type },
            { label: 'Basic Salary', value: formatCurrency(employee.basic_salary) },
            { label: 'Overtime Bonus', value: parseInt(employee.overtime_bonus) ? 'Yes' : 'No' }
        ];

        const emergencyFields = [
            { label: 'Emergency Contact Name', value: employee.emergency_name },
            { label: 'Emergency Contact Relationship', value: employee.emergency_relationship },
            { label: 'Emergency Contact Number', value: employee.emergency_contact }
        ];

        // Create sections
        createSection('Personal Information', personalFields, container);
        createSection('Employment Details', employmentFields, container);
        createSection('Benefits Information', benefitsFields, container);
        createSection('Compensation', compensationFields, container);
        createSection('Emergency Contact', emergencyFields, container);

        document.getElementById('viewModal').classList.remove('hidden');
    }

    function createSection(title, fields, container) {
        const section = document.createElement('div');
        section.className = 'col-span-1 md:col-span-2 mt-4 first:mt-0';

        const titleEl = document.createElement('h3');
        titleEl.className = 'text-lg font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-200';
        titleEl.textContent = title;
        section.appendChild(titleEl);

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-1 md:grid-cols-2 gap-4';

        fields.forEach(field => {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-2';

            const labelSpan = document.createElement('span');
            labelSpan.className = 'block text-sm font-medium text-gray-500';
            labelSpan.textContent = field.label;
            fieldDiv.appendChild(labelSpan);

            const valueSpan = document.createElement('span');
            valueSpan.className = 'block text-base text-gray-800';
            valueSpan.textContent = field.value || 'Not provided';
            fieldDiv.appendChild(valueSpan);

            grid.appendChild(fieldDiv);
        });

        section.appendChild(grid);
        container.appendChild(section);
    }

    function formatDate(dateString) {
        if (!dateString) return 'Not provided';

        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Return original if invalid

        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatCurrency(amount) {
        if (!amount) return '₱0.00';

        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
</script>

<?php include '../components/footer.php'; ?>