<?php
session_start();
session_destroy(); // Destroy all session data
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Logged Out',
            text: 'You have been successfully logged out.',
            timer: 1000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            window.location.href = '../index.php'; // Redirect to login page
        });
    </script>
</body>
</html>