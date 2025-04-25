<?php
session_start();
include '../config.php';

// Check if user is logged in as superadmin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    http_response_code(403);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['admin_id']) || !isset($data['permission']) || !isset($data['granted'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    http_response_code(400);
    exit();
}

$admin_id = $data['admin_id'];
$permission = $data['permission'];
$granted = $data['granted'];

// Verify admin exists and is not a superadmin
$admin_check_sql = "SELECT id FROM admins WHERE id = ? AND role = 'admin'";
$admin_check_stmt = $conn->prepare($admin_check_sql);
$admin_check_stmt->bind_param('i', $admin_id);
$admin_check_stmt->execute();
$admin_result = $admin_check_stmt->get_result();

if ($admin_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID or admin is not an HR admin']);
    http_response_code(400);
    exit();
}

// Update permission
if ($granted) {
    // Add permission if it doesn't exist
    $sql = "INSERT IGNORE INTO admin_permissions (admin_id, permission) VALUES (?, ?)";
} else {
    // Remove permission if it exists
    $sql = "DELETE FROM admin_permissions WHERE admin_id = ? AND permission = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $admin_id, $permission);
$result = $stmt->execute();

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>