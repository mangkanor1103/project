<?php include 'components/header.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Email sending logic (use mail or an external library like PHPMailer)
    $to = "admin@example.com"; // Replace with your email
    $subject = "Contact Form Submission from $name";
    $body = "Name: $name\nEmail: $email\nMessage:\n$message";
    $headers = "From: $email";

    if (mail($to, $subject, $body, $headers)) {
        echo "<p class='text-green-600 text-center'>Message sent successfully!</p>";
    } else {
        echo "<p class='text-red-600 text-center'>Failed to send message. Please try again later.</p>";
    }
}
?>

<main class="container mx-auto px-4 mt-6">
    <h1 class="text-4xl font-bold text-blue-600 text-center mb-4">Contact Us</h1>
    <form method="POST" class="max-w-lg mx-auto bg-white p-6 shadow rounded">
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-bold">Name</label>
            <input type="text" id="name" name="name" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold">Email</label>
            <input type="email" id="email" name="email" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label for="message" class="block text-gray-700 font-bold">Message</label>
            <textarea id="message" name="message" class="w-full p-2 border rounded" rows="4" required></textarea>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            Send Message
        </button>
    </form>
</main>

<?php include 'components/footer.php'; ?>