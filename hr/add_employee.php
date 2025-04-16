<?php
include '../config.php'; // or whatever connects you to your DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $home_address = $_POST['home_address'];
    $job_position = $_POST['job_position'];
    $department = $_POST['department'];
    $employee_type = $_POST['employee_type'];
    $date_hired = $_POST['date_hired'];
    $work_schedule = $_POST['work_schedule'];
    $sss_number = $_POST['sss_number'];
    $philhealth_number = $_POST['philhealth_number'];
    $pagibig_number = $_POST['pagibig_number'];
    $tin = $_POST['tin'];
    $status = $_POST['status'];
    $salary_type = $_POST['salary_type'];
    $basic_salary = $_POST['basic_salary'];
    $overtime_bonus = isset($_POST['overtime_bonus']) ? 1 : 0;
    $emergency_name = $_POST['emergency_name'];
    $emergency_relationship = $_POST['emergency_relationship'];
    $emergency_contact = $_POST['emergency_contact'];

    // Image upload (optional)
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = basename($_FILES['image']['name']);
        $target = "../uploads/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    // Insert to database
    $sql = "INSERT INTO employees (
                full_name, dob, gender, contact_number, email, home_address, image,
                job_position, department, employee_type, date_hired, work_schedule,
                sss_number, philhealth_number, pagibig_number, tin, status,
                salary_type, basic_salary, overtime_bonus,
                emergency_name, emergency_relationship, emergency_contact
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssssssssssssssssdisss",
            $full_name, $dob, $gender, $contact_number, $email, $home_address, $image_name,
            $job_position, $department, $employee_type, $date_hired, $work_schedule,
            $sss_number, $philhealth_number, $pagibig_number, $tin, $status,
            $salary_type, $basic_salary, $overtime_bonus,
            $emergency_name, $emergency_relationship, $emergency_contact
        );

        if ($stmt->execute()) {
            // Redirect on success
            header("Location: hr_dashboard.php");
            exit(); // Important: stop script execution after redirect
        } else {
            $error_message = "Database Error: " . $stmt->error;
        }
    } else {
        $error_message = "Prepare failed: " . $conn->error;
    }
}
?>
