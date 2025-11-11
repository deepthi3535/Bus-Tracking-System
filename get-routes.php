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

try {
    $query = "SELECT * FROM routes ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse stops from JSON
    foreach ($routes as &$route) {
        $route['stops'] = json_decode($route['stops'] ?? '[]', true);
        if (!is_array($route['stops'])) {
            $route['stops'] = [];
        }
    }
    
    echo json_encode([
        'success' => true, 
        'routes' => $routes,
        'count' => count($routes),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>