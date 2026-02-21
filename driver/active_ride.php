<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('driver')) {
    redirect('../login.php');
}

$driver_id = $_SESSION['user_id'];
$page_title = 'Active Ride';
include '../includes/header.php';

// Get active ride
$active_ride = $conn->query("
    SELECT t.*, 
           u.full_name as passenger_name,
           u.phone as passenger_phone,
           v.vehicle_name,
           v.license_plate,
           v.vehicle_type
    FROM trips t
    JOIN users u ON t.passenger_id = u.id
    JOIN vehicles v ON t.vehicle_id = v.id
    WHERE t.driver_id = $driver_id 
    AND t.status IN ('accepted', 'started')
    ORDER BY t.created_at DESC
    LIMIT 1
")->fetch_assoc();

if (!$active_ride) {
    redirect('dashboard.php');
}

// Handle status updates
if (isset($_POST['update_status'])) {
    $new_status = sanitize($_POST['status']);
    $trip_id = $active_ride['id'];
    
    $conn->query("BEGIN");
    
    // Update trip status
    $update_sql = "UPDATE trips SET status = '$new_status'";
    
    if ($new_status == 'started') {
        $update_sql .= ", started_at = NOW()";
    } elseif ($new_status == 'completed') {
        $update_sql .= ", completed_at = NOW(), actual_fare = estimated_fare";
    }
    
    $update_sql .= " WHERE id = $trip_id";
    
    if ($conn->query($update_sql)) {
        // Update vehicle and driver status
        if ($new_status == 'started') {
            $conn->query("UPDATE vehicles SET status = 'on_trip' WHERE id = {$active_ride['vehicle_id']}");
            $conn->query("UPDATE driver_details SET current_status = 'on_trip' WHERE user_id = $driver_id");
        } elseif ($new_status == 'completed') {
            $conn->query("UPDATE vehicles SET status = 'available' WHERE id = {$active_ride['vehicle_id']}");
            $conn->query("UPDATE driver_details SET current_status = 'available' WHERE user_id = $driver_id");
            
            // Update vehicle odometer (simulated)
            $new_odometer = $active_ride['current_odometer'] + ($active_ride['distance_km'] * 1000);
            $conn->query("UPDATE vehicles SET current_odometer = $new_odometer WHERE id = {$active_ride['vehicle_id']}");
        }
        
        $conn->query("COMMIT");
        $success = "Trip status updated successfully!";
        
        if ($new_status == 'completed') {
            redirect('history.php');
        } else {
            redirect('active_ride.php');
        }
    } else {
        $conn->query("ROLLBACK");
        $error = "Error updating status: " . $conn->error;
    }
}

// Handle location update
if (isset($_POST['update_location'])) {
    $lat = floatval($_POST['lat']);
    $lng = floatval($_POST['lng']);
    
    $check = $conn->query("SELECT id FROM trip_tracking WHERE driver_id = $driver_id");
    
    if ($check->num_rows > 0) {
        $conn->query("UPDATE trip_tracking SET driver_lat = $lat, driver_lng = $lng WHERE driver_id = $driver_id");
    } else {
        $conn->query("INSERT INTO trip_tracking (driver_id, trip_id, driver_lat, driver_lng) 
                     VALUES ($driver_id, {$active_ride['id']}, $lat, $lng)");
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-road"></i> Active Ride #<?php echo $active_ride['id']; ?></h1>
        <div class="btn-group">
            <?php if($active_ride['status'] == 'accepted'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="status" value="started">
                    <button type="submit" name="update_status" class="btn btn-success">
                        <i class="fas fa-play"></i> Start Trip
                    </button>
                </form>
            <?php elseif($active_ride['status'] == 'started'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" name="update_status" class="btn btn-success">
                        <i class="fas fa-check"></i> Complete Trip
                    </button>
                </form>
            <?php endif; ?>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="status" value="cancelled">
                <button type="submit" name="update_status" class="btn btn-danger">
                    <i class="fas fa-times"></i> Cancel Trip
                </button>
            </form>
        </div>
    </div>

    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- Trip Details Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Trip Details</h3>
                <span class="status-pill status-<?php echo $active_ride['status']; ?>">
                    <?php echo ucfirst($active_ride['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Passenger:</label>
                        <strong><?php echo $active_ride['passenger_name']; ?></strong>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <a href="tel:<?php echo $active_ride['passenger_phone']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-phone"></i> Call Passenger
                        </a>
                    </div>
                    <div class="info-item">
                        <label>Vehicle:</label>
                        <strong><?php echo $active_ride['vehicle_name']; ?> (<?php echo $active_ride['license_plate']; ?>)</strong>
                    </div>
                    <div class="info-item">
                        <label>Pickup Location:</label>
                        <p><?php echo $active_ride['pickup_location']; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Dropoff Location:</label>
                        <p><?php echo $active_ride['dropoff_location']; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Distance:</label>
                        <strong><?php echo number_format($active_ride['distance_km'], 1); ?> km</strong>
                    </div>
                    <div class="info-item">
                        <label>Estimated Fare:</label>
                        <strong class="text-success">₹<?php echo number_format($active_ride['estimated_fare'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-map"></i> Navigation</h3>
            </div>
            <div class="card-body">
                <div class="btn-group" style="width: 100%;">
                    <a href="https://www.google.com/maps/dir/?api=1&origin=<?php echo $active_ride['pickup_lat']; ?>,<?php echo $active_ride['pickup_lng']; ?>&destination=<?php echo $active_ride['dropoff_lat']; ?>,<?php echo $active_ride['dropoff_lng']; ?>" 
                       target="_blank" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-directions"></i> Open in Google Maps
                    </a>
                </div>
                
                <div style="margin-top: 1rem;">
                    <h4>Quick Actions:</h4>
                    <div class="btn-group" style="width: 100%;">
                        <button onclick="copyAddress('pickup')" class="btn btn-sm btn-info">
                            <i class="fas fa-copy"></i> Copy Pickup
                        </button>
                        <button onclick="copyAddress('dropoff')" class="btn btn-sm btn-info">
                            <i class="fas fa-copy"></i> Copy Dropoff
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Card -->
        <div class="card" style="grid-column: 1/-1;">
            <div class="card-header">
                <h3><i class="fas fa-map-marked-alt"></i> Live Tracking</h3>
                <button onclick="centerMap()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-crosshairs"></i> Center Map
                </button>
            </div>
            <div class="card-body">
                <div id="map"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
<script>
// Initialize map
var map = L.map('map').setView([<?php echo $active_ride['pickup_lat']; ?>, <?php echo $active_ride['pickup_lng']; ?>], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Add markers
var pickupMarker = L.marker([<?php echo $active_ride['pickup_lat']; ?>, <?php echo $active_ride['pickup_lng']; ?>])
    .addTo(map)
    .bindPopup('Pickup Location')
    .openPopup();

var dropoffMarker = L.marker([<?php echo $active_ride['dropoff_lat']; ?>, <?php echo $active_ride['dropoff_lng']; ?>])
    .addTo(map)
    .bindPopup('Dropoff Location');

// Add route
L.Routing.control({
    waypoints: [
        L.latLng(<?php echo $active_ride['pickup_lat']; ?>, <?php echo $active_ride['pickup_lng']; ?>),
        L.latLng(<?php echo $active_ride['dropoff_lat']; ?>, <?php echo $active_ride['dropoff_lng']; ?>)
    ],
    routeWhileDragging: false,
    addWaypoints: false,
    draggableWaypoints: false,
    fitSelectedRoutes: true,
    showAlternatives: false
}).addTo(map);

// Driver marker (simulated)
var driverMarker = L.marker([<?php echo $active_ride['pickup_lat']; ?>, <?php echo $active_ride['pickup_lng']; ?>], {
    icon: L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    })
}).addTo(map).bindPopup('Your Location');

// Simulate driver movement (for demo purposes)
var step = 0;
var totalSteps = 100;
var startLat = <?php echo $active_ride['pickup_lat']; ?>;
var startLng = <?php echo $active_ride['pickup_lng']; ?>;
var endLat = <?php echo $active_ride['dropoff_lat']; ?>;
var endLng = <?php echo $active_ride['dropoff_lng']; ?>;

function updateDriverPosition() {
    if (step <= totalSteps) {
        var lat = startLat + (endLat - startLat) * (step / totalSteps);
        var lng = startLng + (endLng - startLng) * (step / totalSteps);
        
        driverMarker.setLatLng([lat, lng]);
        
        // Send location to server
        fetch('../api/update_location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                driver_id: <?php echo $driver_id; ?>,
                trip_id: <?php echo $active_ride['id']; ?>,
                lat: lat,
                lng: lng
            })
        });
        
        step++;
    }
}

// Update position every 3 seconds if trip is started
<?php if($active_ride['status'] == 'started'): ?>
setInterval(updateDriverPosition, 3000);
<?php endif; ?>

// Center map on driver
function centerMap() {
    map.setView(driverMarker.getLatLng(), 15);
}

// Copy address functions
function copyAddress(type) {
    var address = type === 'pickup' 
        ? '<?php echo addslashes($active_ride['pickup_location']); ?>'
        : '<?php echo addslashes($active_ride['dropoff_location']); ?>';
    
    navigator.clipboard.writeText(address).then(function() {
        alert('Address copied to clipboard!');
    });
}
</script>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-item label {
    display: block;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.3rem;
}

.info-item strong {
    font-size: 1.1rem;
    color: var(--dark-color);
}

.info-item p {
    margin: 0;
    font-size: 0.9rem;
}
</style>

<?php include '../includes/footer.php'; ?>