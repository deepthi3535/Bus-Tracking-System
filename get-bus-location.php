<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get parameters
$bus_id = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : null;
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

try {
    if ($bus_id) {
        // Get specific bus location
        $query = "SELECT bl.*, b.bus_number, b.driver_name, r.name as route_name 
                  FROM bus_locations bl 
                  JOIN buses b ON bl.bus_id = b.id 
                  LEFT JOIN routes r ON b.current_route_id = r.id 
                  WHERE bl.bus_id = :bus_id 
                  ORDER BY bl.timestamp DESC 
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':bus_id', $bus_id);
        $stmt->execute();
        
        $bus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bus) {
            echo json_encode([
                'success' => true, 
                'bus' => $bus,  // ← Changed from 'location' to 'bus'
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No location data found for this bus'
            ]);
        }
        
    } else {
        // Get all bus locations
        $query = "SELECT bl.*, b.bus_number, b.driver_name, r.name as route_name 
          FROM bus_locations bl 
          JOIN buses b ON bl.bus_id = b.id 
          LEFT JOIN routes r ON b.current_route_id = r.id 
          ORDER BY bl.timestamp DESC 
          LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by bus_id to get latest location for each bus
        $unique_buses = [];
        foreach ($buses as $bus) {
            if (!isset($unique_buses[$bus['bus_id']])) {
                $unique_buses[$bus['bus_id']] = $bus;
            }
        }
        
        echo json_encode([
            'success' => true, 
            'buses' => array_values($unique_buses),  // ← KEY CHANGE: 'locations' to 'buses'
            'count' => count($unique_buses),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>