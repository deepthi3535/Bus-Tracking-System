<?php
// api/traccar.php - Updated for React Native location format
require_once '../includes/paths.php';
require_once '../includes/database.php';

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/../logs';
if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start logging
$debug_log = $logs_dir . '/traccar_debug.log';
file_put_contents($debug_log, 
    "=== " . date('Y-m-d H:i:s') . " ===========================================\n", 
    FILE_APPEND
);

// Log basic request info
file_put_contents($debug_log, 
    "🌐 REQUEST RECEIVED\n", 
    FILE_APPEND
);
file_put_contents($debug_log, 
    "Method: " . $_SERVER['REQUEST_METHOD'] . "\n", 
    FILE_APPEND
);
file_put_contents($debug_log, 
    "URL: " . $_SERVER['REQUEST_URI'] . "\n", 
    FILE_APPEND
);

// Log all headers
file_put_contents($debug_log, 
    "📨 HEADERS:\n" . print_r(getallheaders(), true) . "\n", 
    FILE_APPEND
);

// Get the raw POST data
$input = file_get_contents('php://input');
file_put_contents($debug_log, 
    "📦 RAW INPUT (" . strlen($input) . " bytes):\n" . $input . "\n", 
    FILE_APPEND
);

// Log GET parameters if any
if (!empty($_GET)) {
    file_put_contents($debug_log, 
        "🔍 GET PARAMETERS:\n" . print_r($_GET, true) . "\n", 
        FILE_APPEND
    );
}

// Log POST parameters if any (for form data)
if (!empty($_POST)) {
    file_put_contents($debug_log, 
        "📝 POST PARAMETERS:\n" . print_r($_POST, true) . "\n", 
        FILE_APPEND
    );
}

// Try to decode JSON
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    file_put_contents($debug_log, 
        "❌ JSON DECODE FAILED: " . json_last_error_msg() . "\n", 
        FILE_APPEND
    );
    
    // If JSON decode fails, try application/x-www-form-urlencoded
    parse_str($input, $data);
    file_put_contents($debug_log, 
        "🔄 Trying form data parsing\n", 
        FILE_APPEND
    );
}

// Log parsed data
file_put_contents($debug_log, 
    "📊 PARSED DATA:\n" . print_r($data, true) . "\n", 
    FILE_APPEND
);

// Extract location data
$latitude = null;
$longitude = null;
$deviceId = null;
$speed = 0;
$distance = 0;
$totalDistance = 0;

// Check all possible field names
$possible_fields = [
    'lat' => ['lat', 'latitude', 'LAT', 'LATITUDE', 'x', 'X'],
    'lon' => ['lon', 'lng', 'long', 'longitude', 'LON', 'LNG', 'LONG', 'LONGITUDE', 'y', 'Y'],
    'id' => ['id', 'deviceId', 'deviceid', 'DEVICEID', 'device', 'DEVICE', 'imei', 'IMEI'],
    'speed' => ['speed', 'velocity', 'SPEED', 'VELOCITY'],
    'distance' => ['distance', 'odometer', 'DISTANCE', 'ODOMETER'],
    'totalDistance' => ['totalDistance', 'total_distance', 'TOTALDISTANCE']
];

// Check for React Native location format (NEW - handles location.coords)
if (isset($data['location']) && is_array($data['location'])) {
    file_put_contents($debug_log, 
        "🔍 Found React Native 'location' object\n", 
        FILE_APPEND
    );
    
    $location = $data['location'];
    
    // Check for coords object inside location
    if (isset($location['coords']) && is_array($location['coords'])) {
        $coords = $location['coords'];
        file_put_contents($debug_log, 
            "🔍 Found nested 'coords' object\n", 
            FILE_APPEND
        );
        
        // Extract latitude from coords
        foreach ($possible_fields['lat'] as $field) {
            if (isset($coords[$field])) {
                $latitude = (float)$coords[$field];
                file_put_contents($debug_log, 
                    "✅ Found latitude in coords['$field']: $latitude\n", 
                    FILE_APPEND
                );
                break;
            }
        }
        
        // Extract longitude from coords
        foreach ($possible_fields['lon'] as $field) {
            if (isset($coords[$field])) {
                $longitude = (float)$coords[$field];
                file_put_contents($debug_log, 
                    "✅ Found longitude in coords['$field']: $longitude\n", 
                    FILE_APPEND
                );
                break;
            }
        }
        
        // Extract speed from coords
        foreach ($possible_fields['speed'] as $field) {
            if (isset($coords[$field])) {
                $speed = (float)$coords[$field];
                file_put_contents($debug_log, 
                    "✅ Found speed in coords['$field']: $speed\n", 
                    FILE_APPEND
                );
                break;
            }
        }
    }
    
    // Extract odometer (distance) from location
    if (isset($location['odometer'])) {
        $distance = (float)$location['odometer'];
        file_put_contents($debug_log, 
            "✅ Found odometer: $distance\n", 
            FILE_APPEND
        );
    }
}

