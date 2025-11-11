<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';

// Check if user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'user/login.php');
    exit;
}

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Get user details
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's bus if assigned
$user_bus = null;
if ($user['route_id']) {
    $query = "SELECT b.*, r.name as route_name 
              FROM buses b 
              LEFT JOIN routes r ON b.current_route_id = r.id 
              WHERE b.current_route_id = :route_id AND b.status = 'active' 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':route_id', $user['route_id']);
    $stmt->execute();
    $user_bus = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get recent notifications
$query = "SELECT * FROM notifications 
          WHERE target_audience = 'all' OR (target_audience = 'specific_route' AND target_id = :route_id)
          ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':route_id', $user['route_id']);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/fontawesome/css/all.min.css">
    <style>
        /* Base Styles */
        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light: #ddd6fe;
            --secondary: #4b5563;
            --accent: #f59e0b;
            --light: #f9fafb;
            --dark: #1f2937;
            --success: #10b981;
            --danger: #ef4444;
            --card-bg: #ffffff;
            --gradient-start: #7c3aed;
            --gradient-end: #8b5cf6;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 12px;
            --input-border: #d1d5db;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow);
        }
        
        .header-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .logo-icon {
            font-size: 1.8rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
        
        nav a:hover {
            opacity: 0.8;
        }
        
        /* Main Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            flex: 1;
        }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: 30px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .welcome-text p {
            opacity: 0.9;
        }
        
        .student-id {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .card h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .card-content {
            margin-top: 15px;
        }
        
        .card-content p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .quick-actions h3 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--light);
            padding: 20px;
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .action-btn:hover {
            background: var(--primary-light);
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .action-btn span {
            font-weight: 600;
        }
        
        /* Notifications */
        .notifications-preview {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .notifications-preview h3 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }
        
        .notification-list {
            list-style: none;
        }
        
        .notification-item {
            padding: 15px;
            border-left: 4px solid var(--primary);
            background: var(--light);
            border-radius: 0 var(--radius) var(--radius) 0;
            margin-bottom: 15px;
        }
        
        .notification-item:last-child {
            margin-bottom: 0;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .notification-message {
            color: var(--secondary);
            margin-bottom: 8px;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-all {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        /* Footer Styles */
        footer {
            background: var(--dark);
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-top: auto;
        }
        
        .footer-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        .footer-links a:hover {
            opacity: 0.8;
        }
        
        .copyright {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                gap: 15px;
            }
            
            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .logo h1 {
                font-size: 1.3rem;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .welcome-text h2 {
                font-size: 1.5rem;
            }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary-light);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                <i class="fas fa-bus logo-icon"></i>
                <h1><?php echo APP_NAME; ?></h1>
            </a>
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/profile.php">Profile</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="welcome-section">
            <div class="welcome-text">
                <h2>
                    <i class="fas fa-graduation-cap"></i>
                    Welcome, <?php echo htmlspecialchars($user['name']); ?>!
                </h2>
                <p>Manage your bus transportation details and stay updated with notifications</p>
            </div>
            <div class="student-id">
                <i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($user['student_id']); ?>
            </div>
        </div>
        
        <div class="dashboard-cards">
            <div class="card">
                <h3>
                    <div class="card-icon">
                        <i class="fas fa-bus"></i>
                    </div>
                    Your Bus
                </h3>
                <div class="card-content">
                    <?php if ($user_bus): ?>
                        <p><i class="fas fa-bus"></i> <?php echo $user_bus['bus_number']; ?></p>
                        <p><i class="fas fa-route"></i> Route: <?php echo $user_bus['route_name']; ?></p>
                        <p><i class="fas fa-user"></i> Driver: <?php echo $user_bus['driver_name']; ?></p>
                        <p>
                            <i class="fas fa-circle"></i> Status: 
                            <span class="status-badge status-active">Active</span>
                        </p>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bus-slash"></i>
                            <p>No bus assigned yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h3>
                    <div class="card-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    Student Information
                </h3>
                <div class="card-content">
                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($user['student_id']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if ($user['phone']): ?>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h3>
                    <div class="card-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    Your Route
                </h3>
                <div class="card-content">
                    <?php if ($user['route_id']): ?>
                        <p><i class="fas fa-route"></i> Route #<?php echo $user['route_id']; ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> Pickup: Main Campus Gate</p>
                        <p><i class="fas fa-clock"></i> Morning: 7:30 AM</p>
                        <p><i class="fas fa-clock"></i> Evening: 4:30 PM</p>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>No route assigned yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="quick-actions">
            <h3>
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h3>
            <div class="action-buttons">
                <a href="<?php echo BASE_URL; ?>user/track-bus.php" class="action-btn">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Track Bus</span>
                </a>
                <a href="<?php echo BASE_URL; ?>user/bus-routes.php" class="action-btn">
                    <i class="fas fa-route"></i>
                    <span>View Routes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>user/notifications.php" class="action-btn">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
                <a href="<?php echo BASE_URL; ?>user/profile.php" class="action-btn">
                    <i class="fas fa-user-cog"></i>
                    <span>Your Profile</span>
                </a>
            </div>
        </div>
        
        <div class="notifications-preview">
            <h3>
                <i class="fas fa-bell"></i>
                Recent Notifications
            </h3>
            <?php if (count($notifications) > 0): ?>
                <ul class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                    <li class="notification-item">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-time">
                            <i class="fas fa-clock"></i>
                            <?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications available</p>
                </div>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>user/notifications.php" class="view-all">
                View all notifications <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <footer>
        <div class="footer-container">
            <div class="footer-links">
                <a href="<?php echo BASE_URL; ?>index.php">Home</a>
                <a href="#">About</a>
                <a href="#">Contact</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>