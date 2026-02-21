<?php
header('Content-Type: application/json');
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $lat = floatval($_GET['lat'] ?? 0);
    $lng = floatval($_GET['lng'] ?? 0);
    $radius = floatval($_GET['radius'] ?? 5); // Radius in km
    
    if (!$lat || !$lng) {
        echo json_encode(['success' => false, 'message' => 'Location required']);
        exit();
    }
    
    // Find nearby available drivers using Haversine formula
    $sql = "SELECT 
                u.id,
                u.full_name,
                u.phone,
                d.vehicle_type,
                d.current_status,
                d.safety_score,
                (6371 * acos(cos(radians($lat)) * cos(radians(tt.driver_lat)) * 
                cos(radians(tt.driver_lng) - radians($lng)) + 
                sin(radians($lat)) * sin(radians(tt.driver_lat)))) AS distance
            FROM users u
            JOIN driver_details d ON u.id = d.user_id
            LEFT JOIN trip_tracking tt ON u.id = tt.driver_id
            WHERE d.current_status = 'available'
            HAVING distance < $radius
            ORDER BY distance
            LIMIT 20";
    
    $result = $conn->query($sql);
    $drivers = [];
    
    while ($row = $result->fetch_assoc()) {
        $drivers[] = [
            'id' => $row['id'],
            'name' => $row['full_name'],
            'phone' => $row['phone'],
            'vehicle_type' => $row['vehicle_type'],
            'distance' => round($row['distance'], 2),
            'safety_score' => $row['safety_score']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'drivers' => $drivers,
        'count' => count($drivers)
    ]);
}
?>