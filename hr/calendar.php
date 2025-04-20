<?php
session_start();
include '../config.php';
include '../components/header.php';

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

// Get current month and year (defaults to current date if not specified)
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Ensure month stays in valid range (1-12)
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Calculate previous and next month links
$prev_month = $month - 1;
$prev_year = $year;
$next_month = $month + 1;
$next_year = $year;

if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get first day of the month
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$first_day_of_week = date('w', $first_day_timestamp); // 0 (Sunday) to 6 (Saturday)
$days_in_month = date('t', $first_day_timestamp);
$month_name = date('F', $first_day_timestamp);

// Fetch all events for the current month
$events_by_date = [];
$start_date = "$year-$month-01";
$end_date = "$year-$month-$days_in_month";

$sql = "SELECT * FROM calendar_events WHERE date BETWEEN ? AND ? ORDER BY date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

while ($event = $result->fetch_assoc()) {
    $event_date = date('j', strtotime($event['date'])); // Day of month (1-31)
    if (!isset($events_by_date[$event_date])) {
        $events_by_date[$event_date] = [];
    }
    $events_by_date[$event_date][] = $event;
}

// Fetch all events for listing
$all_events = [];
$sql = "SELECT * FROM calendar_events ORDER BY date ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $all_events = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="bg-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">HR Calendar</h1>
            <a href="hr_dashboard.php"
                class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md shadow-md hover:bg-gray-300 transition">
                Back to Dashboard
            </a>
        </div>

        <!-- Calendar Navigation -->
        <div class="bg-white shadow-lg rounded-lg mb-4 sm:mb-8">
            <div class="flex justify-between items-center p-2 sm:p-4 border-b">
                <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>"
                    class="bg-teal-600 text-white px-2 sm:px-4 py-2 rounded-md hover:bg-teal-700 text-sm sm:text-base">
                    &laquo; Prev
                </a>
                <h2 class="text-lg sm:text-2xl font-bold"><?= $month_name ?> <?= $year ?></h2>
                <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>"
                    class="bg-teal-600 text-white px-2 sm:px-4 py-2 rounded-md hover:bg-teal-700 text-sm sm:text-base">
                    Next &raquo;
                </a>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="bg-white shadow-lg rounded-lg mb-8">
            <div class="p-4">
                <!-- Day names header -->
                <div class="grid grid-cols-7 gap-1 mb-2 text-center">
                    <div class="text-gray-600 font-semibold">
                        <span class="hidden sm:inline">Sun</span>
                        <span class="sm:hidden">S</span>
                    </div>
                    <div class="text-gray-600 font-semibold">
                        <span class="hidden sm:inline">Mon</span>
                        <span class="sm:hidden">M</span>
                    </div>
                    <div class="text-gray-600 font-semibold">
                        <span class="hidden sm:inline">Tue</span>
                        <span class="sm:hidden">T</span>
                    </div>
                    <div class="text-gray-600 font-semibold">
                        <span class="hidden sm:inline">Wed</span>
                        <span class="sm:hidden">W</span>
                    </div>
                    <div class="text-gray-600 font-semibold">
                        <span class="hidden sm:inline">Thu</span>
                        <span class="sm:hidden">T</span>
                    </div>
                    <div class="text-gray-600 font-semibold">
                        <span class="hidden sm:inline">Fri</span>
                        <span class="sm:hidden">F</span>
                    </div>
                    <div class="text-gray-600 font-semibold">
                        <span class="hidden sm:inline">Sat</span>
                        <span class="sm:hidden">S</span>
                    </div>
                </div>

                <!-- Calendar days (updated for better mobile view) -->
                <div class="grid grid-cols-7 gap-1">
                    <?php
                    // Empty cells before the first day
                    for ($i = 0; $i < $first_day_of_week; $i++) {
                        echo '<div class="h-20 sm:h-24 md:h-36 p-1 bg-gray-100 rounded"></div>';
                    }

                    // Calendar days
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $date_class = isset($events_by_date[$day]) ? 'bg-teal-50 border-teal-200' : 'bg-white';
                        $today_class = ($day == date('j') && $month == date('m') && $year == date('Y'))
                            ? 'ring-2 ring-teal-600' : '';

                        // Format the date for the data attribute (YYYY-MM-DD)
                        $formatted_date = sprintf('%04d-%02d-%02d', $year, $month, $day);

                        echo '<div class="h-20 sm:h-24 md:h-36 p-1 border rounded cursor-pointer hover:bg-teal-50 transition-colors '
                            . $date_class . ' ' . $today_class . '" '
                            . 'onclick="selectDate(\'' . $formatted_date . '\')" '
                            . 'data-date="' . $formatted_date . '">';
                        echo '<div class="font-bold text-xs sm:text-sm md:text-base">' . $day . '</div>';

                        // Show events for this day - improved for mobile
                        if (isset($events_by_date[$day])) {
                            echo '<div class="overflow-y-auto max-h-14 sm:max-h-16 md:max-h-28">';
                            foreach ($events_by_date[$day] as $event) {
                                $event_color = $event['event_type'] === 'Holiday' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
                                echo '<div class="text-xs md:text-sm p-0.5 sm:p-1 my-0.5 rounded truncate ' . $event_color . '">';
                                echo htmlspecialchars($event['event_name']);
                                echo '</div>';
                            }
                            echo '</div>';
                        }

                        echo '</div>';

                        // Start a new row after Saturday
                        if (($day + $first_day_of_week) % 7 === 0) {
                            echo '</div><div class="grid grid-cols-7 gap-1">';
                        }
                    }

                    // Empty cells after the last day
                    $remaining_cells = 7 - (($days_in_month + $first_day_of_week) % 7);
                    if ($remaining_cells < 7) {
                        for ($i = 0; $i < $remaining_cells; $i++) {
                            echo '<div class="h-20 sm:h-24 md:h-36 p-1 bg-gray-100 rounded"></div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-8">
            <!-- Add Event Form -->
            <div id="event-form" class="bg-white shadow-lg rounded-lg p-4 sm:p-6 transition-colors duration-300">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4">Add New Event</h2>

                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 sm:p-4 mb-4 text-sm sm:text-base"
                        role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 sm:p-4 mb-4 text-sm sm:text-base"
                        role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4 sm:space-y-6">
                    <!-- Form fields remain the same but with better touch targets -->
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" id="date" name="date" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-500 focus:ring-opacity-50 py-2 sm:text-sm">
                    </div>
                    <div>
                        <label for="event_name" class="block text-sm font-medium text-gray-700">Event Name</label>
                        <input type="text" id="event_name" name="event_name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-500 focus:ring-opacity-50 py-2 sm:text-sm">
                    </div>
                    <div>
                        <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type</label>
                        <select id="event_type" name="event_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-500 focus:ring-opacity-50 py-2 sm:text-sm">
                            <option value="Holiday">Holiday</option>
                            <option value="Event">Event</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                            class="w-full sm:w-auto bg-teal-600 text-white px-4 sm:px-6 py-2 rounded-md shadow-md hover:bg-teal-700 transition text-sm sm:text-base">
                            Add Event
                        </button>
                    </div>
                </form>
            </div>

            <!-- Upcoming Events List (mobile optimized) -->
            <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4">Upcoming Events</h2>

                <?php if (!empty($all_events)): ?>
                    <div class="overflow-y-auto max-h-80 sm:max-h-96">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($all_events as $event): ?>
                                <?php
                                $event_date = strtotime($event['date']);
                                $badge_color = $event['event_type'] === 'Holiday' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
                                $is_past = $event_date < strtotime('today');
                                ?>
                                <li class="py-2 sm:py-3 flex justify-between items-center <?= $is_past ? 'opacity-50' : '' ?>">
                                    <div class="pr-2">
                                        <p class="text-base sm:text-lg font-semibold text-gray-800 truncate">
                                            <?php echo htmlspecialchars($event['event_name']); ?>
                                        </p>
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span
                                                class="text-xs sm:text-sm text-gray-600"><?php echo date('M d, Y', $event_date); ?></span>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-xs <?= $badge_color ?>"><?php echo htmlspecialchars($event['event_type']); ?></span>
                                        </div>
                                    </div>
                                    <a href="delete_event.php?id=<?php echo $event['id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this event?')"
                                        class="text-red-600 hover:text-red-800 p-2"> <!-- Added padding for touch target -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No events found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function selectDate(date) {
            // Set the date in the form
            document.getElementById('date').value = date;

            // Scroll to the form
            document.getElementById('event-form').scrollIntoView({ behavior: 'smooth' });

            // Optional: Flash the form to draw attention
            const form = document.getElementById('event-form');
            form.classList.add('bg-teal-50');
            setTimeout(() => {
                form.classList.remove('bg-teal-50');
            }, 1000);

            // Focus on the event name field
            setTimeout(() => {
                document.getElementById('event_name').focus();
            }, 700);
        }
    </script>
</main>

<?php include '../components/footer.php'; ?>