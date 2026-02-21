<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for CORS and JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration with absolute path
require_once dirname(__DIR__) . '/includes/config.php';

// Create logs directory if it doesn't exist
$log_dir = dirname(__DIR__) . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Function to log messages
function logMessage($message, $data = null) {
    global $log_dir;
    $log_file = $log_dir . '/api_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message";
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_entry .= " - Data: " . json_encode($data);
        } else {
            $log_entry .= " - Data: " . $data;
        }
    }
    $log_entry .= PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

logMessage("API called with method: " . $_SERVER['REQUEST_METHOD']);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    logMessage("API failed: User not logged in");
    echo json_encode([
        'success' => false,
        'error' => 'auth_required',
        'message' => 'You must be logged in to book a ride. Please login and try again.'
    ]);
    exit();
}

$passenger_id = intval($_SESSION['user_id']);
logMessage("User ID: $passenger_id");

// Get POST data
$input = file_get_contents('php://input');
logMessage("Raw input received", substr($input, 0, 500)); // Log first 500 chars only

if (empty($input)) {
    logMessage("API failed: Empty request body");
    echo json_encode([
        'success' => false,
        'error' => 'empty_request',
        'message' => 'No data received. Please check your request.'
    ]);
    exit();
}

$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    logMessage("API failed: Invalid JSON - " . json_last_error_msg());
    echo json_encode([
        'success' => false,
        'error' => 'invalid_json',
        'message' => 'Invalid data format: ' . json_last_error_msg()
    ]);
    exit();
}

logMessage("Decoded data keys: " . implode(', ', array_keys($data)));

// Check if this is a test request
if (isset($data['test']) && $data['test'] === true) {
    logMessage("Test request received");
    echo json_encode([
        'success' => true,
        'message' => 'API is working correctly',
        'data_received' => $data,
        'session' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null
        ],
        'server' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'none'
        ]
    ]);
    exit();
}

// ==================== ANTI-DUPLICATE MEASURES ====================

// Check 1: Session-based time check
if (!isset($_SESSION['last_booking_time'])) {
    $_SESSION['last_booking_time'] = 0;
}

$current_time = time();
$time_since_last_booking = $current_time - $_SESSION['last_booking_time'];

// Block if less than 3 seconds since last booking
if ($time_since_last_booking < 3) {
    logMessage("Duplicate booking blocked - too fast: {$time_since_last_booking}s");
    echo json_encode([
        'success' => false,
        'error' => 'duplicate_request',
        'message' => 'Please wait a moment before booking again.'
    ]);
    exit();
}

// Check 2: Request ID duplication check
if (isset($data['request_id']) && !empty($data['request_id'])) {
    $request_id = mysqli_real_escape_string($conn, $data['request_id']);
    
    // Check if this request_id was already processed in the last minute
    $check_sql = "SELECT id FROM booking_log WHERE request_id = '$request_id' AND created_at > NOW() - INTERVAL 1 MINUTE";
    $check_result = mysqli_query($conn, $check_sql);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        logMessage("Duplicate booking with request_id: " . $request_id);
        
        // Try to find if a trip was created with this request_id
        $trip_check = mysqli_query($conn, "SELECT id FROM trips WHERE request_id = '$request_id'");
        if ($trip_check && mysqli_num_rows($trip_check) > 0) {
            $trip = mysqli_fetch_assoc($trip_check);
            echo json_encode([
                'success' => true,
                'message' => 'Ride already booked!',
                'trip_id' => $trip['id'],
                'duplicate' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'duplicate_request',
                'message' => 'This booking request has already been processed.'
            ]);
        }
        exit();
    }
}

// Check 3: Booking token check
if (isset($data['booking_token']) && !empty($data['booking_token'])) {
    $booking_token = mysqli_real_escape_string($conn, $data['booking_token']);
    
    if (!isset($_SESSION['booking_token']) || $_SESSION['booking_token'] !== $booking_token) {
        logMessage("Invalid booking token");
        echo json_encode([
            'success' => false,
            'error' => 'invalid_token',
            'message' => 'Invalid booking session. Please refresh the page.'
        ]);
        exit();
    }
    
    // Invalidate the token after use (prevents reuse)
    $_SESSION['booking_token'] = 'used_' . bin2hex(random_bytes(8));
}

// Check 4: Check for identical booking in last minute
if (isset($data['pickup_lat']) && isset($data['dropoff_lat'])) {
    $pickup_lat = floatval($data['pickup_lat']);
    $pickup_lng = floatval($data['pickup_lng']);
    $dropoff_lat = floatval($data['dropoff_lat']);
    $dropoff_lng = floatval($data['dropoff_lng']);
    
    // Allow small margin of error for coordinates
    $tolerance = 0.0001;
    
    $duplicate_check = "SELECT id FROM trips 
                        WHERE passenger_id = $passenger_id 
                        AND ABS(pickup_lat - $pickup_lat) < $tolerance
                        AND ABS(pickup_lng - $pickup_lng) < $tolerance
                        AND ABS(dropoff_lat - $dropoff_lat) < $tolerance
                        AND ABS(dropoff_lng - $dropoff_lng) < $tolerance
                        AND created_at > NOW() - INTERVAL 1 MINUTE
                        AND status = 'pending'";
    
    $duplicate_result = mysqli_query($conn, $duplicate_check);
    
    if ($duplicate_result && mysqli_num_rows($duplicate_result) > 0) {
        $existing_trip = mysqli_fetch_assoc($duplicate_result);
        logMessage("Duplicate identical booking found", $existing_trip);
        
        echo json_encode([
            'success' => true,
            'message' => 'Ride already booked!',
            'trip_id' => $existing_trip['id'],
            'duplicate' => true
        ]);
        exit();
    }
}

