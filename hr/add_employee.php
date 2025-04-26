<?php
include '../config.php'; // Database configuration

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
    // Add insurance_id (new line)
    $insurance_id = isset($_POST['insurance_id']) && !empty($_POST['insurance_id']) ? $_POST['insurance_id'] : null;

    // Set a fixed temporary password
    $password = "12345678"; // Temporary password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password for storage

    // Image upload (optional)
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = basename($_FILES['image']['name']);
        $target = "../uploads/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    // Insert to database - add insurance_id to the column list and values placeholders
    $sql = "INSERT INTO employees (
                full_name, dob, gender, contact_number, email, home_address, image,
                job_position, department, employee_type, date_hired, work_schedule,
                sss_number, philhealth_number, pagibig_number, tin, status,
                salary_type, basic_salary, overtime_bonus,
                emergency_name, emergency_relationship, emergency_contact,
                password
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssssdissss", // Now 24 's' characters
        $full_name,          
        $dob,                
        $gender,             
        $contact_number,     
        $email,              
        $home_address,       
        $image_name,         
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
        // Get the new employee's ID
        $employee_id = $conn->insert_id;
        
        // Add insurance in a separate query if selected
        if ($insurance_id !== null) {
            $update_sql = "UPDATE employees SET insurance_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $insurance_id, $employee_id);
            $update_stmt->execute();
        }
        
        // Success message with SweetAlert2
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Employee Created</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Employee Created!',
                    html: '<p>The temporary password is <b>12345678</b>.</p>',
                    showConfirmButton: false,
                    timer: 2000,
                    customClass: {
                        popup: 'swal2-popup swal2-rounded swal2-shadow'
                    }
                }).then(() => {
                    window.location.href = 'hr_dashboard.php';
                });
            </script>
        </body>
        </html>";
        exit();
    } else {
        echo "Database Error: " . $stmt->error;
    }
} else {
    echo "Prepare failed: " . $conn->error;
}
?>