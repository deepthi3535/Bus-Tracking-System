<?php
// Test script to simulate Traccar requests
header('Content-Type: text/plain');
echo "<h2>Traccar Request Simulator</h2>";

$url = 'http://192.168.0.150/college-bus-tracking/api/traccar.php';

// Different data formats that Traccar might use
$test_cases = [
    [
        'name' => 'Standard JSON with lat/lon',
        'data' => ['lat' => 12.9716, 'lon' => 77.5946, 'id' => 'test-device-001'],
        'headers' => ['Content-Type: application/json']
    ],
    [
        'name' => 'Standard JSON with latitude/longitude',
        'data' => ['latitude' => 12.9716, 'longitude' => 77.5946, 'deviceId' => 'test-device-002'],
        'headers' => ['Content-Type: application/json']
    ],
    [
        'name' => 'Form data format',
        'data' => 'lat=12.9716&lng=77.5946&id=test-device-003',
        'headers' => ['Content-Type: application/x-www-form-urlencoded']
    ],
    [
        'name' => 'Nested location object',
        'data' => ['location' => ['lat' => 12.9716, 'lng' => 77.5946], 'device' => ['id' => 'test-device-004']],
        'headers' => ['Content-Type: application/json']
    ],
    [
        'name' => 'Position object (some Traccar versions)',
        'data' => ['position' => ['latitude' => 12.9716, 'longitude' => 77.5946], 'device' => 'test-device-005'],
        'headers' => ['Content-Type: application/json']
    ]
];

foreach ($test_cases as $test) {
    echo "<h3>Testing: {$test['name']}</h3>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    if (is_array($test['data'])) {
        $post_data = json_encode($test['data']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $headers = array_merge($test['headers'], ['Content-Length: ' . strlen($post_data)]);
    } else {
        $post_data = $test['data'];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $headers = $test['headers'];
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $http_code</p>";
    echo "<p><strong>Request Data:</strong> " . (is_array($test['data']) ? json_encode($test['data']) : $test['data']) . "</p>";
    echo "<p><strong>Response:</strong> " . $response . "</p>";
    echo "<hr>";
}

echo "<p>Check <strong>logs/traccar_debug.log</strong> for detailed information about what data was received and how it was parsed.</p>";
?>