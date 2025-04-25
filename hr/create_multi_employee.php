<?php
session_start(); // Start session explicitly 

require_once __DIR__ . '/../vendor/autoload.php';

include '../components/header.php';
include_once '../config.php';

// Import PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Check if PhpSpreadsheet is available
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    // If PhpSpreadsheet is not available, you might want to install it using:
    // composer require phpoffice/phpspreadsheet
    $error_message = "PhpSpreadsheet library is missing. Please install it first.";
}

$success_message = $error_message = '';
$import_results = ['success' => 0, 'failed' => 0, 'errors' => []];

// Process Excel file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['employee_excel'])) {
    // Check if file is uploaded
    if ($_FILES['employee_excel']['error'] === UPLOAD_ERR_OK) {
        // Get file extension
        $file_ext = strtolower(pathinfo($_FILES['employee_excel']['name'], PATHINFO_EXTENSION));

        // Check if it's an Excel file
        if ($file_ext === 'xlsx' || $file_ext === 'xls') {
            try {
                // Load the Excel file
                $spreadsheet = IOFactory::load($_FILES['employee_excel']['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();

                // Get all rows as an array
                $rows = $worksheet->toArray();

                // Skip header row
                $header = array_shift($rows);

                // Counter for row tracking
                $row = 2; // Start from row 2 (after header)

                // Process each row
                foreach ($rows as $data) {
                    // Skip empty rows
                    if (empty($data[0]) || count($data) < 22) {
                        $row++;
                        continue;
                    }

                    // Extract data from each Excel column
                    $full_name = trim($data[0]);
                    $dob = trim($data[1]);
                    $gender = trim($data[2]);
                    $contact_number = trim($data[3]);
                    $email = trim($data[4]);
                    $home_address = trim($data[5]);
                    $job_position = trim($data[6]);
                    $department = trim($data[7]);
                    $employee_type = trim($data[8]);
                    $date_hired = trim($data[9]);
                    $work_schedule = trim($data[10]);
                    $sss_number = trim($data[11]);
                    $philhealth_number = trim($data[12]);
                    $pagibig_number = trim($data[13]);
                    $tin = trim($data[14]);
                    $status = trim($data[15]);
                    $salary_type = trim($data[16]);
                    $basic_salary = trim($data[17]);
                    $overtime_bonus = (trim($data[18]) == '1' || strtolower(trim($data[18])) == 'yes') ? 1 : 0;
                    $emergency_name = trim($data[19]);
                    $emergency_relationship = trim($data[20]);
                    $emergency_contact = trim($data[21]);

                    // Convert date formats if needed
                    if ($dob) {
                        // If the date is in DD/MM/YYYY format, convert it to YYYY-MM-DD
                        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dob)) {
                            $date_parts = explode('/', $dob);
                            $dob = $date_parts[2] . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT) . '-' .
                                str_pad($date_parts[0], 2, '0', STR_PAD_LEFT);
                        }
                    }

                    if ($date_hired) {
                        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date_hired)) {
                            $date_parts = explode('/', $date_hired);
                            $date_hired = $date_parts[2] . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT) . '-' .
                                str_pad($date_parts[0], 2, '0', STR_PAD_LEFT);
                        }
                    }

                    // Skip empty rows
                    if (empty($full_name) || empty($email)) {
                        $row++;
                        continue;
                    }

                    // Validate required fields
                    if (
                        empty($full_name) || empty($dob) || empty($gender) || empty($contact_number) ||
                        empty($email) || empty($home_address) || empty($job_position) ||
                        empty($department) || empty($employee_type) || empty($date_hired) ||
                        empty($work_schedule) || empty($sss_number) || empty($philhealth_number) ||
                        empty($pagibig_number) || empty($tin) || empty($status) ||
                        empty($salary_type) || empty($basic_salary) ||
                        empty($emergency_name) || empty($emergency_relationship) || empty($emergency_contact)
                    ) {
                        $import_results['failed']++;
                        $import_results['errors'][] = "Row $row: Missing required fields";
                        $row++;
                        continue;
                    }

                    // Validate email format
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $import_results['failed']++;
                        $import_results['errors'][] = "Row $row: Invalid email format - $email";
                        $row++;
                        continue;
                    }

                    // Check if email already exists
                    $check_email_sql = "SELECT id FROM employees WHERE email = ?";
                    $check_stmt = $conn->prepare($check_email_sql);
                    $check_stmt->bind_param("s", $email);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        $import_results['failed']++;
                        $import_results['errors'][] = "Row $row: Email already exists - $email";
                        $row++;
                        continue;
                    }

                    // Set a default password and hash it
                    $password = "12345678"; // Temporary password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert employee
                    $sql = "INSERT INTO employees (
                        full_name, dob, gender, contact_number, email, home_address,
                        job_position, department, employee_type, date_hired, work_schedule,
                        sss_number, philhealth_number, pagibig_number, tin, status,
                        salary_type, basic_salary, overtime_bonus,
                        emergency_name, emergency_relationship, emergency_contact, password
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "ssssssssssssssssssissss",
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
                        $hashed_password
                    );

                    if ($stmt->execute()) {
                        $import_results['success']++;
                    } else {
                        $import_results['failed']++;
                        $import_results['errors'][] = "Row $row: Database error - " . $stmt->error;
                    }

                    $row++;
                }

                if ($import_results['success'] > 0) {
                    $success_message = $import_results['success'] . " employees imported successfully!";
                }

                if ($import_results['failed'] > 0) {
                    $error_message = $import_results['failed'] . " employees failed to import.";
                }

            } catch (Exception $e) {
                $error_message = "Error processing Excel file: " . $e->getMessage();
            }
        } else {
            $error_message = "Please upload a valid Excel file (.xlsx, .xls)";
        }
    } else {
        $error_message = "Error uploading file: " . $_FILES['employee_excel']['error'];
    }
}

