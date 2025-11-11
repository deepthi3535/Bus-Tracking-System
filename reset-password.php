<?php
// Admin Password Reset Script
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

echo "<h2>Admin Password Reset</h2>";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Reset admin password to "admin123"
    $password = "admin123";
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE admins SET password = :password WHERE username = 'admin'");
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Admin password reset successfully!</p>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
        echo "<p>Hashed password stored: " . $hashed_password . "</p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Password reset failed.</p>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>