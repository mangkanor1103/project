<footer class="bg-gradient-to-r from-indigo-900 to-blue-800 text-white py-6">
    <div class="container mx-auto px-4">
        <div class="flex justify-center">
            <p class="text-sm text-indigo-200">&copy; <?= date('Y') ?> Payroll Management System. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Elements
        const modal = document.getElementById('login-modal');
        const openModalBtn = document.getElementById('employee-login-btn');
        const closeModalBtn = document.getElementById('close-modal');
        const overlay = document.getElementById('modal-overlay');
        const errorContainer = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        const loginForm = document.getElementById('login-form');

        // Show modal function
        function showModal() {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden'); // Prevent scrolling
        }

        // Hide modal function
        function hideModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Event listeners
        openModalBtn.addEventListener('click', showModal);
        closeModalBtn.addEventListener('click', hideModal);
        overlay.addEventListener('click', hideModal);

        // Display error message if it exists from PHP
        <?php if (isset($error_message) && !empty($error_message)): ?>
            errorContainer.classList.remove('hidden');
            errorText.textContent = "<?php echo $error_message; ?>";
            showModal(); // Show modal again if there was an error
        <?php endif; ?>

        // Form submission
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            this.action = "index.php";
            this.submit();
        });
    });

    // Admin Modal Logic
const adminLoginBtn = document.getElementById('admin-login-btn');
const adminModal = document.getElementById('admin-login-modal');
const adminModalOverlay = document.getElementById('admin-modal-overlay');
const closeAdminModal = document.getElementById('close-admin-modal');

// Open Admin Modal
adminLoginBtn.addEventListener('click', () => {
    adminModal.classList.remove('hidden');
});

// Close Admin Modal
closeAdminModal.addEventListener('click', () => {
    adminModal.classList.add('hidden');
});

adminModalOverlay.addEventListener('click', () => {
    adminModal.classList.add('hidden');
});
</script>

</body>

</html>