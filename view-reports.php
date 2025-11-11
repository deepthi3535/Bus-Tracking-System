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

// Get report parameters
$report_type = $_GET['report_type'] ?? 'daily';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$bus_id = $_GET['bus_id'] ?? '';

// Initialize reports data
$reports_data = [];
$chart_data = [];

// Generate reports based on type
try {
    switch ($report_type) {
        case 'daily':
            $query = "SELECT 
                        DATE(timestamp) as report_date,
                        bus_id,
                        b.bus_number,
                        COUNT(*) as total_locations,
                        MAX(distance_km) as total_distance,
                        AVG(speed) as avg_speed
                      FROM bus_locations bl
                      LEFT JOIN buses b ON bl.bus_id = b.id
                      WHERE DATE(timestamp) BETWEEN :start_date AND :end_date
                      " . ($bus_id ? " AND bus_id = :bus_id" : "") . "
                      GROUP BY DATE(timestamp), bus_id
                      ORDER BY report_date DESC, bus_number";
            break;

        case 'bus_usage':
            $query = "SELECT 
                        b.id,
                        b.bus_number,
                        b.driver_name,
                        COUNT(bl.id) as total_records,
                        MAX(bl.distance_km) as total_distance,
                        AVG(bl.speed) as avg_speed,
                        MAX(bl.timestamp) as last_active
                      FROM buses b
                      LEFT JOIN bus_locations bl ON b.id = bl.bus_id
                      WHERE bl.timestamp BETWEEN :start_date AND :end_date
                      GROUP BY b.id
                      ORDER BY total_records DESC";
            break;

        case 'route_performance':
            $query = "SELECT 
                        r.id,
                        r.name as route_name,
                        COUNT(DISTINCT b.id) as total_buses,
                        COUNT(bl.id) as total_locations,
                        MAX(bl.distance_km) as max_distance,
                        AVG(bl.speed) as avg_speed
                      FROM routes r
                      LEFT JOIN buses b ON r.id = b.current_route_id
                      LEFT JOIN bus_locations bl ON b.id = bl.bus_id
                      WHERE bl.timestamp BETWEEN :start_date AND :end_date
                      GROUP BY r.id
                      ORDER BY total_locations DESC";
            break;
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    
    if ($bus_id && $report_type !== 'bus_usage') {
        $stmt->bindParam(':bus_id', $bus_id);
    }
    
    $stmt->execute();
    $reports_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare chart data for daily report
    if ($report_type === 'daily') {
        $chart_query = "SELECT 
                         DATE(timestamp) as date,
                         COUNT(*) as count,
                         SUM(distance_km) as distance
                       FROM bus_locations 
                       WHERE DATE(timestamp) BETWEEN :start_date AND :end_date
                       GROUP BY DATE(timestamp)
                       ORDER BY date";
        
        $chart_stmt = $db->prepare($chart_query);
        $chart_stmt->bindParam(':start_date', $start_date);
        $chart_stmt->bindParam(':end_date', $end_date);
        $chart_stmt->execute();
        $chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get buses for filter
$buses = [];
$bus_query = "SELECT id, bus_number FROM buses ORDER BY bus_number";
$bus_stmt = $db->query($bus_query);
$buses = $bus_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - Admin Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <!-- Note: Chart.js currently from CDN; for full offline support, vendor locally if needed -->
    <script src="<?php echo BASE_URL; ?>assets/vendor/chart.js/chart.umd.min.js"></script>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="container">
        <h2>System Reports</h2>

        <!-- Report Filters -->
        <div class="card">
            <h3>Report Filters</h3>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="report_type">Report Type</label>
                    <select id="report_type" name="report_type">
                        <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Daily Activity</option>
                        <option value="bus_usage" <?php echo $report_type === 'bus_usage' ? 'selected' : ''; ?>>Bus Usage</option>
                        <option value="route_performance" <?php echo $report_type === 'route_performance' ? 'selected' : ''; ?>>Route Performance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>

                <?php if ($report_type === 'daily'): ?>
                <div class="form-group">
                    <label for="bus_id">Bus Filter (Optional)</label>
                    <select id="bus_id" name="bus_id">
                        <option value="">All Buses</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?php echo $bus['id']; ?>" <?php echo $bus_id == $bus['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bus['bus_number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
        </div>

        <!-- Report Results -->
        <div class="report-results">
            <h3>Report Results: <?php echo ucfirst(str_replace('_', ' ', $report_type)); ?> Report</h3>
            
            <?php if (!empty($reports_data)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <?php if ($report_type === 'daily'): ?>
                                    <th>Date</th>
                                    <th>Bus Number</th>
                                    <th>Locations</th>
                                    <th>Distance (km)</th>
                                    <th>Avg Speed</th>
                                <?php elseif ($report_type === 'bus_usage'): ?>
                                    <th>Bus Number</th>
                                    <th>Driver</th>
                                    <th>Records</th>
                                    <th>Distance (km)</th>
                                    <th>Avg Speed</th>
                                    <th>Last Active</th>
                                <?php elseif ($report_type === 'route_performance'): ?>
                                    <th>Route Name</th>
                                    <th>Buses</th>
                                    <th>Locations</th>
                                    <th>Max Distance</th>
                                    <th>Avg Speed</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports_data as $row): ?>
                            <tr>
                                <?php if ($report_type === 'daily'): ?>
                                    <td><?php echo date('M j, Y', strtotime($row['report_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['bus_number']); ?></td>
                                    <td><?php echo number_format($row['total_locations']); ?></td>
                                    <td><?php echo number_format($row['total_distance'], 2); ?></td>
                                    <td><?php echo number_format($row['avg_speed'], 1); ?> km/h</td>
                                <?php elseif ($report_type === 'bus_usage'): ?>
                                    <td><?php echo htmlspecialchars($row['bus_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['driver_name']); ?></td>
                                    <td><?php echo number_format($row['total_records']); ?></td>
                                    <td><?php echo number_format($row['total_distance'], 2); ?></td>
                                    <td><?php echo number_format($row['avg_speed'], 1); ?> km/h</td>
                                    <td><?php echo $row['last_active'] ? date('M j, g:i a', strtotime($row['last_active'])) : 'Never'; ?></td>
                                <?php elseif ($report_type === 'route_performance'): ?>
                                    <td><?php echo htmlspecialchars($row['route_name']); ?></td>
                                    <td><?php echo number_format($row['total_buses']); ?></td>
                                    <td><?php echo number_format($row['total_locations']); ?></td>
                                    <td><?php echo number_format($row['max_distance'], 2); ?></td>
                                    <td><?php echo number_format($row['avg_speed'], 1); ?> km/h</td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Chart for daily report -->
                <?php if ($report_type === 'daily' && !empty($chart_data)): ?>
                <div class="card">
                    <h3>Activity Chart</h3>
                    <canvas id="activityChart" width="400" height="200"></canvas>
                    <script>
                        const ctx = document.getElementById('activityChart').getContext('2d');
                        const chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode(array_column($chart_data, 'date')); ?>,
                                datasets: [{
                                    label: 'Location Records',
                                    data: <?php echo json_encode(array_column($chart_data, 'count')); ?>,
                                    borderColor: '#2563eb',
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    </script>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <p>No data found for the selected criteria.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../footer.php'; ?>
</body>
</html>