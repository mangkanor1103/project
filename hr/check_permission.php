<?php
// Function to check if the current admin has permission to access a page
function checkPermission($required_permission)
{
    // Skip check for superadmin
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') {
        return true;
    }

    // Check if admin_id exists in session
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }

    global $conn;

    // Check if the admin has the required permission
    $sql = "SELECT 1 FROM admin_permissions WHERE admin_id = ? AND permission = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $_SESSION['admin_id'], $required_permission);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

// Use this at the beginning of each HR admin page that requires permission
function requirePermission($permission)
{
    if (!checkPermission($permission)) {
        // Prepare the access denied page
        include '../components/header.php';
        echo '
        <div class="min-h-screen bg-gray-100 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
                <div class="flex justify-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-center text-red-600 mb-4">Access Denied</h1>
                <p class="text-gray-600 text-center mb-6">You do not have permission to access this page. Please contact your administrator.</p>
                <div class="flex justify-center">
                    <a href="hr_dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
        ';
        include '../components/footer.php';
        exit();
    }
}
?>