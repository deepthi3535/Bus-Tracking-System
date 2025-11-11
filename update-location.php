<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if driver is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['driver_id'])) {
    echo json_encode(['success' => false, 'message' => 'Driver not logged in']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['lat']) || !isset($input['lng'])) {
    echo json_encode(['success' => false, 'message' => 'Missing latitude or longitude']);
    exit;
}

$latitude = (float)$input['lat'];
$longitude = (float)$input['lng'];
$speed = isset($input['speed']) ? (float)$input['speed'] : null;
$bearing = isset($input['bearing']) ? (float)$input['bearing'] : null;
$accuracy = isset($input['accuracy']) ? (float)$input['accuracy'] : null;

// Initialize database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Update bus location
    $query = "INSERT INTO bus_locations (bus_id, latitude, longitude, speed, bearing, accuracy) 
              VALUES (:bus_id, :lat, :lng, :speed, :bearing, :accuracy)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $_SESSION['bus_id']);
    $stmt->bindParam(':lat', $latitude);
    $stmt->bindParam(':lng', $longitude);
    $stmt->bindParam(':speed', $speed);
    $stmt->bindParam(':bearing', $bearing);
    $stmt->bindParam(':accuracy', $accuracy);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Location updated successfully',
            'bus_id' => $_SESSION['bus_id'],
            'location' => [$latitude, $longitude],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update location']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>