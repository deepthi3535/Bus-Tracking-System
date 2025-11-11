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

// Get all routes
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM routes ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's current route
$user_id = $_SESSION['user_id'];
$query = "SELECT route_id FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_route_id = $user['route_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Routes - <?php echo APP_NAME; ?></title>
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
        
        /* Page Title */
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            color: var(--primary-dark);
        }
        
        .page-title i {
            font-size: 2rem;
        }
        
        /* Alert styles */
        .alert {
            padding: 15px 20px;
            margin-bottom: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-left: 4px solid var(--primary);
        }
        
        /* Routes grid */
        .routes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        /* Route card styles */
        .route-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 5px solid var(--primary);
        }
        
        .route-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .route-card.current-route {
            border-top: 5px solid var(--success);
            background: linear-gradient(to bottom right, #f0fdf4, #fff);
        }
        
        .route-card h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }
        
        .route-card h3 i {
            color: var(--primary);
        }
        
        .route-card > p {
            color: var(--secondary);
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .route-details {
            margin-top: 15px;
        }
        
        .route-details p {
            margin: 12px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .route-details strong {
            color: var(--dark);
            min-width: 100px;
        }
        
        .route-details i {
            color: var(--primary);
            width: 20px;
        }
        
        /* Badge styles */
        .badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            
            .routes-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 20px 15px;
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
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .route-card {
                padding: 20px;
            }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary);
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary-light);
        }
        
        .empty-state p {
            font-size: 1.1rem;
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
                    <li><a href="<?php echo BASE_URL; ?>user/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/profile.php">Profile</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <h2 class="page-title">
            <i class="fas fa-route"></i>
            Bus Routes
        </h2>
        
        <?php if ($user_route_id): ?>
            <div class="alert alert-info">
                <strong>Your Assigned Route:</strong> 
                <?php
                $user_route = array_filter($routes, function($route) use ($user_route_id) {
                    return $route['id'] == $user_route_id;
                });
                if (!empty($user_route)) {
                    $user_route = reset($user_route);
                    echo htmlspecialchars($user_route['name']) . ' - ' . htmlspecialchars($user_route['description']);
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="routes-grid">
            <?php if (count($routes) > 0): ?>
                <?php foreach ($routes as $route): ?>
                    <div class="route-card <?php echo $route['id'] == $user_route_id ? 'current-route' : ''; ?>">
                        <h3><i class="fas fa-bus"></i> <?php echo htmlspecialchars($route['name']); ?></h3>
                        <p><?php echo htmlspecialchars($route['description']); ?></p>
                        
                        <div class="route-details">
                            <p>
                                <i class="fas fa-map-marker-alt"></i>
                                <strong>Start Point:</strong> <?php echo htmlspecialchars($route['start_point']); ?>
                            </p>
                            <p>
                                <i class="fas fa-flag-checkered"></i>
                                <strong>End Point:</strong> <?php echo htmlspecialchars($route['end_point']); ?>
                            </p>
                            <p>
                                <i class="fas fa-map-pin"></i>
                                <strong>Stops:</strong> <?php echo htmlspecialchars($route['stops']); ?>
                            </p>
                            <p>
                                <i class="fas fa-clock"></i>
                                <strong>Schedule:</strong> <?php echo htmlspecialchars($route['schedule']); ?>
                            </p>
                        </div>
                        
                        <?php if ($route['id'] == $user_route_id): ?>
                            <div class="badge"><i class="fas fa-check"></i> Your Route</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-route"></i>
                    <p>No bus routes available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="footer-container">
            <div class="footer-links">
                <a href="<?php echo BASE_URL; ?>index.php">Home</a>
                <a href="<?php echo BASE_URL; ?>user/dashboard.php">Dashboard</a>
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