// Check for device_id at root level (React Native format)
if (isset($data['device_id']) && empty($deviceId)) {
    $deviceId = $data['device_id'];
    file_put_contents($debug_log, 
        "✅ Found device_id at root level: $deviceId\n", 
        FILE_APPEND
    );
}

// Standard Traccar format handling (existing code)
foreach ($possible_fields['lat'] as $field) {
    if (isset($data[$field]) && $latitude === null) {
        $latitude = (float)$data[$field];
        file_put_contents($debug_log, 
            "✅ Found latitude in field '$field': $latitude\n", 
            FILE_APPEND
        );
        break;
    }
}

foreach ($possible_fields['lon'] as $field) {
    if (isset($data[$field]) && $longitude === null) {
        $longitude = (float)$data[$field];
        file_put_contents($debug_log, 
            "✅ Found longitude in field '$field': $longitude\n", 
            FILE_APPEND
        );
        break;
    }
}

// Extract speed data
foreach ($possible_fields['speed'] as $field) {
    if (isset($data[$field]) && $speed === 0) {
        $speed = (float)$data[$field];
        file_put_contents($debug_log, 
            "✅ Found speed in field '$field': $speed\n", 
            FILE_APPEND
        );
        break;
    }
}

// Extract distance data
foreach ($possible_fields['distance'] as $field) {
    if (isset($data[$field]) && $distance === 0) {
        $distance = (float)$data[$field];
        file_put_contents($debug_log, 
            "✅ Found distance in field '$field': $distance\n", 
            FILE_APPEND
        );
        break;
    }
}

// Extract total distance data
foreach ($possible_fields['totalDistance'] as $field) {
    if (isset($data[$field])) {
        $totalDistance = (float)$data[$field];
        file_put_contents($debug_log, 
            "✅ Found totalDistance in field '$field': $totalDistance\n", 
            FILE_APPEND
        );
        break;
    }
}

// Fixed Device ID handling with array support
foreach ($possible_fields['id'] as $field) {
    if (isset($data[$field]) && empty($deviceId)) {
        $deviceId = $data[$field];
        
        // Handle the case where deviceId might be an array
        if (is_array($deviceId)) {
            if (isset($deviceId['id'])) {
                $deviceId = $deviceId['id']; // Extract from array
                file_put_contents($debug_log, 
                    "✅ Found device ID in field '$field' (extracted from array): $deviceId\n", 
                    FILE_APPEND
                );
            } else {
                $deviceId = json_encode($deviceId); // Convert array to string
                file_put_contents($debug_log, 
                    "⚠ Device ID in field '$field' is array: " . $deviceId . "\n", 
                    FILE_APPEND
                );
            }
        } else {
            file_put_contents($debug_log, 
                "✅ Found device ID in field '$field': $deviceId\n", 
                FILE_APPEND
            );
        }
        break;
    }
}

// Check for nested device object
if (isset($data['device']) && is_array($data['device']) && empty($deviceId)) {
    file_put_contents($debug_log, 
        "🔍 Found nested 'device' object\n", 
        FILE_APPEND
    );
    
    $deviceObj = $data['device'];
    if (isset($deviceObj['id'])) {
        $deviceId = $deviceObj['id'];
        file_put_contents($debug_log, 
            "✅ Found device ID in device['id']: $deviceId\n", 
            FILE_APPEND
        );
    }
}

// Check if data is in position object (some Traccar versions)
if (isset($data['position']) && is_array($data['position'])) {
    file_put_contents($debug_log, 
        "🔍 Found 'position' object\n", 
        FILE_APPEND
    );
    
    $position = $data['position'];
    foreach ($possible_fields['lat'] as $field) {
        if (isset($position[$field]) && $latitude === null) {
            $latitude = (float)$position[$field];
            file_put_contents($debug_log, 
                "✅ Found latitude in position['$field']: $latitude\n", 
                FILE_APPEND
            );
            break;
        }
    }
    
    foreach ($possible_fields['lon'] as $field) {
        if (isset($position[$field]) && $longitude === null) {
            $longitude = (float)$position[$field];
            file_put_contents($debug_log, 
                "✅ Found longitude in position['$field']: $longitude\n", 
                FILE_APPEND
            );
            break;
        }
    }
}