// ==================== VALIDATION ====================

// Validate required fields for actual booking
$required_fields = ['pickup', 'dropoff', 'pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng', 'distance', 'fare'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    logMessage("API failed: Missing fields", $missing_fields);
    echo json_encode([
        'success' => false,
        'error' => 'missing_fields',
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
        'missing_fields' => $missing_fields
    ]);
    exit();
}

// Sanitize and validate data
$pickup = mysqli_real_escape_string($conn, trim($data['pickup']));
$dropoff = mysqli_real_escape_string($conn, trim($data['dropoff']));
$pickup_lat = floatval($data['pickup_lat']);
$pickup_lng = floatval($data['pickup_lng']);
$dropoff_lat = floatval($data['dropoff_lat']);
$dropoff_lng = floatval($data['dropoff_lng']);
$distance = floatval($data['distance']);
$fare = floatval($data['fare']);
$vehicle_type = isset($data['vehicle_type']) ? mysqli_real_escape_string($conn, $data['vehicle_type']) : 'car';
$notes = isset($data['notes']) ? mysqli_real_escape_string($conn, $data['notes']) : '';
$request_id = isset($data['request_id']) ? mysqli_real_escape_string($conn, $data['request_id']) : null;

// Validate coordinates
if ($pickup_lat < -90 || $pickup_lat > 90 || $pickup_lng < -180 || $pickup_lng > 180) {
    logMessage("API failed: Invalid pickup coordinates", ['lat' => $pickup_lat, 'lng' => $pickup_lng]);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_coordinates',
        'message' => 'Invalid pickup coordinates'
    ]);
    exit();
}

if ($dropoff_lat < -90 || $dropoff_lat > 90 || $dropoff_lng < -180 || $dropoff_lng > 180) {
    logMessage("API failed: Invalid dropoff coordinates", ['lat' => $dropoff_lat, 'lng' => $dropoff_lng]);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_coordinates',
        'message' => 'Invalid dropoff coordinates'
    ]);
    exit();
}

// Validate distance and fare
if ($distance <= 0 || $distance > 1000) {
    logMessage("API failed: Invalid distance", $distance);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_distance',
        'message' => 'Invalid distance. Distance must be between 0 and 1000 km.'
    ]);
    exit();
}

if ($fare <= 0 || $fare > 10000) {
    logMessage("API failed: Invalid fare", $fare);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_fare',
        'message' => 'Invalid fare amount. Fare must be between ₹1 and ₹10,000.'
    ]);
    exit();
}

logMessage("All validations passed. Inserting trip...");

// ==================== INSERT TRIP ====================

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // First, log this request to prevent duplicates
    if ($request_id) {
        $log_sql = "INSERT INTO booking_log (request_id, user_id, created_at) 
                    VALUES ('$request_id', $passenger_id, NOW())";
        if (!mysqli_query($conn, $log_sql)) {
            // If log fails, continue anyway but log the error
            logMessage("Warning: Could not insert booking log: " . mysqli_error($conn));
        }
    }
    
    // Insert trip
    $sql = "INSERT INTO trips (
        passenger_id, 
        pickup_location, 
        dropoff_location, 
        pickup_lat, 
        pickup_lng, 
        dropoff_lat, 
        dropoff_lng, 
        distance_km, 
        estimated_fare, 
        status, 
        payment_method, 
        payment_status,
        request_id,
        created_at
    ) VALUES (
        $passenger_id, 
        '$pickup', 
        '$dropoff', 
        $pickup_lat, 
        $pickup_lng, 
        $dropoff_lat, 
        $dropoff_lng, 
        $distance, 
        $fare, 
        'pending', 
        'cash', 
        'pending',
        " . ($request_id ? "'$request_id'" : "NULL") . ",
        NOW()
    )";

    logMessage("Executing SQL: " . $sql);

    if (!mysqli_query($conn, $sql)) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

    $trip_id = mysqli_insert_id($conn);
    logMessage("Trip inserted with ID: " . $trip_id);

    // Get the generated trip number
    $result = mysqli_query($conn, "SELECT trip_number FROM trips WHERE id = $trip_id");
    if (!$result) {
        throw new Exception("Failed to fetch trip number: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) == 0) {
        throw new Exception("Trip not found after insertion");
    }

    $trip = mysqli_fetch_assoc($result);
    
    // Update last booking time in session
    $_SESSION['last_booking_time'] = time();
    
    // Commit transaction
    mysqli_commit($conn);
    
    logMessage("Trip created successfully", [
        'trip_id' => $trip_id,
        'trip_number' => $trip['trip_number']
    ]);
    
    // Create notification for admins (optional)
    $admin_notification = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                          SELECT id, 'New Ride Booked', CONCAT('A new ride has been booked by passenger #', $passenger_id), 'info', NOW() 
                          FROM users WHERE role = 'admin'";
    mysqli_query($conn, $admin_notification);

    echo json_encode([
        'success' => true,
        'message' => 'Ride booked successfully! Finding nearby drivers...',
        'trip_id' => $trip_id,
        'trip_number' => $trip['trip_number']
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    logMessage("Error creating trip: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Failed to book ride: ' . $e->getMessage()
    ]);
}

// Close connection
mysqli_close($conn);
?>