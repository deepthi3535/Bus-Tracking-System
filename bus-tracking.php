<?php
/**
 * Bus Location Simulation System
 * Optional - Use if you want simulated bus movement instead of real GPS
 */

class BusTracker {
    private $db;
    
    public function __construct($db_connection) {
        $this->db = $db_connection;
    }
    
    /**
     * Simulate movement of all active buses (for demo purposes)
     */
    public function simulateMovement() {
        // Get all active buses
        $query = "SELECT * FROM buses WHERE status = 'active'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($buses as $bus) {
            $this->updateBusLocation($bus['id']);
        }
        
        return count($buses);
    }
    
    /**
     * Update a specific bus's location (simulated)
     */
    private function updateBusLocation($bus_id) {
        // Get bus details
        $query = "SELECT * FROM buses WHERE id = :bus_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':bus_id', $bus_id);
        $stmt->execute();
        $bus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bus || !$bus['current_route_id']) {
            return false;
        }
        
        // Generate random movement around current position
        $latChange = (rand(-100, 100) / 100000); // Small random change
        $lngChange = (rand(-100, 100) / 100000); // Small random change
        
        $newLat = $bus['current_lat'] + $latChange;
        $newLng = $bus['current_lng'] + $lngChange;
        
        // Update bus location in database
        $query = "UPDATE buses 
                 SET current_lat = :lat, current_lng = :lng, last_updated = NOW() 
                 WHERE id = :bus_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':lat', $newLat);
        $stmt->bindParam(':lng', $newLng);
        $stmt->bindParam(':bus_id', $bus_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get current location of a specific bus
     */
    public function getBusLocation($bus_id) {
        $query = "SELECT * FROM buses WHERE id = :bus_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':bus_id', $bus_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all buses with their current locations
     */
    public function getAllBusLocations() {
        $query = "SELECT b.*, r.name as route_name 
                 FROM buses b 
                 LEFT JOIN routes r ON b.current_route_id = r.id 
                 WHERE b.status = 'active'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>