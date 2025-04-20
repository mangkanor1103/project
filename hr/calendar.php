<!-- filepath: c:\xampp\htdocs\project\hr\calendar.php -->
<?php
session_start();
include '../config.php';
include '../components/header.php'; // Include your header

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

$success_message = $error_message = "";

// Handle form submission for adding holidays/events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date']);
    $event_name = trim($_POST['event_name']);
    $event_type = trim($_POST['event_type']); // e.g., Holiday or Event

    if (!empty($date) && !empty($event_name) && !empty($event_type)) {
        $sql = "INSERT INTO calendar_events (date, event_name, event_type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $date, $event_name, $event_type);
        if ($stmt->execute()) {
            $success_message = "Event added successfully!";
        } else {
            $error_message = "Failed to add event.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Fetch existing events
$events = [];
$sql = "SELECT * FROM calendar_events ORDER BY date ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="bg-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Manage Calendar</h1>
            <a href="hr_dashboard.php"
                class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md shadow-md hover:bg-gray-300 transition">
                Back to Dashboard
            </a>
        </div>
        <div class="bg-white shadow-lg rounded-lg p-6">
            <?php if (!empty($success_message)): ?>
                <p class="text-green-600 mb-4"><?php echo $success_message; ?></p>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <p class="text-red-600 mb-4"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" id="date" name="date" required
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="event_name" class="block text-sm font-medium text-gray-700">Event Name</label>
                    <input type="text" id="event_name" name="event_name" required
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type</label>
                    <select id="event_type" name="event_type" required
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Holiday">Holiday</option>
                        <option value="Event">Event</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-teal-600 text-white px-6 py-2 rounded-md shadow-md hover:bg-teal-700 transition">
                        Add Event
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Upcoming Events</h2>
            <div class="bg-white shadow-lg rounded-lg p-6">
                <?php if (!empty($events)): ?>
                    <ul class="space-y-4">
                        <?php foreach ($events as $event): ?>
                            <li class="flex justify-between items-center">
                                <div>
                                    <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($event['event_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($event['event_type']); ?> - <?php echo date('F d, Y', strtotime($event['date'])); ?></p>
                                </div>
                                <a href="delete_event.php?id=<?php echo $event['id']; ?>"
                                    class="text-red-600 hover:underline">Delete</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-600">No events found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>