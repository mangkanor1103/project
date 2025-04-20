<!-- filepath: c:\xampp\htdocs\project\admin\announcements.php -->
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
?>

<main class="bg-gray-100 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Create Announcement</h1>
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
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="title" name="title" required
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea id="content" name="content" required rows="5"
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-md shadow-md hover:bg-indigo-700 transition">
                        Create Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; // Include your footer ?>