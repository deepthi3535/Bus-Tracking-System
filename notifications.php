<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/session.php';

// Check if user is admin
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message_text = trim($_POST['message']);
    $target_audience = $_POST['target_audience'];
    $target_id = $_POST['target_id'] ?? null;
    $priority = $_POST['priority'] ?? 'normal';

    if (!empty($title) && !empty($message_text)) {
        try {
            $query = "INSERT INTO notifications (title, message, target_audience, target_id, priority, created_by) 
                      VALUES (:title, :message, :target_audience, :target_id, :priority, :created_by)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message_text);
            $stmt->bindParam(':target_audience', $target_audience);
            $stmt->bindParam(':target_id', $target_id);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = "Notification sent successfully!";
            } else {
                $error = "Failed to send notification.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Get routes for target selection
$routes = [];
$query = "SELECT id, name FROM routes ORDER BY name";
$stmt = $db->query($query);
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent notifications
$recent_notifications = [];
$query = "SELECT n.*, u.name as created_by_name 
          FROM notifications n 
          LEFT JOIN users u ON n.created_by = u.id 
          ORDER BY n.created_at DESC 
          LIMIT 10";
$stmt = $db->query($query);
$recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for icons
function priorityIcon($priority) {
    switch ($priority) {
        case 'low': return '<i class="fa fa-leaf"></i>';
        case 'normal': return '<i class="fa fa-info-circle"></i>';
        case 'high': return '<i class="fa fa-exclamation-circle"></i>';
        case 'urgent': return '<i class="fa fa-bell"></i>';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notifications - Admin Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">

    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f6f8; }
        .container { max-width: 1100px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2, h3 { margin-bottom: 15px; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .form-container { margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 6px; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        .btn-primary { padding: 10px 18px; background: #007bff; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-primary:hover { background: #0056b3; }
        .table-container { overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f1f1f1; }
        /* Status badges */
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: capitalize; display: inline-flex; align-items: center; gap: 5px; }
        .status-low { background-color: #d1f7c4; color: #256029; }
        .status-normal { background-color: #e0e0e0; color: #333; }
        .status-high { background-color: #ffe9b3; color: #7a4f01; }
        .status-urgent { background-color: #ffb3b3; color: #7a0000; }
        /* "New" badge */
        .new-badge { background: #007bff; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 8px; margin-left: 5px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Send Notifications</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="target_audience">Target Audience *</label>
                    <select id="target_audience" name="target_audience" required>
                        <option value="all">All Users</option>
                        <option value="specific_route">Specific Route</option>
                        <option value="drivers">Drivers Only</option>
                    </select>
                </div>

                <div class="form-group" id="route-selector" style="display: none;">
                    <label for="target_id">Select Route</label>
                    <select id="target_id" name="target_id">
                        <option value="">Select a route</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>"><?php echo htmlspecialchars($route['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Send Notification</button>
            </form>
        </div>

        <div class="recent-notifications">
            <h3>Recent Notifications</h3>
            <?php if (count($recent_notifications) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Target</th>
                                <th>Priority</th>
                                <th>Created By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_notifications as $notification): ?>
                            <?php $isNew = strtotime($notification['created_at']) > strtotime('-1 day'); ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                    <?php if ($isNew): ?><span class="new-badge">New</span><?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                <td>
                                    <?php 
                                    switch ($notification['target_audience']) {
                                        case 'all': echo 'All Users'; break;
                                        case 'specific_route': echo 'Route #' . $notification['target_id']; break;
                                        case 'drivers': echo 'Drivers Only'; break;
                                        default: echo $notification['target_audience'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $notification['priority'] ?? 'normal'; ?>">
                                        <?php echo priorityIcon($notification['priority'] ?? 'normal') . " " . ucfirst($notification['priority'] ?? 'Normal'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($notification['created_by_name'] ?? ''); ?></td>
                                <td><?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No notifications sent yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Show/hide route selector
        document.getElementById('target_audience').addEventListener('change', function() {
            const routeSelector = document.getElementById('route-selector');
            routeSelector.style.display = this.value === 'specific_route' ? 'block' : 'none';
        });
    </script>
</body>
</html>
