<?php
class DriverAuth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function loginDriver($driver_id, $password) {
        if (!$this->conn) {
            error_log("Database connection not available in loginDriver");
            return false;
        }
        
        try {
            $query = "SELECT * FROM buses WHERE driver_id = :driver_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':driver_id', $driver_id);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $driver = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // For demo purposes, we're using simple password check
                // In production, use password_verify() with hashed passwords
                if ($password === 'password123') {
                    // Update login status
                    $updateQuery = "UPDATE buses SET is_logged_in = 1 WHERE id = :bus_id";
                    $updateStmt = $this->conn->prepare($updateQuery);
                    $updateStmt->bindParam(':bus_id', $driver['id']);
                    $updateStmt->execute();
                    
                    return $driver;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Driver login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function logoutDriver($bus_id) {
        if (!$this->conn) {
            return false;
        }
        
        try {
            $query = "UPDATE buses SET is_logged_in = 0 WHERE id = :bus_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':bus_id', $bus_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Driver logout error: " . $e->getMessage());
            return false;
        }
    }
    
    public function verifyDriverSession($bus_id) {
        if (!$this->conn) {
            return false;
        }
        
        try {
            $query = "SELECT is_logged_in FROM buses WHERE id = :bus_id AND is_logged_in = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':bus_id', $bus_id);
            $stmt->execute();
            
            return $stmt->rowCount() == 1;
        } catch (PDOException $e) {
            error_log("Driver session verification error: " . $e->getMessage());
            return false;
        }
    }
}
?>