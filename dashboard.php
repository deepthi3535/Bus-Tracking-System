<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

requireAdminLogin();

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Get statistics for dashboard
$stats = [
    'total_buses' => 0,
    'active_buses' => 0,
    'total_routes' => 0,
    'total_users' => 0,
    'total_drivers' => 0,
    'total_notifications' => 0,
    'online_buses' => 0
];

// Get recent activities
$recent_locations = [];
$recent_notifications = [];

if ($db) {
    try {
        // Get statistics
        $stats['total_buses'] = $db->query("SELECT COUNT(*) FROM buses")->fetchColumn();
        $stats['active_buses'] = $db->query("SELECT COUNT(*) FROM buses WHERE status = 'active'")->fetchColumn();
        $stats['total_routes'] = $db->query("SELECT COUNT(*) FROM routes")->fetchColumn();
        $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_drivers'] = $db->query("SELECT COUNT(DISTINCT driver_name) FROM buses WHERE driver_name IS NOT NULL")->fetchColumn();
        $stats['total_notifications'] = $db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
        $stats['online_buses'] = $db->query("SELECT COUNT(DISTINCT bus_id) FROM bus_locations WHERE timestamp >= NOW() - INTERVAL 5 MINUTE")->fetchColumn();

        // Get recent bus locations
        $stmt = $db->query("
            SELECT bl.*, b.bus_number, b.driver_name 
            FROM bus_locations bl 
            JOIN buses b ON bl.bus_id = b.id 
            ORDER BY bl.timestamp DESC 
            LIMIT 10
        ");
        $recent_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent notifications
        $stmt = $db->query("
            SELECT n.*, a.username as admin_name 
            FROM notifications n 
            LEFT JOIN admins a ON n.created_by = a.id 
            ORDER BY n.created_at DESC 
            LIMIT 5
        ");
        $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Dashboard error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin: -20px -20px 20px -20px;
            border-radius: 0 0 10px 10px;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1rem;
        }
        .card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .quick-actions .btn {
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }
        .recent-activity {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        @media (max-width: 768px) {
            .recent-activity {
                grid-template-columns: 1fr;
            }
        }
        .activity-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo $_SESSION['admin_name']; ?>!</p>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Buses</h3>
                <div class="number"><?php echo $stats['total_buses']; ?></div>
                <small><?php echo $stats['active_buses']; ?> active</small>
            </div>
            
            <div class="card">
                <h3>Bus Routes</h3>
                <div class="number"><?php echo $stats['total_routes']; ?></div>
                <small>Active routes</small>
            </div>
            
            <div class="card">
                <h3>Registered Users</h3>
                <div class="number"><?php echo $stats['total_users']; ?></div>
                <small>Student accounts</small>
            </div>
            
            <div class="card">
                <h3>Drivers</h3>
                <div class="number"><?php echo $stats['total_drivers']; ?></div>
                <small>Active drivers</small>
            </div>

            <div class="card">
                <h3>Online Buses</h3>
                <div class="number"><?php echo $stats['online_buses']; ?></div>
                <small>Currently tracking</small>
            </div>

            <div class="card">
                <h3>Notifications</h3>
                <div class="number"><?php echo $stats['total_notifications']; ?></div>
                <small>Messages sent</small>
            </div>
        </div>

        <div class="quick-actions">
            <a href="manage-buses.php" class="btn">🚌 Manage Buses</a>
            <a href="manage-routes.php" class="btn">🗺️ Manage Routes</a>
            <a href="register-device.php" class="btn">📱 Register Devices</a>
            <a href="notifications.php" class="btn">📢 Send Notifications</a>
            <a href="view-reports.php" class="btn">📊 View Reports</a>
        </div>

        <div class="recent-activity">
            <div class="activity-card">
                <h3>Recent Bus Locations</h3>
                <?php if (count($recent_locations) > 0): ?>
                    <?php foreach ($recent_locations as $location): ?>
                    <div class="activity-item">
                        <strong><?php echo $location['bus_number']; ?></strong>
                        <p><?php echo $location['driver_name']; ?></p>
                        <p><?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?></p>
                        <small><?php echo date('M j, g:i a', strtotime($location['timestamp'])); ?></small>
                    </div>
                    <hr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No recent location data available.</p>
                <?php endif; ?>
            </div>

            <div class="activity-card">
                <h3>Recent Notifications</h3>
                <?php if (count($recent_notifications) > 0): ?>
                    <?php foreach ($recent_notifications as $notification): ?>
                    <div class="activity-item">
                        <strong><?php echo $notification['title']; ?></strong>
                        <p><?php echo substr($notification['message'], 0, 50); ?>...</p>
                        <small>By: <?php echo $notification['admin_name']; ?> | 
                            <?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?></small>
                    </div>
                    <hr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No notifications sent yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>