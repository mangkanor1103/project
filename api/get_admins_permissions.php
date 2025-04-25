<?php
session_start();
include '../config.php';

// Check if user is logged in as superadmin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    echo json_encode(['error' => 'Unauthorized access']);
    http_response_code(403);
    exit();
}

// Get all admins (excluding superadmin)
$admins_sql = "SELECT id, username, created_at FROM admins WHERE role = 'admin'";
$admins_result = $conn->query($admins_sql);

$admins = [];
if ($admins_result->num_rows > 0) {
    while ($admin = $admins_result->fetch_assoc()) {
        // Get permissions for this admin
        $permissions_sql = "SELECT permission FROM admin_permissions WHERE admin_id = ?";
        $stmt = $conn->prepare($permissions_sql);
        $stmt->bind_param('i', $admin['id']);
        $stmt->execute();
        $permissions_result = $stmt->get_result();

        $permissions = [];
        while ($permission = $permissions_result->fetch_assoc()) {
            $permissions[] = $permission['permission'];
        }

        $admin['permissions'] = $permissions;
        $admins[] = $admin;
    }
}

echo json_encode($admins);
?>