// Validate required data
if ($latitude === null || $longitude === null || empty($deviceId)) {
    $error_msg = 'Missing location data or device ID. Available fields: ' . 
                 implode(', ', array_keys($data ?: [])) . 
                 '. Raw input: ' . substr($input, 0, 200);
    
    file_put_contents($debug_log, 
        "❌ ERROR: $error_msg\n", 
        FILE_APPEND
    );
    
    $response = ['success' => false, 'message' => $error_msg];
    
} else {
    // Success! We found location data
    file_put_contents($debug_log, 
        "🎉 SUCCESS: Location data received - Lat: $latitude, Lon: $longitude, Device: $deviceId, Speed: $speed, Distance: $distance, TotalDistance: $totalDistance\n", 
        FILE_APPEND
    );
    
    // Save to database - UPDATED SECTION WITH PROPER ERROR HANDLING
    $db_success = false;
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Database connection failed - getConnection() returned false");
        }
        
        // First, get bus_id from device_id from bus_devices table
        $query = "SELECT bus_id FROM bus_devices WHERE device_id = :device_id AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':device_id', $deviceId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $bus_id = $result['bus_id'];
            
            // Save to bus_locations table with distance columns
            $query = "INSERT INTO bus_locations 
                      (bus_id, latitude, longitude, speed, bearing, accuracy, altitude, distance_km, total_distance_km, timestamp) 
                      VALUES (:bus_id, :lat, :lng, :speed, 0, 0, 0, :distance, :total_distance, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':bus_id', $bus_id);
            $stmt->bindParam(':lat', $latitude);
            $stmt->bindParam(':lng', $longitude);
            $stmt->bindParam(':speed', $speed);
            $stmt->bindParam(':distance', $distance);
            $stmt->bindParam(':total_distance', $totalDistance);
            
            if ($stmt->execute()) {
                $db_success = true;
                file_put_contents($debug_log, 
                    "💾 Saved to database: Bus $bus_id at $latitude,$longitude, Speed: $speed km/h, Distance: $distance km, Total: $totalDistance km\n", 
                    FILE_APPEND
                );
            } else {
                $errorInfo = $stmt->errorInfo();
                file_put_contents($debug_log, 
                    "❌ Database insert failed: " . implode(", ", $errorInfo) . "\n", 
                    FILE_APPEND
                );
            }
        } else {
            file_put_contents($debug_log, 
                "❌ Device not registered or inactive: $deviceId\n", 
                FILE_APPEND
            );
        }
    } catch (PDOException $e) {
        file_put_contents($debug_log, 
            "❌ PDO Database error: " . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        if (isset($e->errorInfo)) {
            file_put_contents($debug_log, 
                "❌ PDO Error Info: " . implode(", ", $e->errorInfo) . "\n", 
                FILE_APPEND
            );
        }
    } catch (Exception $e) {
        file_put_contents($debug_log, 
            "❌ General Database error: " . $e->getMessage() . "\n", 
            FILE_APPEND
        );
    }
    
    if ($db_success) {
        $response = [
            'success' => true, 
            'message' => 'Location received and saved successfully',
            'data' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'deviceId' => $deviceId,
                'speed' => $speed,
                'distance' => $distance,
                'totalDistance' => $totalDistance,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    } else {
        $response = [
            'success' => false, 
            'message' => 'Location received but database save failed',
            'data' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'deviceId' => $deviceId,
                'speed' => $speed,
                'distance' => $distance,
                'totalDistance' => $totalDistance
            ]
        ];
    }
    
    // Also log to a separate data file
    file_put_contents($logs_dir . '/traccar_data.log', 
        date('Y-m-d H:i:s') . " - $deviceId - $latitude,$longitude - Speed: $speed km/h - Distance: $distance km - Total: $totalDistance km\n", 
        FILE_APPEND
    );
}

// Log the response
file_put_contents($debug_log, 
    "📤 RESPONSE:\n" . json_encode($response, JSON_PRETTY_PRINT) . "\n", 
    FILE_APPEND
);

file_put_contents($debug_log, 
    "--- END OF REQUEST --------------------------------------------\n\n", 
    FILE_APPEND
);

// Send response
echo json_encode($response);
?>