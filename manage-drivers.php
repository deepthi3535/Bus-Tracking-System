<?php
require_once '../includes/paths.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

requireAdminLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Detect if buses.driver_contact column exists
$hasDriverContact = false;
try {
	$currentDb = $db->query("SELECT DATABASE()")->fetchColumn();
	$colStmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'buses' AND COLUMN_NAME = 'driver_contact'");
	$colStmt->bindParam(':db', $currentDb);
	$colStmt->execute();
	$hasDriverContact = (int)$colStmt->fetchColumn() > 0;
} catch (PDOException $e) {
	$hasDriverContact = false; // fallback
}

// Handle driver update/clear
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$bus_id = $_POST['bus_id'] ?? null;
	$action = $_POST['action'] ?? 'update';
	if (!$bus_id) {
		$error = 'Invalid request: missing bus ID.';
	} else {
		try {
			if ($action === 'clear') {
				$query = $hasDriverContact
					? "UPDATE buses SET driver_name = NULL, driver_contact = NULL WHERE id = :bus_id"
					: "UPDATE buses SET driver_name = NULL WHERE id = :bus_id";
				$stmt = $db->prepare($query);
				$stmt->bindParam(':bus_id', $bus_id);
				$stmt->execute();
				$message = 'Driver details cleared for the selected bus.';
			} else {
				$driver_name = trim($_POST['driver_name'] ?? '');
				if ($hasDriverContact) {
					$driver_contact = trim($_POST['driver_contact'] ?? '');
					$query = "UPDATE buses SET driver_name = :driver_name, driver_contact = :driver_contact WHERE id = :bus_id";
					$stmt = $db->prepare($query);
					$stmt->bindParam(':driver_name', $driver_name);
					$stmt->bindParam(':driver_contact', $driver_contact);
					$stmt->bindParam(':bus_id', $bus_id);
				} else {
					$query = "UPDATE buses SET driver_name = :driver_name WHERE id = :bus_id";
					$stmt = $db->prepare($query);
					$stmt->bindParam(':driver_name', $driver_name);
					$stmt->bindParam(':bus_id', $bus_id);
				}
				$stmt->execute();
				$message = 'Driver details updated successfully.';
			}
		} catch (PDOException $e) {
			$error = 'Database error: ' . $e->getMessage();
		}
	}
}

// Fetch buses and current driver info
$buses = [];
try {
	$select = "SELECT id, bus_number, driver_name" . ($hasDriverContact ? ", driver_contact" : "") . " FROM buses ORDER BY bus_number";
	$stmt = $db->query($select);
	$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	$error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manage Drivers - <?php echo APP_NAME; ?></title>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
	<?php include '../header.php'; ?>

	<div class="container">
		<h2>Manage Drivers</h2>

		<?php if (!empty($message)): ?>
			<div class="alert alert-success"><?php echo $message; ?></div>
		<?php endif; ?>
		<?php if (!empty($error)): ?>
			<div class="alert alert-error"><?php echo $error; ?></div>
		<?php endif; ?>

		<div class="card">
			<h3>Assign / Update Driver Per Bus</h3>
			<div class="table-container">
				<table>
					<thead>
						<tr>
							<th>Bus Number</th>
							<th>Driver Name</th>
							<?php if ($hasDriverContact): ?>
							<th>Driver Contact</th>
							<?php endif; ?>
							<th style="width: 230px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($buses as $bus): ?>
						<tr>
							<td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
							<td>
								<form method="POST" style="display:flex; gap:10px; align-items:center;">
									<input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
									<input type="text" name="driver_name" value="<?php echo htmlspecialchars($bus['driver_name'] ?? ''); ?>" placeholder="Driver name" class="form-control" style="min-width:200px;">
						</td>
						<?php if ($hasDriverContact): ?>
						<td>
								<input type="text" name="driver_contact" value="<?php echo htmlspecialchars($bus['driver_contact'] ?? ''); ?>" placeholder="Contact" class="form-control" style="min-width:180px;">
						</td>
						<?php endif; ?>
						<td>
								<button type="submit" name="action" value="update" class="btn btn-success">Save</button>
								<button type="submit" name="action" value="clear" class="btn btn-danger" onclick="return confirm('Clear driver details for this bus?');">Clear</button>
							</form>
						</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<?php include '../footer.php'; ?>
</body>
</html>


