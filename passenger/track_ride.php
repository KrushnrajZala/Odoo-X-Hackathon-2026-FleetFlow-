<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('passenger')) {
    redirect('../login.php');
}

$trip_id = $_GET['id'] ?? 0;

// Get trip details
$trip = $conn->query("
    SELECT t.*, 
           d.full_name as driver_name,
           d.phone as driver_phone,
           v.vehicle_name,
           v.license_plate
    FROM trips t
    LEFT JOIN users d ON t.driver_id = d.id
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    WHERE t.id = $trip_id AND t.passenger_id = {$_SESSION['user_id']}
")->fetch_assoc();

if (!$trip) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Ride - FleetFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">🚀 FleetFlow Passenger</a>
        <div class="navbar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="book_ride.php">Book Ride</a>
            <a href="history.php">My Trips</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </nav>

    <div style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="margin-bottom: 1rem;">Ride Status: 
                <span class="status-pill status-<?php echo $trip['status']; ?>">
                    <?php echo ucfirst($trip['status']); ?>
                </span>
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <?php if($trip['driver_name']): ?>
                <div>
                    <strong>Driver:</strong> <?php echo $trip['driver_name']; ?>
                </div>
                <div>
                    <strong>Driver Phone:</strong> <a href="tel:<?php echo $trip['driver_phone']; ?>"><?php echo $trip['driver_phone']; ?></a>
                </div>
                <div>
                    <strong>Vehicle:</strong> <?php echo $trip['vehicle_name']; ?> (<?php echo $trip['license_plate']; ?>)
                </div>
                <?php endif; ?>
                <div>
                    <strong>Pickup:</strong> <?php echo substr($trip['pickup_location'], 0, 50); ?>
                </div>
                <div>
                    <strong>Dropoff:</strong> <?php echo substr($trip['dropoff_location'], 0, 50); ?>
                </div>
                <div>
                    <strong>Fare:</strong> ₹<?php echo $trip['estimated_fare']; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 1rem;">Live Tracking</h3>
            <div id="map"></div>
        </div>
    </div>

    <script>
    var map = L.map('map').setView([<?php echo $trip['pickup_lat'] ?: 28.6139; ?>, <?php echo $trip['pickup_lng'] ?: 77.2090; ?>], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add pickup marker
    L.marker([<?php echo $trip['pickup_lat']; ?>, <?php echo $trip['pickup_lng']; ?>])
        .addTo(map)
        .bindPopup('Pickup Location')
        .openPopup();
    
    // Add dropoff marker
    L.marker([<?php echo $trip['dropoff_lat']; ?>, <?php echo $trip['dropoff_lng']; ?>])
        .addTo(map)
        .bindPopup('Dropoff Location');
    
    // Driver marker (will update in real-time)
    var driverMarker = L.marker([<?php echo $trip['pickup_lat']; ?>, <?php echo $trip['pickup_lng']; ?>], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map).bindPopup('Driver Location');
    
    // Update driver location every 5 seconds
    function updateDriverLocation() {
        fetch(`../api/get_driver_location.php?trip_id=<?php echo $trip_id; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.lat && data.lng) {
                    driverMarker.setLatLng([data.lat, data.lng]);
                    map.panTo([data.lat, data.lng]);
                }
            });
    }
    
    <?php if($trip['status'] == 'accepted' || $trip['status'] == 'started'): ?>
    setInterval(updateDriverLocation, 5000);
    <?php endif; ?>
    </script>
</body>
</html>