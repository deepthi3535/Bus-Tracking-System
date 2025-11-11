<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';

// Only allow access if it's a development environment or first-time setup
$allow_access = true; // Set to false in production after initial setup

if (!$allow_access) {
    header('HTTP/1.1 403 Forbidden');
    exit('Admin creation is disabled.');
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if email already exists
            $check_query = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $error = "Email already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'admin';
                $created_at = date('Y-m-d H:i:s');

                // Insert new admin
                $query = "INSERT INTO users (name, email, password, role, created_at) 
                          VALUES (:name, :email, :password, :role, :created_at)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':created_at', $created_at);
                
                if ($stmt->execute()) {
                    $message = "Admin account created successfully!";
                    // Clear form
                    $name = $email = '';
                } else {
                    $error = "Failed to create admin account.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Check if admin users already exist
$admin_count = 0;
try {
    $count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $count_stmt = $db->query($count_query);
    $admin_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    $error = "Cannot check existing admins: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Setup</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <style>
        .setup-warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form" style="max-width: 600px;">
            <h2 style="text-align: center; margin-bottom: 2rem;">
                Create Admin Account
            </h2>

            <?php if ($admin_count > 0): ?>
                <div class="setup-warning">
                    <strong>⚠️ Warning:</strong> There are already <?php echo $admin_count; ?> admin account(s) in the system.
                    Only use this page for initial setup or if you've lost admin access.
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password * (min 6 characters)</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Create Admin Account
                </button>
            </form>

            <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p>Go to <a href="<?php echo BASE_URL; ?>">Homepage</a></p>
            </div>

            <!-- Security notice -->
            <div style="margin-top: 2rem; padding: 1rem; background: #f1f5f9; border-radius: 6px; font-size: 0.9rem;">
                <strong>Security Notice:</strong> This page should be disabled in production after initial setup.
                To disable, set <code>$allow_access = false;</code> in this file.
            </div>
        </div>
    </div>
</body>
</html>