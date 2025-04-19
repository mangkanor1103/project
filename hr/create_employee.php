<?php include '../components/header.php'; ?>
<main class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="bg-white shadow-lg rounded-lg px-8 py-6 border border-gray-100">
        <div class="flex justify-between items-center border-b pb-3 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Add New Employee</h1>
            <a href="hr_dashboard.php"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-4 py-2 rounded-md shadow-sm hover:shadow transition duration-300 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                        clip-rule="evenodd" />
                </svg>
                Back
            </a>
        </div>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <p class="font-medium">Error</p>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <p class="font-medium">Success</p>
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        <form action="add_employee.php" method="post" enctype="multipart/form-data" class="space-y-8">

            <!-- Basic Information -->
            <fieldset class="bg-gray-50 p-5 rounded-lg shadow-sm">
                <legend
                    class="text-xl font-semibold text-blue-700 px-3 py-1 -ml-1 mb-4 border-b-2 border-blue-200 inline-block">
                    Basic Information</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="full_name" name="full_name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                            required>
                    </div>
                    <div>
                        <label for="dob" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span
                                class="text-red-500">*</span></label>
                        <input type="date" id="dob" name="dob"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                            required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender <span
                                class="text-red-500">*</span></label>
                        <select id="gender" name="gender"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                            required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number
                            <span class="text-red-500">*</span></label>
                        <input type="text" id="contact_number" name="contact_number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                            required>
                    </div>
                </div>
                <div class="mt-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span
                            class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                        required>
                </div>
                <div class="mt-4">
                    <label for="home_address" class="block text-sm font-medium text-gray-700 mb-1">Home Address <span
                            class="text-red-500">*</span></label>
                    <textarea id="home_address" name="home_address" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition"
                        required></textarea>
                </div>
                <div class="mt-4">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
                    <div class="mt-1 flex items-center">
                        <span class="inline-block h-12 w-12 rounded-full overflow-hidden bg-gray-100 mr-3">
                            <svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </span>
                        <input type="file" id="image" name="image" accept="image/*"
                            class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </fieldset>

            <!-- Job Details -->
            <fieldset>
                <legend class="text-lg font-bold">Job Details</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="job_position" class="block text-sm font-medium">Job Position</label>
                        <input type="text" id="job_position" name="job_position"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="department" class="block text-sm font-medium">Department</label>
                        <input type="text" id="department" name="department"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="employee_type" class="block text-sm font-medium">Employee Type</label>
                        <select id="employee_type" name="employee_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Select</option>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Contractual">Contractual</option>
                        </select>
                    </div>
                    <div>
                        <label for="date_hired" class="block text-sm font-medium">Date Hired</label>
                        <input type="date" id="date_hired" name="date_hired"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="work_schedule" class="block text-sm font-medium">Work Schedule</label>
                        <input type="text" id="work_schedule" name="work_schedule"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
            </fieldset>

            <!-- Government Details -->
            <fieldset>
                <legend class="text-lg font-bold">Government Details</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sss_number" class="block text-sm font-medium">SSS Number</label>
                        <input type="text" id="sss_number" name="sss_number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="philhealth_number" class="block text-sm font-medium">PhilHealth Number</label>
                        <input type="text" id="philhealth_number" name="philhealth_number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pagibig_number" class="block text-sm font-medium">Pag-IBIG Number</label>
                        <input type="text" id="pagibig_number" name="pagibig_number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="tin" class="block text-sm font-medium">TIN (Tax Identification Number)</label>
                        <input type="text" id="tin" name="tin"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium">Status</label>
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        required>
                        <option value="">Select</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Divorced">Divorced</option>
                    </select>
                </div>
            </fieldset>

            <!-- Salary & Payment Details -->
            <fieldset>
                <legend class="text-lg font-bold">Salary & Payment Details</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="salary_type" class="block text-sm font-medium">Salary Type</label>
                        <select id="salary_type" name="salary_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Select</option>
                            <option value="Fixed">Fixed</option>
                            <option value="Hourly">Hourly</option>
                            <option value="Commission">Commission</option>
                        </select>
                    </div>
                    <div>
                        <label for="basic_salary" class="block text-sm font-medium">Basic Salary</label>
                        <input type="number" id="basic_salary" name="basic_salary" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div class="grid grid-cols-1">
                    <div>
                        <label for="overtime_bonus" class="block text-sm font-medium">Overtime & Bonus
                            Eligibility</label>
                        <input type="checkbox" id="overtime_bonus" name="overtime_bonus" class="mt-1">
                    </div>
                </div>
            </fieldset>

            <!-- Emergency Contact -->
            <fieldset>
                <legend class="text-lg font-bold">Emergency Contact</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="emergency_name" class="block text-sm font-medium">Name</label>
                        <input type="text" id="emergency_name" name="emergency_name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="emergency_relationship" class="block text-sm font-medium">Relationship</label>
                        <input type="text" id="emergency_relationship" name="emergency_relationship"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div>
                    <label for="emergency_contact" class="block text-sm font-medium">Contact Number</label>
                    <input type="text" id="emergency_contact" name="emergency_contact"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                </div>
            </fieldset>

            <button type="submit"
                class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-md shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                    <path d="M16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                </svg>
                Add Employee
            </button>
        </form>
    </div>
</main>
</body>

</html>