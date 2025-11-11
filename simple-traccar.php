<?php
// Simple endpoint for initial Traccar testing
header('Content-Type: text/plain');

// Create logs directory if it doesn't exist
if (!file_exists('../logs')) {
    mkdir('../logs', 0777, true);
}

// Log the connection
file_put_contents('../logs/received.txt', date('Y-m-d H:i:s') . " - Connection received!\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    file_put_contents('../logs/received.txt', "POST data: " . $input . "\n", FILE_APPEND);
}

echo "Simple Traccar endpoint is working!\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
?>