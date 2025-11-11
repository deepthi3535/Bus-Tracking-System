<?php
require_once '../includes/paths.php';

// Initialize session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Initialize database connection
        require_once '../includes/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            require_once '../includes/auth.php';
            $auth = new Auth($db);
            $admin = $auth->loginAdmin($username, $password);
            
            if ($admin) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'Database connection failed. Please try again later.';
            error_log("Database connection failed in admin login");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
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
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        /* Form Container */
        .form-container {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 40px;
            width: 100%;
            max-width: 440px;
            border-top: 4px solid var(--primary);
        }
        
        .form-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-dark);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        /* Alert Styles */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--secondary);
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(124, 58, 237, 0.4);
        }
        
        .form-container p {
            text-align: center;
            font-size: 0.9rem;
            color: var(--secondary);
        }
        
        .form-container a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .form-container a:hover {
            text-decoration: underline;
        }
        
        /* Footer Styles */
        footer {
            background: var(--dark);
            color: white;
            padding: 30px 0;
            text-align: center;
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
        
        /* Student Access */
        .student-access {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .student-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .student-btn:hover {
            text-decoration: underline;
        }
        
        /* Admin Specific Styling */
        .admin-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(124, 58, 237, 0.1);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
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
            
            .form-container {
                padding: 30px 20px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
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
            
            .form-container {
                padding: 25px 15px;
            }
            
            .form-container h2 {
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
                    <li><a href="<?php echo BASE_URL; ?>user/login.php">Student Login</a></li>
                    <li><a href="<?php echo BASE_URL; ?>admin/login.php">Admin Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="form-container">
            <h2>
                <i class="fas fa-users-cog"></i>
                Admin Login
                <span class="admin-badge">Secure</span>
            </h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required placeholder="Enter admin username">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required placeholder="Enter admin password">
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="student-access">
                <a href="<?php echo BASE_URL; ?>user/login.php" class="student-btn">
                    <i class="fas fa-user-graduate"></i> Student Login
                </a>
            </div>
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