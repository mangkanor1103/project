<?php
session_start();
include '../components/header.php';
include_once '../config.php'; // Ensure database connection is available

// Check if the user is logged in and has the HR admin role
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Fetch distinct departments from the database
$departments = [];
$dept_query = "SELECT DISTINCT department FROM employees WHERE department != '' ORDER BY department";
$dept_result = $conn->query($dept_query);

if ($dept_result && $dept_result->num_rows > 0) {
    while ($row = $dept_result->fetch_assoc()) {
        if (!empty($row['department'])) {
            $departments[] = $row['department'];
        }
    }
}
?>
<style>
    /* Enhanced Form Elements */
    .form-input,
    .form-select,
    .form-textarea {
        transition: all 0.2s;
        border: 2px solid #cbd5e1;
        /* Thicker border with visible color */
        border-radius: 0.375rem;
        padding: 0.625rem 0.875rem;
        /* Slightly more padding for better appearance */
        width: 100%;
        background-color: #ffffff;
        color: #1e293b;
        font-size: 0.95rem;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
    }


    .form-input:hover,
    .form-select:hover,
    .form-textarea:hover {
        border-color: #64748b;
    }

    /* Improved select styling with traditional dropdown */
    .form-select {
        appearance: none;
        background-color: #ffffff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23475569' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        padding-right: 2.5rem;
    }

    /* Custom select styling */
    .select-wrapper {
        position: relative;
    }

    .select-wrapper::after {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1rem;
        height: 1rem;
        pointer-events: none;
        transition: transform 0.2s;
        content: '';
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%232563eb' viewBox='0 0 24 24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
    }

    /* File input preview */
    .image-preview {
        position: relative;
        display: inline-block;
        height: 5rem;
        width: 5rem;
        border-radius: 9999px;
        overflow: hidden;
        background-color: #f3f4f6;
        border: 2px solid #e5e7eb;
    }

    .image-preview svg {
        height: 100%;
        width: 100%;
        color: #d1d5db;
    }

    /* Custom checkbox styling */
    .custom-checkbox {
        position: relative;
    }

    /* Fieldset styling */
    .form-fieldset {
        background-color: #f9fafb;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        transition: all 0.3s;
    }

    .form-fieldset:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        background-color: rgba(249, 250, 251, 0.8);
    }

    .form-fieldset legend {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1d4ed8;
        padding: 0.25rem 1rem;
        background-color: white;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        margin-top: -2.25rem;
        border: 1px solid #e5e7eb;
    }

    /* Form label styling */
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }

    /* Button styling */
    .btn-primary {
        background-color: #2563eb;
        color: white;
        font-weight: 500;
        padding: 0.75rem 2rem;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-primary:hover {
        background-color: #1d4ed8;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Input group with icon */
    .input-group {
        position: relative;
    }

    .input-group-icon {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        padding-left: 0.75rem;
        display: flex;
        align-items: center;
        pointer-events: none;
        color: #6b7280;
    }

    .input-group input {
        padding-left: 1.75rem;
    }

    /* File upload button */
    .file-upload-btn {
        background-color: white;
        border: 1px solid #d1d5db;
        color: #2563eb;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: all 0.2s;
    }

    .file-upload-btn:hover {
        border-color: #3b82f6;
        background-color: #eff6ff;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }
</style>
<main class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="bg-white shadow-lg rounded-lg px-8 py-6 border border-gray-100">
        <div class="flex justify-between items-center border-b pb-4 mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Add New Employee</h1>
            <div class="flex space-x-3">
                <a href="create_multi_employee.php"
                    class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-md shadow-sm hover:shadow transition duration-300 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Bulk Import
                </a>
                <a href="hr_dashboard.php"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-4 py-2 rounded-md shadow-sm hover:shadow transition duration-300 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Back
                </a>
            </div>
        </div>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <p class="font-medium">Error</p>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                <p class="font-medium">Success</p>
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        <form action="add_employee.php" method="post" enctype="multipart/form-data" class="space-y-10">

            <!-- Basic Information -->
            <fieldset class="form-fieldset">
                <legend>Basic Information</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="full_name" class="form-label">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-input" required>
                    </div>
                    <div>
                        <label for="dob" class="form-label">Date of Birth <span class="text-red-500">*</span></label>
                        <input type="date" id="dob" name="dob" class="form-input" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label for="gender" class="form-label">Gender <span class="text-red-500">*</span></label>
                        <select id="gender" name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="contact_number" class="form-label">Contact Number
                            <span class="text-red-500">*</span></label>
                        <input type="text" id="contact_number" name="contact_number" class="form-input" required>
                    </div>
                </div>
                <div class="mt-4">
                    <label for="email" class="form-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <div class="mt-4">
                    <label for="home_address" class="form-label">Home Address <span
                            class="text-red-500">*</span></label>
                    <textarea id="home_address" name="home_address" rows="3" class="form-textarea" required></textarea>
                </div>
                <div class="mt-4">
                    <label for="image" class="form-label">Profile Image</label>
                    <div class="mt-1 flex items-center space-x-4">
                        <div class="image-preview">
                            <svg fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <label class="file-upload-btn">
                                <span>Upload Photo</span>
                                <input type="file" id="image" name="image" accept="image/*" class="sr-only">
                            </label>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 5MB</p>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Job Details -->
            <fieldset class="form-fieldset">
                <legend>Job Details</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="job_position" class="form-label">Job Position
                            <span class="text-red-500">*</span></label>
                        <input type="text" id="job_position" name="job_position" class="form-input" required>
                    </div>
                    <div>
                        <label for="department" class="form-label">Department <span
                                class="text-red-500">*</span></label>
                        <select id="department" name="department" class="form-select" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                        <div id="other_department_container" class="mt-2 hidden">
                            <input type="text" id="other_department" name="other_department"
                                placeholder="Enter new department name" class="form-input border-l-4 border-l-blue-500">
                        </div>
                    </div>
                    <div>
                        <label for="employee_type" class="form-label">Employee Type
                            <span class="text-red-500">*</span></label>
                        <select id="employee_type" name="employee_type" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Contractual">Contractual</option>
                        </select>
                    </div>
                    <div>
                        <label for="date_hired" class="form-label">Date Hired <span
                                class="text-red-500">*</span></label>
                        <input type="date" id="date_hired" name="date_hired" class="form-input" required>
                    </div>
                    <div>
                        <label for="work_schedule" class="form-label">Work Schedule
                            <span class="text-red-500">*</span></label>
                        <input type="text" id="work_schedule" name="work_schedule" class="form-input" required>
                    </div>
                </div>
            </fieldset>

            <!-- Government Details -->
            <fieldset class="form-fieldset">
                <legend>Government Details</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="sss_number" class="form-label">SSS Number <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="sss_number" name="sss_number" class="form-input" required>
                    </div>
                    <div>
                        <label for="philhealth_number" class="form-label">PhilHealth
                            Number <span class="text-red-500">*</span></label>
                        <input type="text" id="philhealth_number" name="philhealth_number" class="form-input" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label for="pagibig_number" class="form-label">Pag-IBIG Number
                            <span class="text-red-500">*</span></label>
                        <input type="text" id="pagibig_number" name="pagibig_number" class="form-input" required>
                    </div>
                    <div>
                        <label for="tin" class="form-label">TIN (Tax Identification
                            Number) <span class="text-red-500">*</span></label>
                        <input type="text" id="tin" name="tin" class="form-input" required>
                    </div>
                </div>
                <div class="mt-4">
                    <label for="status" class="form-label">Marital Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="">Select</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Divorced">Divorced</option>
                    </select>
                </div>
            </fieldset>

            <!-- Salary & Payment Details -->
            <fieldset class="form-fieldset">
                <legend>Salary & Payment Details</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="salary_type" class="form-label">Salary Type <span
                                class="text-red-500">*</span></label>
                        <select id="salary_type" name="salary_type" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Fixed">Fixed</option>
                            <option value="Hourly">Hourly</option>
                            <option value="Commission">Commission</option>
                        </select>
                    </div>
                    <div>
                        <label for="basic_salary" class="form-label">Basic Salary
                            <span class="text-red-500">*</span></label>
                        <div class="input-group">
                            <div class="input-group-icon">
                                <span>₱</span>
                            </div>
                            <input type="number" id="basic_salary" name="basic_salary" step="0.01" class="form-input"
                                required>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="flex items-center space-x-3 py-1 cursor-pointer group">
                        <input type="checkbox" id="overtime_bonus" name="overtime_bonus"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 h-5 w-5 transition-all duration-200 group-hover:border-blue-400">
                        <span
                            class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors duration-200">
                            Eligible for Overtime & Bonuses
                        </span>
                    </label>
                </div>
            </fieldset>

            <!-- Emergency Contact -->
            <fieldset class="form-fieldset">
                <legend>Emergency Contact</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="emergency_name" class="form-label">Name <span class="text-red-500">*</span></label>
                        <input type="text" id="emergency_name" name="emergency_name" class="form-input" required>
                    </div>
                    <div>
                        <label for="emergency_relationship" class="form-label">Relationship <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="emergency_relationship" name="emergency_relationship" class="form-input"
                            required>
                    </div>
                </div>
                <div class="mt-4">
                    <label for="emergency_contact" class="form-label">Contact Number
                        <span class="text-red-500">*</span></label>
                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-input" required>
                </div>
            </fieldset>

            <div class="flex justify-end pt-4">
                <button type="submit" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                        <path d="M16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                    </svg>
                    Add Employee
                </button>
            </div>
        </form>
    </div>
</main>
<script>
    // Show/hide the "Other" department input field with improved transition
    document.getElementById('department').addEventListener('change', function () {
        const otherContainer = document.getElementById('other_department_container');
        const otherField = document.getElementById('other_department');

        if (this.value === 'other') {
            otherContainer.classList.remove('hidden');
            setTimeout(() => {
                otherField.focus();
            }, 100);
            otherField.setAttribute('required', 'required');
        } else {
            otherContainer.classList.add('hidden');
            otherField.removeAttribute('required');
            otherField.value = '';
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Apply classes to input elements
        document.querySelectorAll('input[type="text"], input[type="email"], input[type="number"], input[type="date"], input[type="password"]')
            .forEach(input => {
                if (!input.classList.contains('form-input')) {
                    input.classList.add('form-input');
                }
            });

        // Clean up select elements and remove any double-wrapping
        document.querySelectorAll('select').forEach(select => {
            // Add required classes if they don't exist
            if (!select.classList.contains('form-select')) {
                select.classList.add('form-select');
            }

            // Remove appearance-none class to use new styling
            select.classList.remove('appearance-none');

            // Remove the pr-10 class as we have new padding
            select.classList.remove('pr-10');

            // If select is inside select-wrapper, move it outside (cleanup)
            if (select.parentElement.classList.contains('select-wrapper')) {
                const wrapper = select.parentElement;
                const parent = wrapper.parentElement;
                parent.insertBefore(select, wrapper);
                wrapper.remove();
            }
        });

        // Apply classes to textarea elements
        document.querySelectorAll('textarea').forEach(textarea => {
            if (!textarea.classList.contains('form-textarea')) {
                textarea.classList.add('form-textarea');
            }
        });

        // Create custom image preview functionality
        const imageInput = document.getElementById('image');
        if (imageInput) {
            imageInput.addEventListener('change', function (e) {
                const preview = document.querySelector('.image-preview');
                const file = e.target.files[0];

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        preview.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-cover">`;
                    };

                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
</body>

</html>