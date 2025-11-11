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
    $route_id = $_POST['route_id'] ?? null;
    
    try {
        switch ($action) {
            case 'add':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $start_point = $_POST['start_point'];
                $end_point = $_POST['end_point'];
                $stops = json_encode(array_filter(array_map('trim', explode(',', $_POST['stops']))));
                
                $query = "INSERT INTO routes (name, description, start_point, end_point, stops) 
                          VALUES (:name, :description, :start_point, :end_point, :stops)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':start_point', $start_point);
                $stmt->bindParam(':end_point', $end_point);
                $stmt->bindParam(':stops', $stops);
                
                if ($stmt->execute()) {
                    $message = 'Route added successfully!';
                }
                break;
                
            case 'delete':
                $query = "DELETE FROM routes WHERE id = :route_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':route_id', $route_id);
                
                if ($stmt->execute()) {
                    $message = 'Route deleted successfully!';
                }
                break;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get all routes
$routes = [];
try {
    $stmt = $db->query("SELECT * FROM routes ORDER BY name");
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
    <title>Manage Routes - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="container">
        <h2>Manage Routes</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Add New Route</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Route Name*</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="start_point">Start Point*</label>
                    <input type="text" id="start_point" name="start_point" required>
                </div>
                
                <div class="form-group">
                    <label for="end_point">End Point*</label>
                    <input type="text" id="end_point" name="end_point" required>
                </div>
                
                <div class="form-group">
                    <label for="stops">Bus Stops (comma-separated)</label>
                    <textarea id="stops" name="stops" rows="3" 
                              placeholder="Stop 1, Stop 2, Stop 3"></textarea>
                </div>
                
                <button type="submit" class="btn">Add Route</button>
            </form>
        </div>

        <div class="card">
            <h3>All Routes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Route Name</th>
                        <th>Start Point</th>
                        <th>End Point</th>
                        <th>Bus Stops</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): 
                        $stops = json_decode($route['stops'] ?? '[]', true);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($route['name']); ?></td>
                        <td><?php echo htmlspecialchars($route['start_point']); ?></td>
                        <td><?php echo htmlspecialchars($route['end_point']); ?></td>
                        <td>
                            <?php if (is_array($stops) && count($stops) > 0): ?>
                                <?php echo implode(', ', $stops); ?>
                            <?php else: ?>
                                No stops defined
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this route?')">
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