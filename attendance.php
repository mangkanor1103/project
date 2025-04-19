<?php
session_start();
include 'config.php'; // Include database configuration

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch employee details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Handle attendance submission
$success_message = $error_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = date('Y-m-d'); // Current date
    $time_now = date('H:i:s'); // Current time

    if (isset($_POST['time_in'])) {
        // Log Time In
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance) {
            $error_message = "You have already logged your time-in for today.";
        } else {
            $insert_sql = "INSERT INTO attendance (employee_id, date, time_in) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iss", $user_id, $date, $time_now);
            $insert_stmt->execute();
            $success_message = "Time-in logged successfully at $time_now!";
        }
    } elseif (isset($_POST['time_out'])) {
        // Log Time Out
        $check_sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $date);
        $check_stmt->execute();
        $attendance = $check_stmt->get_result()->fetch_assoc();

        if ($attendance && $attendance['time_out'] === null) {
            $update_sql = "UPDATE attendance SET time_out = ? WHERE employee_id = ? AND date = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sis", $time_now, $user_id, $date);
            $update_stmt->execute();
            $success_message = "Time-out logged successfully at $time_now!";
        } else {
            $error_message = "You need to log your time-in first, or you have already logged your time-out.";
        }
    }
}
?>

<?php include 'components/header.php'; ?>

<main class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-8">Real-Time Attendance for <?php echo htmlspecialchars($employee['full_name']); ?></h1>
        
        <!-- Real-Time Clock -->
        <div class="bg-white shadow-md rounded-lg p-6 text-center mb-6">
            <h2 class="text-xl font-bold text-gray-700">Current Date and Time</h2>
            <p id="real-time" class="text-2xl font-mono text-blue-600 mt-2"></p>
        </div>

        <!-- Home Button -->
        <div class="text-center mb-6">
            <a href="dashboard.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition transform hover:-translate-y-1 hover:scale-105">
                Home
            </a>
        </div>

        <!-- Attendance Buttons -->
        <div class="bg-white shadow-lg rounded-lg p-6 max-w-4xl mx-auto text-center space-y-4">
            <form action="attendance.php" method="POST">
                <button type="submit" name="time_in" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition transform hover:-translate-y-1 hover:scale-105" <?php echo isset($attendance) && $attendance['time_in'] ? 'disabled' : ''; ?>>
                    Log Time In
                </button>
            </form>
            <form action="attendance.php" method="POST">
                <button type="submit" name="time_out" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition transform hover:-translate-y-1 hover:scale-105" <?php echo isset($attendance) && $attendance['time_out'] ? 'disabled' : ''; ?>>
                    Log Time Out
                </button>
            </form>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Real-Time Clock
function updateClock() {
    const now = new Date();
    const formattedDate = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const formattedTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    document.getElementById('real-time').textContent = `${formattedDate} ${formattedTime}`;
}

// Update the clock every second
setInterval(updateClock, 1000);
updateClock();

// SweetAlert2 Messages
<?php if (!empty($success_message)): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '<?php echo $success_message; ?>',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    });
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $error_message; ?>',
        confirmButtonText: 'OK'
    });
<?php endif; ?>
</script>