<?php
session_start();
include '../config.php';

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

?>

<?php include '../components/header.php'; ?>

<main class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Dashboard Header -->
        <div class="flex flex-col items-center mb-10">
            <h1 class="text-4xl font-bold text-blue-700 mb-2">HR Admin Dashboard</h1>
            <div class="h-1 w-24 bg-blue-600 rounded-full mb-2"></div>
            <p class="text-gray-600 text-lg">Manage employees and approvals</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <?php
            // Get employee count
            $employee_count = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];

            // Get pending leave requests count
            $pending_leaves = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'Pending'")->fetch_assoc()['count'];

            // Get departments count
            $departments = $conn->query("SELECT COUNT(DISTINCT department) as count FROM employees")->fetch_assoc()['count'];
            ?>

            <!-- Employee Count -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Total Employees</h3>
                        <p class="text-3xl font-bold text-blue-700"><?php echo $employee_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Leaves -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="bg-amber-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Pending Leaves</h3>
                        <p class="text-3xl font-bold text-amber-600"><?php echo $pending_leaves; ?></p>
                    </div>
                </div>
            </div>

            <!-- Departments -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-700">Departments</h3>
                        <p class="text-3xl font-bold text-green-600"><?php echo $departments; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- Create Employee -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Create Employee</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Add new employees to the system with all their details and
                        credentials.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <a href="create_employee.php"
                            class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-blue-700 hover:to-blue-800 transition-all flex items-center">
                            <span>Create Employee</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Assign Department Manager -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Assign Manager</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Promote qualified employees to department managers to enhance
                        workflow.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-purple-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <a href="manager.php"
                            class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-purple-700 hover:to-purple-800 transition-all flex items-center">
                            <span>Assign Manager</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Leave Approval -->
            <div
                class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all transform hover:-translate-y-1 duration-200">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Leave Requests</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Review and manage employee leave requests for proper attendance
                        tracking.</p>
                    <div class="flex justify-between items-center">
                        <div class="text-green-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <a href="approval.php"
                            class="bg-gradient-to-r from-green-600 to-green-700 text-white px-4 py-2 rounded-lg shadow-md hover:from-green-700 hover:to-green-800 transition-all flex items-center">
                            <span>Manage Leaves</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee List -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 mb-10">
    <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4 flex justify-between items-center">
        <h2 class="text-xl font-bold text-white">Employee Directory</h2>
        <div class="text-gray-300 text-sm">Total: 
            <?php 
            $employee_count = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count']; 
            echo $employee_count; 
            ?> employees
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $modals = ''; // Collect modals here
                $query = "SELECT * FROM employees ORDER BY full_name ASC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $employee_id = $row['id'];
                        echo '<tr class="hover:bg-gray-50">';
                        echo '<td class="px-6 py-4 whitespace-nowrap">';
                        echo '<div class="flex items-center">';
                        // Avatar/Image
                        echo '<div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 overflow-hidden">';
                        if (!empty($row['image'])) {
                            echo '<img class="h-10 w-10 object-cover" src="../uploads/' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['full_name']) . '">';
                        } else {
                            echo '<svg class="h-10 w-10 text-gray-500 p-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>';
                        }
                        echo '</div>';
                        // Name and email
                        echo '<div class="ml-4">';
                        echo '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($row['full_name']) . '</div>';
                        echo '<div class="text-sm text-gray-500">' . htmlspecialchars($row['email']) . '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</td>';
                        // Position
                        echo '<td class="px-6 py-4 whitespace-nowrap">' . htmlspecialchars($row['job_position']) . '</td>';
                        // Department
                        echo '<td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">' . htmlspecialchars($row['department']) . '</span></td>';
                        // Contact
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['contact_number']) . '</td>';
                        // Actions
                        echo '<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">';
                        echo '<button onclick="document.getElementById(\'modal-' . $employee_id . '\').classList.remove(\'hidden\')" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 rounded-lg px-3 py-2 transition-colors">Edit</button>';
                        echo '</td>';
                        echo '</tr>';

                        $modals .= "
<div id='modal-$employee_id' class='fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50'>
    <div class='bg-white w-full max-w-2xl p-6 rounded-xl shadow-2xl overflow-y-auto max-h-[90vh]'>
        <h2 class='text-xl font-bold mb-4'>Edit Employee</h2>
        <form action='edit_employee_action.php?id=" . $employee_id . "' method='POST' enctype='multipart/form-data'>
            <input type='hidden' name='id' value='" . $employee_id . "'>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Full Name</label>
                    <input type='text' name='full_name' value='" . htmlspecialchars($row['full_name']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Date of Birth</label>
                    <input type='date' name='dob' value='" . htmlspecialchars($row['dob']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Gender</label>
                    <select name='gender' class='block w-full mt-1 border-gray-300 rounded-md'>
                        <option value='Male' " . ($row['gender'] == 'Male' ? 'selected' : '') . ">Male</option>
                        <option value='Female' " . ($row['gender'] == 'Female' ? 'selected' : '') . ">Female</option>
                    </select>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Contact Number</label>
                    <input type='text' name='contact_number' value='" . htmlspecialchars($row['contact_number']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Email</label>
                    <input type='email' name='email' value='" . htmlspecialchars($row['email']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Home Address</label>
                    <textarea name='home_address' class='block w-full mt-1 border-gray-300 rounded-md'>" . htmlspecialchars($row['home_address']) . "</textarea>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Job Position</label>
                    <input type='text' name='job_position' value='" . htmlspecialchars($row['job_position']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Department</label>
                    <input type='text' name='department' value='" . htmlspecialchars($row['department']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Date Hired</label>
                    <input type='date' name='date_hired' value='" . htmlspecialchars($row['date_hired']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>SSS Number</label>
                    <input type='text' name='sss_number' value='" . htmlspecialchars($row['sss_number']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>PhilHealth Number</label>
                    <input type='text' name='philhealth_number' value='" . htmlspecialchars($row['philhealth_number']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Pag-IBIG Number</label>
                    <input type='text' name='pagibig_number' value='" . htmlspecialchars($row['pagibig_number']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>TIN</label>
                    <input type='text' name='tin' value='" . htmlspecialchars($row['tin']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
                <div>
                    <label class='block text-sm font-medium text-gray-700'>Basic Salary</label>
                    <input type='number' name='basic_salary' step='0.01' value='" . htmlspecialchars($row['basic_salary']) . "' class='block w-full mt-1 border-gray-300 rounded-md'>
                </div>
            </div>
            <div class='mt-4'>
                <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg'>Save Changes</button>
                <button type='button' onclick=\"document.getElementById('modal-$employee_id').classList.add('hidden')\" class='bg-gray-600 text-white px-4 py-2 rounded-lg'>Cancel</button>
            </div>
        </form>
    </div>
</div>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center px-6 py-4'>No employees found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php echo $modals; ?>
</div>
        <?php
        // Output all modals
        echo $modals;
        ?>

        <div class="flex justify-center space-x-4 mt-10">
            <a href="../index.php"
                class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                        clip-rule="evenodd" />
                </svg>
                Back to Home
            </a>
            <a href="logout.php"
                class="bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-3 rounded-lg hover:from-red-700 hover:to-red-800 transition flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </a>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>