// Generate Excel template for download
if (isset($_GET['download_template'])) {
    // Ensure no output has been sent before this point
    if (ob_get_level())
        ob_end_clean();

    // Create Excel spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column headers
    $headers = [
        'Full Name',
        'Date of Birth (YYYY-MM-DD)',
        'Gender (Male/Female/Other)',
        'Contact Number',
        'Email',
        'Home Address',
        'Job Position',
        'Department',
        'Employee Type (Regular/Probationary/Contractual)',
        'Date Hired (YYYY-MM-DD)',
        'Work Schedule',
        'SSS Number',
        'PhilHealth Number',
        'Pag-IBIG Number',
        'TIN',
        'Marital Status (Single/Married/Widowed/Divorced)',
        'Salary Type (Fixed/Hourly/Commission)',
        'Basic Salary',
        'Overtime Bonus (1=Yes, 0=No)',
        'Emergency Contact Name',
        'Emergency Contact Relationship',
        'Emergency Contact Number'
    ];

    // Add headers to first row
    for ($i = 0; $i < count($headers); $i++) {
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
        $sheet->setCellValue($columnLetter . '1', $headers[$i]);
    }

    // Apply styling to header row
    $sheet->getStyle('A1:V1')->getFont()->setBold(true);

    // Set column widths
    foreach (range('A', 'V') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Create Excel file
    $writer = new Xlsx($spreadsheet);

    // Set headers for Excel download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="employee_import_template.xlsx"');
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Pragma: public');

    // Save Excel file to output
    $writer->save('php://output');
    exit;
}
?>

<main class="container mx-auto px-4 py-8 max-w-5xl">
    <!-- Back to Dashboard Button -->
    <div class="mb-6">
        <a href="hr_dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to HR Dashboard
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg px-8 py-6 border border-gray-100">
        <div class="flex justify-between items-center border-b pb-4 mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Bulk Import Employees</h1>
            <a href="create_employee.php"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md shadow-sm hover:shadow transition duration-300 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Add Single Employee
            </a>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-medium">Error</p>
                <p><?php echo $error_message; ?></p>
                <?php if (!empty($import_results['errors'])): ?>
                    <ul class="mt-2 list-disc list-inside">
                        <?php foreach ($import_results['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-medium">Success</p>
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Download Template -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-100">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">Step 1: Download Template</h2>
                <p class="text-gray-700 mb-4">
                    Download the Excel template file and fill it with employee information. Make sure to follow the
                    format in the template headers.
                </p>
                <div class="flex items-center space-x-4">
                    <a href="?download_template=1"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-md shadow-md hover:shadow-lg transition flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                        Download Excel Template
                    </a>
                </div>
            </div>

            <!-- Upload File -->
            <div class="bg-green-50 p-6 rounded-lg border border-green-100">
                <h2 class="text-xl font-semibold text-green-800 mb-4">Step 2: Upload Completed File</h2>
                <p class="text-gray-700 mb-4">
                    Upload your completed Excel file. The system will process the data and add the employees to the
                    database.
                </p>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="employee_excel" class="block text-sm font-medium text-gray-700 mb-2">Select Excel
                            File</label>
                        <input type="file" id="employee_excel" name="employee_excel" accept=".xlsx,.xls" class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4 file:rounded-md
                            file:border-0 file:text-sm file:font-medium
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100" required>
                    </div>
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-md shadow-md hover:shadow-lg transition flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        Upload and Import
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">How to Use Excel Import</h2>
            <ol class="list-decimal list-inside space-y-3 text-gray-700">
                <li>Download the Excel template using the button above</li>
                <li>Open the template in Microsoft Excel or any compatible spreadsheet program</li>
                <li>Fill in employee data according to the column headers</li>
                <li>Save the file as Excel format (.xlsx or .xls)</li>
                <li>Upload the saved Excel file using the form above</li>
                <li>Review any errors that may occur during import</li>
            </ol>
        </div>

        <div class="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Important Notes</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li>All imported employees will be given a temporary password: <strong>12345678</strong></li>
                <li>Required fields: Full Name, Email, Date of Birth, Gender, Contact Number, and all other fields
                    marked in the template</li>
                <li>Make sure email addresses are unique and in the correct format</li>
                <li>Dates should be in YYYY-MM-DD format</li>
                <li>Gender must be one of: Male, Female, Other</li>
                <li>Employee Type must be one of: Regular, Probationary, Contractual</li>
                <li>Marital Status must be one of: Single, Married, Widowed, Divorced</li>
                <li>Salary Type must be one of: Fixed, Hourly, Commission</li>
                <li>For Overtime Bonus, use 1 for Yes and 0 for No</li>
                <li>Do not change the order of columns in the Excel template</li>
            </ul>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>