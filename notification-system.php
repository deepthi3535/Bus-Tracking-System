<?php
/**
 * Notification System
 * For sending notifications to users
 */

class NotificationSystem {
    private $db;
    
    public function __construct($db_connection) {
        $this->db = $db_connection;
    }
    
    /**
     * Send notification to all users
     */
    public function sendToAll($title, $message, $admin_id) {
        try {
            $query = "INSERT INTO notifications (title, message, target_audience, created_by) 
                      VALUES (:title, :message, 'all', :admin_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':admin_id', $admin_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to users on a specific route
     */
    public function sendToRoute($title, $message, $route_id, $admin_id) {
        try {
            $query = "INSERT INTO notifications (title, message, target_audience, target_id, created_by) 
                      VALUES (:title, :message, 'specific_route', :route_id, :admin_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':route_id', $route_id);
            $stmt->bindParam(':admin_id', $admin_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notifications for a user
     */
    public function getUserNotifications($user_id, $limit = 10) {
        try {
            // Get user's route if available
            $userQuery = "SELECT route_id FROM users WHERE id = :user_id";
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindParam(':user_id', $user_id);
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $route_id = $user ? $user['route_id'] : null;
            
            // Get notifications for this user
            $query = "SELECT * FROM notifications 
                     WHERE target_audience = 'all' 
                     OR (target_audience = 'specific_route' AND target_id = :route_id)
                     ORDER BY created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':route_id', $route_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Notification retrieval error: " . $e->getMessage());
            return [];
        }
    }
}
?>