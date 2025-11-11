<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

requireAdminLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Get all buses
$buses = [];
try {
    $stmt = $db->query("SELECT id, bus_number, driver_name FROM buses ORDER BY bus_number");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_id = $_POST['bus_id'] ?? null;
    $device_id = trim($_POST['device_id'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $phone_model = trim($_POST['phone_model'] ?? '');
    
    if (empty($bus_id) || empty($device_id)) {
        $error = 'Please select a bus and enter device ID';
    } else {
        try {
            $query = "INSERT INTO bus_devices (bus_id, device_id, driver_name, phone_model) 
                      VALUES (:bus_id, :device_id, :driver_name, :phone_model)
                      ON DUPLICATE KEY UPDATE 
                      bus_id = VALUES(bus_id), 
                      driver_name = VALUES(driver_name), 
                      phone_model = VALUES(phone_model),
                      is_active = 1";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':bus_id', $bus_id);
            $stmt->bindParam(':device_id', $device_id);
            $stmt->bindParam(':driver_name', $driver_name);
            $stmt->bindParam(':phone_model', $phone_model);
            
            if ($stmt->execute()) {
                $affectedRows = $stmt->rowCount();
                if ($affectedRows > 0) {
                    $message = 'Device registered successfully!';
                } else {
                    $message = 'Device updated successfully!';
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                $error = 'Failed to register device. Error: ' . $errorInfo[2];
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry error
                $error = 'Device ID already exists! Please use a different ID or update the existing one.';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get registered devices
$devices = [];
try {
    $stmt = $db->query("
        SELECT d.*, b.bus_number 
        FROM bus_devices d 
        JOIN buses b ON d.bus_id = b.id 
        ORDER BY d.created_at DESC
    ");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Get device statistics
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
try {
    $stmt = $db->query("SELECT COUNT(*) as total, 
                               SUM(is_active = 1) as active, 
                               SUM(is_active = 0) as inactive 
                        FROM bus_devices");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently fail stats, not critical
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Devices - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <style>
        .stats-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #007bff;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="container">
        <h2>Register Driver Devices</h2>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Devices</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active'] ?? 0; ?></div>
                <div class="stat-label">Active Devices</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['inactive'] ?? 0; ?></div>
                <div class="stat-label">Inactive Devices</div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Register New Device</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="bus_id">Select Bus*</label>
                    <select id="bus_id" name="bus_id" required>
                        <option value="">Select a bus</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?php echo $bus['id']; ?>" 
                                <?php echo (isset($_POST['bus_id']) && $_POST['bus_id'] == $bus['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bus['bus_number']); ?> - 
                                <?php echo htmlspecialchars($bus['driver_name'] ?? 'No driver'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="device_id">Device ID*</label>
                    <input type="text" id="device_id" name="device_id" required 
                           placeholder="Enter Traccar device ID"
                           value="<?php echo htmlspecialchars($_POST['device_id'] ?? ''); ?>">
                    <small>This is the unique ID shown in the Traccar Client app</small>
                </div>
                
                <div class="form-group">
                    <label for="driver_name">Driver Name</label>
                    <input type="text" id="driver_name" name="driver_name" 
                           placeholder="Driver's name"
                           value="<?php echo htmlspecialchars($_POST['driver_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone_model">Phone Model</label>
                    <input type="text" id="phone_model" name="phone_model" 
                           placeholder="e.g., Android Phone, iPhone"
                           value="<?php echo htmlspecialchars($_POST['phone_model'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn">Register Device</button>
            </form>
        </div>

        <div class="card">
            <h3>Registered Devices (<?php echo count($devices); ?>)</h3>
            <?php if (count($devices) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Device ID</th>
                            <th>Bus Number</th>
                            <th>Driver Name</th>
                            <th>Phone Model</th>
                            <th>Registered On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($device['device_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($device['bus_number']); ?></td>
                                <td><?php echo htmlspecialchars($device['driver_name']); ?></td>
                                <td><?php echo htmlspecialchars($device['phone_model'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y g:i a', strtotime($device['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $device['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $device['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['device_id']); ?>">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <button type="submit" class="btn btn-sm">
                                            <?php echo $device['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No devices registered yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Setup Instructions</h3>
            <ol>
                <li>Install <strong>Traccar Client</strong> from Play Store/App Store</li>
                <li>Open the app and note the <strong>Device ID</strong> from Status section</li>
                <li>Set <strong>Server URL</strong> to: 
                    <code><?php echo BASE_URL; ?>api/traccar.php</code>
                </li>
                <li>Set <strong>Frequency</strong> to 15-30 seconds</li>
                <li>Enable <strong>High Accuracy</strong> and <strong>Wake Lock</strong></li>
                <li>Register the <strong>exact Device ID</strong> here with the corresponding bus</li>
                <li>Wait 2-3 minutes for data to start appearing on the map</li>
            </ol>
            
            <div class="alert alert-info">
                <strong>Tip:</strong> Check <code>logs/traccar_debug.log</code> to see the exact device ID being sent by the app!
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>