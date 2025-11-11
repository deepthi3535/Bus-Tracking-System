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

// Get user's route information
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$query = "SELECT route_id FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$route_id = $user['route_id'];

// Get active buses for user's route
$buses = [];
if ($route_id) {
    $query = "SELECT b.*, r.name as route_name, 
                     bl.latitude, bl.longitude, bl.timestamp as last_update,
                     bl.speed, bl.bearing, bl.accuracy
              FROM buses b 
              LEFT JOIN routes r ON b.current_route_id = r.id 
              LEFT JOIN bus_locations bl ON b.id = bl.bus_id 
                         AND bl.timestamp = (SELECT MAX(timestamp) FROM bus_locations WHERE bus_id = b.id)
              WHERE b.current_route_id = :route_id AND b.status = 'active' 
              ORDER BY b.bus_number";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':route_id', $route_id);
    $stmt->execute();
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all buses with locations for the map
$query = "SELECT b.*, r.name as route_name, 
                 bl.latitude, bl.longitude, bl.timestamp as last_update,
                 bl.speed, bl.bearing, bl.accuracy
          FROM buses b 
          LEFT JOIN routes r ON b.current_route_id = r.id 
          LEFT JOIN bus_locations bl ON b.id = bl.bus_id 
                     AND bl.timestamp = (SELECT MAX(timestamp) FROM bus_locations WHERE bus_id = b.id)
          WHERE b.status = 'active'
          ORDER BY b.bus_number";
$stmt = $db->prepare($query);
$stmt->execute();
$all_buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Bus Tracking - <?php echo APP_NAME; ?></title>
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
            padding: 20px;
            flex: 1;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-dark);
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        /* Map Styles */
        #map {
            height: 500px;
            width: 100%;
            border-radius: var(--radius);
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            z-index: 1;
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(124, 58, 237, 0.4);
        }
        
        .refresh-btn {
            margin-bottom: 20px;
        }
        
        /* Card Styles */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
            border-top: 4px solid var(--primary);
        }
        
        .card h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Bus List Styles */
        .bus-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .bus-item {
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .bus-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }
        
        .bus-item.active {
            background: #e3f2fd;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
        }
        
        .bus-item strong {
            color: var(--primary-dark);
            font-size: 1.1rem;
        }
        
        .bus-item small {
            color: var(--secondary);
        }
        
        /* Info Panel Styles */
        .info-panel {
            background: var(--light);
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }
        
        .info-panel h4 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .info-panel p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            
            .bus-list {
                grid-template-columns: 1fr;
            }
            
            #map {
                height: 400px;
            }
        }
        
        @media (max-width: 480px) {
            .logo h1 {
                font-size: 1.3rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .card {
                padding: 15px;
            }
        }
        
        /* Custom Bus Marker */
        .bus-marker {
            background: none !important;
            border: none !important;
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Status Indicators */
        .status-online {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-offline {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: var(--danger);
            border-radius: 50%;
            margin-right: 5px;
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
                    <li><a href="<?php echo BASE_URL; ?>user/index.php">Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/track-bus.php">Track Bus</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/profile.php">Profile</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <h2 class="page-title">
            <i class="fas fa-map-marker-alt"></i>
            Live Bus Tracking
        </h2>
        
        <button class="btn refresh-btn" onclick="loadBusLocations()">
            <i class="fas fa-sync-alt"></i> Refresh Locations
        </button>
        
        <div id="map"></div>
        
        <div class="card">
            <h3><i class="fas fa-info-circle"></i> Bus Information</h3>
            <div id="selected-bus-info" class="info-panel">
                <p>Select a bus from the list or click on a bus marker on the map to see details</p>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-bus"></i> All Active Buses</h3>
            <div class="bus-list" id="buses-container">
                <?php if (count($all_buses) > 0): ?>
                    <?php foreach ($all_buses as $bus): ?>
                        <div class="bus-item" data-bus-id="<?php echo $bus['id']; ?>" 
                             data-lat="<?php echo $bus['latitude']; ?>" 
                             data-lng="<?php echo $bus['longitude']; ?>"
                             onclick="focusOnBus(this)">
                            <strong>Bus <?php echo htmlspecialchars($bus['bus_number']); ?></strong>
                            - <?php echo htmlspecialchars($bus['route_name']); ?>
                            <br>
                            <small>
                                <span class="status-online"></span>
                                Driver: <?php echo htmlspecialchars($bus['driver_name']); ?> | 
                                Last update: <?php echo $bus['last_update'] ? date('H:i:s', strtotime($bus['last_update'])) : 'Never'; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No active buses with location data available.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($route_id && count($buses) > 0): ?>
        <div class="card">
            <h3><i class="fas fa-route"></i> Your Route Buses (Route #<?php echo $route_id; ?>)</h3>
            <div class="bus-list" id="user-buses-container">
                <?php foreach ($buses as $bus): ?>
                    <div class="bus-item" data-bus-id="<?php echo $bus['id']; ?>" 
                         data-lat="<?php echo $bus['latitude']; ?>" 
                         data-lng="<?php echo $bus['longitude']; ?>"
                         onclick="focusOnBus(this)">
                        <strong>Bus <?php echo htmlspecialchars($bus['bus_number']); ?></strong>
                        - <?php echo htmlspecialchars($bus['route_name']); ?>
                        <br>
                        <small>
                            <span class="status-online"></span>
                            Driver: <?php echo htmlspecialchars($bus['driver_name']); ?> | 
                            Last update: <?php echo $bus['last_update'] ? date('H:i:s', strtotime($bus['last_update'])) : 'Never'; ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Load Leaflet CSS and JS only when needed -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/leaflet/css/leaflet.css" />
    <script src="<?php echo BASE_URL; ?>assets/vendor/leaflet/js/leaflet.js"></script>
    <!-- Offline Map Support -->
    <script src="<?php echo BASE_URL; ?>assets/vendor/offline-map/offline-tiles.js"></script>
    
    <script>
        let map;
        let markers = {};
        const defaultCenter = [16.23812030, 80.56098230];
        const defaultZoom = 15;

        // Initialize map with enhanced offline support
        function initMap() {
            console.log("🌍 Initializing map with offline support...");
            
            // Use the enhanced offline map initialization
            map = initMapWithOfflineSupport('map', defaultCenter, defaultZoom);
            
            // Load initial bus locations
            loadBusLocations();
            
            // Refresh every 30 seconds
            setInterval(loadBusLocations, 30000);
        }

        // Load bus locations from API
        function loadBusLocations() {
            console.log("🔄 Loading bus locations from API...");
            
            const refreshBtn = document.querySelector('.refresh-btn');
            const originalText = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<span class="loading"></span> Loading...';
            
            fetch('<?php echo BASE_URL; ?>api/get-bus-location.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateMap(data.buses);
                    } else {
                        console.error("❌ API error:", data.message);
                    }
                    refreshBtn.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('❌ Error loading bus locations:', error);
                    refreshBtn.innerHTML = originalText;
                });
        }

        // Update map with bus markers
        function updateMap(buses) {
            // Clear existing markers
            Object.values(markers).forEach(marker => map.removeLayer(marker));
            markers = {};

            if (buses.length === 0) {
                document.getElementById('selected-bus-info').innerHTML = 
                    "<p>No active buses found with location data.</p>";
                return;
            }

            buses.forEach(bus => {
                if (bus.latitude && bus.longitude) {
                    const position = [parseFloat(bus.latitude), parseFloat(bus.longitude)];
                    
                    // Create custom bus icon
                    const busIcon = L.divIcon({
                        className: 'bus-marker',
                        html: `<div style="background: #e74c3c; color: white; padding: 8px; border-radius: 50%; 
                                border: 3px solid white; text-align: center; width: 40px; height: 40px; 
                                line-height: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.3); font-size: 18px;">🚌</div>`,
                        iconSize: [40, 40],
                        iconAnchor: [20, 20]
                    });

                    const marker = L.marker(position, {icon: busIcon}).addTo(map);
                    
                    // Add popup with bus info
                    const popupContent = `
                        <div style="min-width: 200px;">
                            <h4>Bus ${bus.bus_number}</h4>
                            <p><strong>Driver:</strong> ${bus.driver_name || 'N/A'}</p>
                            <p><strong>Route:</strong> ${bus.route_name || 'N/A'}</p>
                            <p><strong>Speed:</strong> ${bus.speed || 0} km/h</p>
                            <p><strong>Last update:</strong> ${bus.last_update || 'N/A'}</p>
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent);
                    
                    // Store marker reference
                    markers[bus.id] = marker;
                    
                    // Add click event to show bus info
                    marker.on('click', function() {
                        showBusInfo(bus);
                    });
                }
            });
        }

        // Show bus information in the info panel
        function showBusInfo(bus) {
            const infoDiv = document.getElementById('selected-bus-info');
            infoDiv.innerHTML = `
                <h4>Bus ${bus.bus_number}</h4>
                <p><i class="fas fa-user"></i> <strong>Driver:</strong> ${bus.driver_name || 'N/A'}</p>
                <p><i class="fas fa-route"></i> <strong>Route:</strong> ${bus.route_name || 'N/A'}</p>
                <p><i class="fas fa-tachometer-alt"></i> <strong>Speed:</strong> ${bus.speed || 0} km/h</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${bus.latitude}, ${bus.longitude}</p>
                <p><i class="fas fa-clock"></i> <strong>Last update:</strong> ${bus.last_update || 'N/A'}</p>
            `;
        }

        // Focus on a specific bus
        function focusOnBus(element) {
            const busId = element.getAttribute('data-bus-id');
            const lat = parseFloat(element.getAttribute('data-lat'));
            const lng = parseFloat(element.getAttribute('data-lng'));
            
            if (markers[busId]) {
                map.setView([lat, lng], 16);
                markers[busId].openPopup();
                
                // Highlight selected bus in list
                document.querySelectorAll('.bus-item').forEach(item => {
                    item.classList.remove('active');
                });
                element.classList.add('active');
                
                // Show bus info
                const busData = <?php echo json_encode($all_buses); ?>.find(bus => bus.id == busId);
                if (busData) showBusInfo(busData);
            }
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
    </script>
    
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