<?php
header('Content-Type: application/json');
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $driver_id = intval($data['driver_id'] ?? 0);
    $trip_id = intval($data['trip_id'] ?? 0);
    $lat = floatval($data['lat'] ?? 0);
    $lng = floatval($data['lng'] ?? 0);
    
    if (!$driver_id || !$lat || !$lng) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit();
    }
    
    // Check if tracking record exists
    $check = $conn->query("SELECT id FROM trip_tracking WHERE driver_id = $driver_id");
    
    if ($check->num_rows > 0) {
        // Update existing
        $sql = "UPDATE trip_tracking SET driver_lat = $lat, driver_lng = $lng, updated_at = NOW() 
                WHERE driver_id = $driver_id";
    } else {
        // Insert new
        $sql = "INSERT INTO trip_tracking (driver_id, trip_id, driver_lat, driver_lng) 
                VALUES ($driver_id, $trip_id, $lat, $lng)";
    }
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Location updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
}

// GET request to get driver location
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $driver_id = intval($_GET['driver_id'] ?? 0);
    $trip_id = intval($_GET['trip_id'] ?? 0);
    
    if (!$driver_id && !$trip_id) {
        echo json_encode(['success' => false, 'message' => 'Driver ID or Trip ID required']);
        exit();
    }
    
    if ($trip_id) {
        // Get driver from trip
        $trip = $conn->query("SELECT driver_id FROM trips WHERE id = $trip_id")->fetch_assoc();
        $driver_id = $trip['driver_id'] ?? 0;
    }
    
    $location = $conn->query("SELECT driver_lat, driver_lng, updated_at 
                              FROM trip_tracking 
                              WHERE driver_id = $driver_id 
                              ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
    
    if ($location) {
        echo json_encode([
            'success' => true,
            'lat' => $location['driver_lat'],
            'lng' => $location['driver_lng'],
            'updated_at' => $location['updated_at']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Location not found']);
    }
}
?>