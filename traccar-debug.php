<?php
// Simple debug endpoint for Traccar
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log everything
file_put_contents('../logs/traccar_debug.log', date('Y-m-d H:i:s') . " - Debug endpoint hit\n", FILE_APPEND);
file_put_contents('../logs/traccar_debug.log', "Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents('../logs/traccar_debug.log', "Headers: " . print_r(getallheaders(), true) . "\n", FILE_APPEND);

// Get the raw POST data
$input = file_get_contents('php://input');
file_put_contents('../logs/traccar_debug.log', "Raw input: " . $input . "\n", FILE_APPEND);

// Try to parse the data
$data = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Try JSON first
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try form data
        parse_str($input, $data);
    }
}

file_put_contents('../logs/traccar_debug.log', "Parsed data: " . print_r($data, true) . "\n", FILE_APPEND);
file_put_contents('../logs/traccar_debug.log', "---------------------------------\n", FILE_APPEND);

// Always return success for testing
echo json_encode([
    'success' => true,
    'message' => 'Debug endpoint working',
    'received_data' => $data,
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER
]);
?>