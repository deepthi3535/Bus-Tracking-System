<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';

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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['bus_id']) || !isset($input['lat']) || !isset($input['lng']) || !isset($input['auth_token'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$bus_id = (int)$input['bus_id'];
$latitude = (float)$input['lat'];
$longitude = (float)$input['lng'];
$auth_token = $input['auth_token'];
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

// Verify authentication token (simplified - in production use proper authentication)
try {
    $query = "SELECT id FROM buses WHERE id = :bus_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $bus_id);
    $stmt->execute();
    
    if ($stmt->rowCount() !== 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid bus ID']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Authentication error']);
    exit;
}

try {
    // Update bus location
    $query = "INSERT INTO bus_locations (bus_id, latitude, longitude, speed, bearing, accuracy) 
              VALUES (:bus_id, :lat, :lng, :speed, :bearing, :accuracy)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $bus_id);
    $stmt->bindParam(':lat', $latitude);
    $stmt->bindParam(':lng', $longitude);
    $stmt->bindParam(':speed', $speed);
    $stmt->bindParam(':bearing', $bearing);
    $stmt->bindParam(':accuracy', $accuracy);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Location updated successfully',
            'bus_id' => $bus_id,
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