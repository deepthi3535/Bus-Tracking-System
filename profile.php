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

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user details
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get available routes for dropdown
$query = "SELECT * FROM routes ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : null;
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        $query = "UPDATE users SET name = :name, email = :email, phone = :phone, route_id = :route_id WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            // Update session
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['route_id'] = $route_id;
            
            // Refresh user data
            $query = "SELECT * FROM users WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update profile.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
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
            max-width: 1000px;
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
        
        /* Card Styles */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .card-title {
            color: var(--primary-dark);
            font-size: 1.5rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            color: var(--primary);
        }
        
        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label i {
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--input-border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--light);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
        }
        
        .form-control:disabled {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        
        .form-help {
            display: block;
            margin-top: 8px;
            color: var(--secondary);
            font-size: 0.875rem;
        }
        
        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.15);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* Alert styles */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* Profile Info Styles */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: var(--radius);
        }
        
        .info-label {
            font-size: 0.875rem;
            color: var(--secondary);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
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
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .logo h1 {
                font-size: 1.3rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
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
                    <li><a href="<?php echo BASE_URL; ?>user/bus-routes.php">Routes</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/notifications.php">Notifications</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <h2 class="page-title">
            <i class="fas fa-user-circle"></i>
            Your Profile
        </h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3 class="card-title">
                <i class="fas fa-user-edit"></i>
                Personal Information
            </h3>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="student_id"><i class="fas fa-id-card"></i> Student ID</label>
                        <input type="text" id="student_id" class="form-control" value="<?php echo htmlspecialchars($user['student_id']); ?>" disabled>
                        <small class="form-help">Student ID cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="name"><i class="fas fa-signature"></i> Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="route_id"><i class="fas fa-route"></i> Bus Route</label>
                        <select id="route_id" name="route_id" class="form-control">
                            <option value="">-- Select Route --</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['id']; ?>" <?php echo $user['route_id'] == $route['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($route['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i>
                    Update Profile
                </button>
            </form>
        </div>
        
        <div class="card">
            <h3 class="card-title">
                <i class="fas fa-info-circle"></i>
                Account Information
            </h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Member since</span>
                    <span class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Last login</span>
                    <span class="info-value"><?php 
                        $last_login_ts = !empty($user['last_login']) ? strtotime($user['last_login']) : null;
                        echo $last_login_ts ? date('M j, Y g:i a', $last_login_ts) : 'Never';
                    ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="status-badge">
                        <i class="fas fa-check-circle"></i>
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="footer-container">
            <div class="footer-links">
                <a href="<?php echo BASE_URL; ?>index.php">Home</a>
                <a href="<?php echo BASE_URL; ?>user/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>user/bus-routes.php">Routes</a>
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