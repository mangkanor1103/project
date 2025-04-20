<?php
session_start();

// Check if the confirmation was already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_destroy(); // Destroy all session data
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <title>Logged Out</title>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Logged Out',
                text: 'You have been logged out successfully!',
                timer: 1000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>
    </body>
    </html>
    ";
    exit();
}
?>

<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen flex items-center justify-center">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you really want to log out?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, log me out',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send a POST request to log out
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'logout.php';
                document.body.appendChild(form);
                form.submit();
            } else {
                // Redirect back to dashboard
                window.location.href = 'dashboard.php';
            }
        });
    </script>
</main>

<?php include 'components/footer.php'; ?>