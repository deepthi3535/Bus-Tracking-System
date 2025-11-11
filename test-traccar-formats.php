<?php
// Test different data formats that Traccar might send
header('Content-Type: application/json');

$test_url = 'http://localhost/college-bus-tracking/api/traccar.php';
$tests = [
    [
        'name' => 'JSON with lat/lon',
        'data' => ['lat' => 12.9716, 'lon' => 77.5946, 'id' => 'test-device-001']
    ],
    [
        'name' => 'JSON with latitude/longitude',
        'data' => ['latitude' => 12.9716, 'longitude' => 77.5946, 'deviceId' => 'test-device-002']
    ],
    [
        'name' => 'Form data with lat/lng',
        'data' => 'lat=12.9716&lng=77.5946&id=test-device-003'
    ],
    [
        'name' => 'Nested location object',
        'data' => ['location' => ['lat' => 12.9716, 'lng' => 77.5946], 'device' => ['id' => 'test-device-004']]
    ]
];

$results = [];

foreach ($tests as $test) {
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    if (is_array($test['data'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $test['data']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    }
    
    $response = curl_exec($ch);
    $results[$test['name']] = [
        'request' => $test['data'],
        'response' => json_decode($response, true),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE)
    ];
    
    curl_close($ch);
}

echo json_encode(['tests' => $results], JSON_PRETTY_PRINT);
?>