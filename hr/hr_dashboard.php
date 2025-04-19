<?php
session_start();
include '../config.php';

// Check if the HR Admin is logged in
if (!isset($_SESSION['hr_loggedin']) || $_SESSION['hr_loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<?php include '../components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <section class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-6 text-center">HR Admin Dashboard</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <!-- Create Employee -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">Create Employee</h2>
                <p class="text-gray-700 mb-4">Add a new employee to the system.</p>
                <a href="create_employee.php" class="block bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition text-center">
                    Create Employee
                </a>
            </div>
            <!-- Assign Department Manager -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">Assign Department Manager</h2>
                <p class="text-gray-700 mb-4">Promote an employee to Department Manager.</p>
                <a href="manager.php" class="block bg-blue-600 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700 transition text-center">
                    Assign Manager
                </a>
            </div>
            <!-- Leave Approval -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-blue-600 mb-4">Approve Leave Requests</h2>
                <p class="text-gray-700 mb-4">Review and manage leave requests submitted by employees.</p>
                <a href="approval.php" class="block bg-green-600 text-white px-4 py-2 rounded shadow-md hover:bg-green-700 transition text-center">
                    Approve Leave Requests
                </a>
            </div>
        </div>

        <!-- Employee List -->
        <div class="bg-white shadow-md rounded-lg p-6 overflow-x-auto">
            <h2 class="text-2xl font-bold text-blue-600 mb-4 text-center">Employee List</h2>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Position</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Department</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Email</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Contact</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    $modals = ''; // We'll collect modals here and echo them AFTER the table
                    $query = "SELECT * FROM employees ORDER BY full_name ASC";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $employee_id = $row['id'];
                            echo "<tr>";
                            echo "<td class='px-4 py-2 text-gray-800'>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td class='px-4 py-2 text-gray-800'>" . htmlspecialchars($row['job_position']) . "</td>";
                            echo "<td class='px-4 py-2 text-gray-800'>" . htmlspecialchars($row['department']) . "</td>";
                            echo "<td class='px-4 py-2 text-gray-800'>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td class='px-4 py-2 text-gray-800'>" . htmlspecialchars($row['contact_number']) . "</td>";
                            echo "<td class='px-4 py-2 text-gray-800'>";
                            echo "<button onclick=\"document.getElementById('modal-$employee_id').classList.remove('hidden')\" class='bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded'>View</button>";
                            echo "</td>";
                            echo "</tr>";

                            // Collect modal HTML to be rendered after the table
                            $modals .= "
                            <div id='modal-$employee_id' class='fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50'>
                                <div class='bg-white w-full max-w-2xl p-6 rounded-lg shadow-lg overflow-y-auto max-h-[90vh]'>
                                    <h2 class='text-2xl font-bold mb-4 text-center text-blue-600'>Employee Details</h2>
                                    <table class='w-full text-sm'>
                                        <tr><td class='font-semibold py-1'>Full Name:</td><td>" . htmlspecialchars($row['full_name']) . "</td></tr>
                                        <tr><td class='font-semibold py-1'>Date of Birth:</td><td>" . htmlspecialchars($row['dob']) . "</td></tr>
                                        <tr><td class='font-semibold py-1'>Gender:</td><td>" . htmlspecialchars($row['gender']) . "</td></tr>
                                        <tr><td class='font-semibold py-1'>Contact:</td><td>" . htmlspecialchars($row['contact_number']) . "</td></tr>
                                        <tr><td class='font-semibold py-1'>Email:</td><td>" . htmlspecialchars($row['email']) . "</td></tr>
                                        <tr><td class='font-semibold py-1'>Address:</td><td>" . htmlspecialchars($row['home_address']) . "</td></tr>
                                        <tr><td class='font-semibold py-1'>Position:</td><td>" . htmlspecialchars($row['job_position']) . "</td></tr>
                                        <tr><td class='font-semibold py-1'>Department:</td><td>" . htmlspecialchars($row['department']) . "</td></tr>
                                    </table>
                                    <div class='text-center mt-4'>
                                        <button onclick=\"document.getElementById('modal-$employee_id').classList.add('hidden')\" class='mt-4 px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700'>Close</button>
                                    </div>
                                </div>
                            </div>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center px-4 py-4 text-gray-500'>No employees found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <?php
            // Output all modals
            echo $modals;
            ?>
        </div>

        <div class="text-center mt-6">
            <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition">
                Logout
            </a>
        </div>
    </section>
</main>

<?php include '../components/footer.php'; ?>