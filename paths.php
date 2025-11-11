<?php
// ===== ABSOLUTE PATH =====
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Include config if not already defined
if (!defined('DB_HOST')) {
    require_once ROOT_PATH . 'config.php';
}

// ===== ASSET PATHS (using BASE_URL from config) =====
define('CSS_PATH', BASE_URL . 'css/');
define('JS_PATH', BASE_URL . 'js/');
define('IMG_PATH', BASE_URL . 'assets/images/');
define('ICON_PATH', BASE_URL . 'assets/icons/');

// ===== API PATHS =====
define('API_BASE', BASE_URL . 'api/');
define('TRACCAR_ENDPOINT', API_BASE . 'traccar.php');

// ===== SECTION PATHS =====
define('ADMIN_BASE', BASE_URL . 'admin/');
define('USER_BASE', BASE_URL . 'user/');
define('DRIVER_BASE', BASE_URL . 'driver/');

// Debug function
function debug_paths() {
    echo "<h3>Path Debug Information</h3>";
    echo "ROOT_PATH: " . ROOT_PATH . "<br>";
    echo "BASE_URL: " . BASE_URL . "<br>";
    echo "CSS_PATH: " . CSS_PATH . "<br>";
    echo "TRACCAR_ENDPOINT: " . TRACCAR_ENDPOINT . "<br>";
    exit;
}

// Uncomment to test paths:
// debug_paths();
?>