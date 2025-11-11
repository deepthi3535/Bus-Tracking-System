<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Test database connection
$database = new Database();
$db = $database->getConnection();

$db_status = $db ? 'Connected' : 'Failed';

// Test data reception
$request_method = $_SERVER['REQUEST_METHOD'];
$post_data = [];

if ($request_method == 'POST') {
    $input = file_get_contents('php://input');
    $post_data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        parse_str($input, $post_data);
    }
}

// Response
echo json_encode([
    'success' => true,
    'message' => 'Connection test successful',
    'database' => $db_status,
    'request_method' => $request_method,
    'post_data' => $post_data,
    'get_data' => $_GET,
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]
]);
?>