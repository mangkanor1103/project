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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {
        $sql = "INSERT INTO announcements (title, content) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $title, $content);
        if ($stmt->execute()) {
            $success_message = "Announcement created successfully!";
        } else {
            $error_message = "Failed to create announcement.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Fetch existing announcements for display
$announcements = [];
$sql = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>

<main class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Page header with animation -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Company Announcements</h1>
                <p class="text-gray-600">Create and manage important announcements for employees</p>
            </div>
            <a href="hr_dashboard.php"
                class="bg-gray-200 text-gray-700 px-4 py-2.5 rounded-lg shadow hover:bg-gray-300 transition flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                        clip-rule="evenodd" />
                </svg>
                <span>Back to Dashboard</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Create announcement form -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-600 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        Create New Announcement
                    </h2>

                    <?php if (!empty($success_message)): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                            <div class="flex">
                                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <p><?php echo $success_message; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                            <div class="flex">
                                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <p><?php echo $error_message; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Announcement
                                Title</label>
                            <input type="text" id="title" name="title" required
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Announcement
                                Content</label>
                            <textarea id="content" name="content" required rows="6"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition"></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full bg-teal-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-teal-700 transition flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Publish Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements List -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow-lg rounded-xl border border-gray-100">
                    <div class="p-5 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-600 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            Recent Announcements
                        </h2>
                    </div>

                    <div class="p-2">
                        <?php if (!empty($announcements)): ?>
                            <div class="divide-y divide-gray-100">
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="p-4 hover:bg-gray-50 transition-colors rounded-lg">
                                        <div class="flex justify-between mb-2">
                                            <h3 class="font-semibold text-lg text-gray-800">
                                                <?php echo htmlspecialchars($announcement['title']); ?></h3>
                                            <span
                                                class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                                        </div>
                                        <p class="text-gray-600 mb-3">
                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                        <div class="flex justify-end">
                                            <a href="#" class="text-sm text-teal-600 hover:text-teal-800 mr-4">Edit</a>
                                            <a href="#" class="text-sm text-red-600 hover:text-red-800"
                                                onclick="return confirm('Are you sure you want to delete this announcement?')">Delete</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                                <p class="text-gray-500 text-lg">No announcements yet</p>
                                <p class="text-gray-400 mt-1">Create your first announcement to get started!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>