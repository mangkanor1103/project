<?php
// Include the database connection file
include('config.php');

// Initialize variables for form data
$name = "";
$email = "";
$message = "";
$successMessage = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['message'])) {
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $message = htmlspecialchars($_POST['message']);
        
        // Simple validation for email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $successMessage = "Invalid email format!";
        } else {
            // Prepare and bind
            $stmt = $conn->prepare("INSERT INTO feedback (name, email, message) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $message);

            // Execute the query and check if insertion was successful
            if ($stmt->execute()) {
                $successMessage = "Thank you for your feedback!";
            } else {
                $successMessage = "There was an error saving your feedback. Please try again.";
            }

            // Close the statement
            $stmt->close();
        }
    } else {
        $successMessage = "All fields are required!";
    }
}

// Close the database connection
$conn->close();
?>

<?php include('components/header.php'); // Including the header ?>

<!-- Page Content -->
<div class="container mx-auto px-4 mt-12">
    <div class="message-container text-center">
        <?php if ($successMessage): ?>
            <?php if ($successMessage === "Thank you for your feedback!"): ?>
                <div class="bg-green-500 text-white p-4 rounded-md shadow-md">
                    <?php echo $successMessage; ?>
                </div>
            <?php else: ?>
                <div class="bg-red-500 text-white p-4 rounded-md shadow-md">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            <p class="mt-4 text-gray-700">You will be redirected to the homepage in 2 second. If not, <a href="index.php" class="text-blue-500">click here</a>.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Redirect to index.php after 1 second
    setTimeout(function() {
        window.location.href = 'index.php';
    }, 2000);
</script>

</body>
</html>
