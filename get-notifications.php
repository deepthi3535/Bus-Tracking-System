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

// Get parameters
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Initialize database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    if ($user_id) {
        // Get user's route if available
        $userQuery = "SELECT route_id FROM users WHERE id = :user_id";
        $userStmt = $db->prepare($userQuery);
        $userStmt->bindParam(':user_id', $user_id);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        $route_id = $user ? $user['route_id'] : null;
        
        // Get notifications for this user
        $query = "SELECT n.*, a.username as admin_name 
                  FROM notifications n 
                  LEFT JOIN admins a ON n.created_by = a.id 
                  WHERE n.target_audience = 'all' 
                  OR (n.target_audience = 'specific_route' AND n.target_id = :route_id)
                  ORDER BY n.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
    } else {
        // Get all notifications (for admin)
        $query = "SELECT n.*, a.username as admin_name 
                  FROM notifications n 
                  LEFT JOIN admins a ON n.created_by = a.id 
                  ORDER BY n.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'notifications' => $notifications,
        'count' => count($notifications),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>