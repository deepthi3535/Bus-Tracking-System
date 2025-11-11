<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

requireAdminLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bus_id = $_POST['bus_id'] ?? null;
    
    try {
        switch ($action) {
            case 'add':
                $bus_number = $_POST['bus_number'];
                $capacity = $_POST['capacity'];
                $driver_name = $_POST['driver_name'];
                $driver_contact = $_POST['driver_contact'];
                $route_id = $_POST['route_id'] ?: null;
                
                $query = "INSERT INTO buses (bus_number, capacity, driver_name, driver_contact, current_route_id) 
                          VALUES (:bus_number, :capacity, :driver_name, :driver_contact, :route_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':bus_number', $bus_number);
                $stmt->bindParam(':capacity', $capacity);
                $stmt->bindParam(':driver_name', $driver_name);
                $stmt->bindParam(':driver_contact', $driver_contact);
                $stmt->bindParam(':route_id', $route_id);
                
                if ($stmt->execute()) {
                    $message = 'Bus added successfully!';
                }
                break;
                
            case 'edit':
                $bus_number = $_POST['bus_number'];
                $capacity = $_POST['capacity'];
                $driver_name = $_POST['driver_name'];
                $driver_contact = $_POST['driver_contact'];
                $route_id = $_POST['route_id'] ?: null;
                $status = $_POST['status'];
                
                $query = "UPDATE buses SET bus_number = :bus_number, capacity = :capacity, 
                          driver_name = :driver_name, driver_contact = :driver_contact, 
                          current_route_id = :route_id, status = :status 
                          WHERE id = :bus_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':bus_number', $bus_number);
                $stmt->bindParam(':capacity', $capacity);
                $stmt->bindParam(':driver_name', $driver_name);
                $stmt->bindParam(':driver_contact', $driver_contact);
                $stmt->bindParam(':route_id', $route_id);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':bus_id', $bus_id);
                
                if ($stmt->execute()) {
                    $message = 'Bus updated successfully!';
                }
                break;
                
            case 'delete':
                $query = "DELETE FROM buses WHERE id = :bus_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':bus_id', $bus_id);
                
                if ($stmt->execute()) {
                    $message = 'Bus deleted successfully!';
                }
                break;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get all buses with route information
$buses = [];
$routes = [];

try {
    $stmt = $db->query("
        SELECT b.*, r.name as route_name 
        FROM buses b 
        LEFT JOIN routes r ON b.current_route_id = r.id 
        ORDER BY b.bus_number
    ");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT id, name FROM routes");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="container">
        <h2>Manage Buses</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Add New Bus</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="bus_number">Bus Number*</label>
                    <input type="text" id="bus_number" name="bus_number" required>
                </div>
                
                <div class="form-group">
                    <label for="capacity">Capacity*</label>
                    <input type="number" id="capacity" name="capacity" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="driver_name">Driver Name</label>
                    <input type="text" id="driver_name" name="driver_name">
                </div>
                
                <div class="form-group">
                    <label for="driver_contact">Driver Contact</label>
                    <input type="tel" id="driver_contact" name="driver_contact">
                </div>
                
                <div class="form-group">
                    <label for="route_id">Assigned Route</label>
                    <select id="route_id" name="route_id">
                        <option value="">Select Route</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>">
                                <?php echo htmlspecialchars($route['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">Add Bus</button>
            </form>
        </div>

        <div class="card">
            <h3>All Buses</h3>
            <table>
                <thead>
                    <tr>
                        <th>Bus Number</th>
                        <th>Capacity</th>
                        <th>Driver</th>
                        <th>Route</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buses as $bus): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bus['bus_number']); ?></td>
                        <td><?php echo $bus['capacity']; ?></td>
                        <td><?php echo htmlspecialchars($bus['driver_name'] ?? 'Not assigned'); ?></td>
                        <td><?php echo htmlspecialchars($bus['route_name'] ?? 'Not assigned'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $bus['status']; ?>">
                                <?php echo ucfirst($bus['status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this bus?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>