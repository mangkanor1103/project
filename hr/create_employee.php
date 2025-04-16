<?php
session_start();

// Check if the HR Admin is logged in
if (!isset($_SESSION['hr_loggedin']) || $_SESSION['hr_loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include the database connection file
include '../config.php'; // Ensure the path is correct
include '../components/header.php'; // Include header for consistent layout
// Initialize variables
$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $dob = htmlspecialchars(trim($_POST['dob']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $contact_number = htmlspecialchars(trim($_POST['contact_number']));
    $email = htmlspecialchars(trim($_POST['email']));
    $home_address = htmlspecialchars(trim($_POST['home_address']));
    $image = isset($_FILES['image']) ? $_FILES['image']['name'] : null;

    $job_position = htmlspecialchars(trim($_POST['job_position']));
    $department = htmlspecialchars(trim($_POST['department']));
    $employee_type = htmlspecialchars(trim($_POST['employee_type']));
    $date_hired = htmlspecialchars(trim($_POST['date_hired']));
    $work_schedule = htmlspecialchars(trim($_POST['work_schedule']));

    $sss_number = htmlspecialchars(trim($_POST['sss_number']));
    $philhealth_number = htmlspecialchars(trim($_POST['philhealth_number']));
    $pagibig_number = htmlspecialchars(trim($_POST['pagibig_number']));
    $tin = htmlspecialchars(trim($_POST['tin']));
    $status = htmlspecialchars(trim($_POST['status']));

    $salary_type = htmlspecialchars(trim($_POST['salary_type']));
    $basic_salary = htmlspecialchars(trim($_POST['basic_salary']));
    $overtime_bonus = isset($_POST['overtime_bonus']) ? 1 : 0;

    $emergency_name = htmlspecialchars(trim($_POST['emergency_name']));
    $emergency_relationship = htmlspecialchars(trim($_POST['emergency_relationship']));
    $emergency_contact = htmlspecialchars(trim($_POST['emergency_contact']));

    // Validate required fields
    if (empty($full_name) || empty($dob) || empty($gender) || empty($contact_number) || empty($email) ||
        empty($home_address) || empty($job_position) || empty($department) || empty($employee_type) || 
        empty($date_hired) || empty($sss_number) || empty($philhealth_number) || empty($pagibig_number) || 
        empty($tin) || empty($status) || empty($salary_type) || empty($basic_salary) || 
        empty($emergency_name) || empty($emergency_relationship) || empty($emergency_contact)) {
        $error_message = "All required fields must be filled out.";
    } else {
        // Handle file upload if image is provided
        if ($image) {
            $target_dir = "../uploads/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($image_file_type, $allowed_types)) {
                $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } else {
                // Move the uploaded file
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $error_message = "Error uploading image.";
                }
            }
        }

        // Insert data into the database if no errors
        if (empty($error_message)) {
            try {
                $sql = "INSERT INTO employee (
                            full_name, dob, gender, contact_number, email, home_address, image, 
                            job_position, department, employee_type, date_hired, work_schedule, 
                            sss_number, philhealth_number, pagibig_number, tin, status, 
                            salary_type, basic_salary, overtime_bonus, 
                            emergency_name, emergency_relationship, emergency_contact
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssssssssssssdss",
                    $full_name, $dob, $gender, $contact_number, $email, $home_address, $image,
                    $job_position, $department, $employee_type, $date_hired, $work_schedule,
                    $sss_number, $philhealth_number, $pagibig_number, $tin, $status,
                    $salary_type, $basic_salary, $overtime_bonus,
                    $emergency_name, $emergency_relationship, $emergency_contact
                );

                if ($stmt->execute()) {
                    $success_message = "Employee added successfully.";
                    header("Location: hr_dashboard.php?message=" . urlencode($success_message));
                    exit();
                } else {
                    $error_message = "Error: Could not add employee.";
                }
            } catch (Exception $e) {
                $error_message = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-md rounded px-8 py-6">
        <h1 class="text-2xl font-bold mb-6">Add New Employee</h1>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <form action="add_employee.php" method="post" enctype="multipart/form-data" class="space-y-6">
            
            <!-- Basic Information -->
            <fieldset>
                <legend class="text-lg font-bold">Basic Information</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="full_name" class="block text-sm font-medium">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="dob" class="block text-sm font-medium">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="gender" class="block text-sm font-medium">Gender</label>
                        <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="contact_number" class="block text-sm font-medium">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input type="email" id="email" name="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                </div>
                <div>
                    <label for="home_address" class="block text-sm font-medium">Home Address</label>
                    <textarea id="home_address" name="home_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required></textarea>
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium">Image (Optional)</label>
                    <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </fieldset>
            
            <!-- Government Details -->
            <fieldset>
                <legend class="text-lg font-bold">Government Details</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sss_number" class="block text-sm font-medium">SSS Number</label>
                        <input type="text" id="sss_number" name="sss_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="philhealth_number" class="block text-sm font-medium">PhilHealth Number</label>
                        <input type="text" id="philhealth_number" name="philhealth_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pagibig_number" class="block text-sm font-medium">Pag-IBIG Number</label>
                        <input type="text" id="pagibig_number" name="pagibig_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="tin" class="block text-sm font-medium">TIN (Tax Identification Number)</label>
                        <input type="text" id="tin" name="tin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium">Status</label>
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
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
                        <select id="salary_type" name="salary_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Select</option>
                            <option value="Fixed">Fixed</option>
                            <option value="Hourly">Hourly</option>
                            <option value="Commission">Commission</option>
                        </select>
                    </div>
                    <div>
                        <label for="basic_salary" class="block text-sm font-medium">Basic Salary</label>
                        <input type="number" id="basic_salary" name="basic_salary" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div class="grid grid-cols-1">
                    <div>
                        <label for="overtime_bonus" class="block text-sm font-medium">Overtime & Bonus Eligibility</label>
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
                        <input type="text" id="emergency_name" name="emergency_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="emergency_relationship" class="block text-sm font-medium">Relationship</label>
                        <input type="text" id="emergency_relationship" name="emergency_relationship" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>
                <div>
                    <label for="emergency_contact" class="block text-sm font-medium">Contact Number</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                </div>
            </fieldset>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                Add Employee
            </button>
        </form>
    </div>
</main>
</body>
</html>