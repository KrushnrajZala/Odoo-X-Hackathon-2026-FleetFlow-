<?php
header('Content-Type: application/json');
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_id = intval($_POST['trip_id']);
    $driver_id = intval($_POST['driver_id']);
    
    // Update trip
    $sql = "UPDATE trips SET driver_id = $driver_id, status = 'accepted' WHERE id = $trip_id AND status = 'pending'";
    
    if ($conn->query($sql) && $conn->affected_rows > 0) {
        // Update driver status
        $conn->query("UPDATE driver_details SET current_status = 'on_trip' WHERE user_id = $driver_id");
        
        echo json_encode(['success' => true, 'message' => 'Ride accepted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'This ride is no longer available']);
    }
}
?>