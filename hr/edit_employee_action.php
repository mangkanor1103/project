<?php
include '../config.php'; // Database configuration

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || intval($_GET['id']) <= 0) {
    $provided_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : "No ID provided";
    echo "Invalid employee ID. Provided ID: " . $provided_id;
    exit();
}
$employee_id = intval($_GET['id']);

// Fetch the employee to validate the ID
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Invalid employee ID. No record found for ID: " . htmlspecialchars($employee_id);
    exit();
}

// Sanitize and update inputs
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $home_address = $_POST['home_address'];
    $job_position = $_POST['job_position'];
    $department = $_POST['department'];
    $date_hired = $_POST['date_hired'];
    $sss_number = $_POST['sss_number'];
    $philhealth_number = $_POST['philhealth_number'];
    $pagibig_number = $_POST['pagibig_number'];
    $tin = $_POST['tin'];
    $basic_salary = $_POST['basic_salary'];

    $update_sql = "UPDATE employees SET
        full_name = ?, dob = ?, gender = ?, contact_number = ?, email = ?, 
        home_address = ?, job_position = ?, department = ?, 
        date_hired = ?, sss_number = ?, philhealth_number = ?, 
        pagibig_number = ?, tin = ?, basic_salary = ?
        WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);

    if ($update_stmt) {
        $update_stmt->bind_param(
            "ssssssssssssssi",
            $full_name,
            $dob,
            $gender,
            $contact_number,
            $email,
            $home_address,
            $job_position,
            $department,
            $date_hired,
            $sss_number,
            $philhealth_number,
            $pagibig_number,
            $tin,
            $basic_salary,
            $employee_id
        );

        if ($update_stmt->execute()) {
            // SweetAlert2 message for success
            echo "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Employee Updated</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Employee updated successfully!',
                        showConfirmButton: false,
                        timer: 1000
                    }).then(() => {
                        window.location.href = 'hr_dashboard.php';
                    });
                </script>
            </body>
            </html>";
            exit();
        } else {
            echo "Error updating employee: " . $update_stmt->error;
        }
    } else {
        echo "Database error: " . $conn->error;
    }
}
?>