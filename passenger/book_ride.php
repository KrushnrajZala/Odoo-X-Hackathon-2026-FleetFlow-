<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
include '../includes/config.php';

// Check if user is logged in and is passenger
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Please login to continue";
    redirect('../login.php');
}

if (!hasRole('passenger')) {
    $_SESSION['error_message'] = "Access denied. Passenger only area.";
    redirect('../index.php');
}

$page_title = 'Book a Ride';
include '../includes/header.php';

// Test database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get available vehicles for selection
$vehicles = $conn->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY vehicle_type");
if (!$vehicles) {
    error_log("Vehicle query failed: " . $conn->error);
}

// Get user's recent trips for quick rebook
$user_id = $_SESSION['user_id'];
$recent_trips = $conn->query("
    SELECT * FROM trips 
    WHERE passenger_id = $user_id 
    AND status = 'completed' 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Generate a unique booking token for this session
$booking_token = bin2hex(random_bytes(16));
$_SESSION['booking_token'] = $booking_token;
?>

<style>
/* Modern Booking Page Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #ff6b35 0%, #ff8c5a 100%);
}

.booking-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 2rem;
}

.booking-header {
    text-align: center;
    margin-bottom: 3rem;
    position: relative;
}

.booking-header h1 {
    font-size: 2.8rem;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.booking-header p {
    color: #666;
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
}

.booking-grid {
    display: grid;
    grid-template-columns: 1.2fr 1.8fr;
    gap: 2rem;
    margin-top: 2rem;
}

/* Form Card Styles */
.booking-form-card {
    background: white;
    border-radius: 30px;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    animation: slideInLeft 0.5s ease;
}

.booking-form-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 50px rgba(0,0,0,0.15);
}

.booking-form-card h2 {
    color: #333;
    margin-bottom: 2rem;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 1rem;
}

.booking-form-card h2 i {
    color: var(--primary-color);
    font-size: 2rem;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.8rem;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 0.6rem;
    color: #333;
    font-weight: 600;
    font-size: 0.95rem;
}

.form-group label i {
    color: var(--primary-color);
    margin-right: 0.5rem;
    width: 20px;
}

.form-control {
    width: 100%;
    padding: 1rem 1.2rem;
    border: 2px solid #e0e0e0;
    border-radius: 15px;
    font-size: 1rem;
    transition: all 0.3s;
    background: #f8f9fa;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    background: white;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

/* Location Input with Icons */
.location-input-group {
    position: relative;
}

.location-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    z-index: 1;
}

.location-input {
    padding-left: 3rem !important;
}

.use-current-location {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s;
    z-index: 2;
}

.use-current-location:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* Vehicle Options */
.vehicle-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 0.5rem;
}

.vehicle-option {
    border: 2px solid #e0e0e0;
    border-radius: 20px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
    position: relative;
    overflow: hidden;
}

.vehicle-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    transform: scaleX(0);
    transition: transform 0.3s;
}

.vehicle-option:hover {
    border-color: var(--primary-color);
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
}

.vehicle-option:hover::before {
    transform: scaleX(1);
}

.vehicle-option.selected {
    border-color: var(--primary-color);
    background: linear-gradient(135deg, #f5f7ff 0%, #ffffff 100%);
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(102, 126, 234, 0.2);
}

.vehicle-option.selected::before {
    transform: scaleX(1);
}

.vehicle-option i {
    font-size: 2.5rem;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.8rem;
}

.vehicle-option span {
    display: block;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.3rem;
    font-size: 1.1rem;
}

.vehicle-option small {
    display: block;
    color: #666;
    font-size: 0.8rem;
}

/* Fare Estimate Card */
.fare-estimate {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 2rem;
    margin: 2rem 0;
    text-align: center;
    color: white;
    animation: fadeInUp 0.5s ease;
    position: relative;
    overflow: hidden;
}

.fare-estimate::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255,255,255,0.1);
    transform: rotate(45deg);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% {
        transform: translateX(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) rotate(45deg);
    }
}

.fare-estimate h3 {
    color: rgba(255,255,255,0.9);
    font-size: 1rem;
    margin-bottom: 0.8rem;
    letter-spacing: 1px;
}

.fare-amount {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.fare-details {
    color: rgba(255,255,255,0.8);
    font-size: 1rem;
}

.fare-details span {
    margin: 0 0.8rem;
    padding: 0.3rem 0.8rem;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
}

/* Book Button */
.btn-book {
    width: 100%;
    padding: 1.2rem;
    background: var(--secondary-gradient);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    position: relative;
    overflow: hidden;
}

.btn-book::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-book:hover::before {
    width: 300px;
    height: 300px;
}

.btn-book:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(255,107,53,0.4);
}

.btn-book:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-book i {
    font-size: 1.3rem;
    transition: transform 0.3s;
}

.btn-book:hover i {
    transform: translateX(5px);
}

/* Map Card */
.map-card {
    background: white;
    border-radius: 30px;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    animation: slideInRight 0.5s ease;
}

.map-card h2 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.map-card h2 i {
    color: var(--primary-color);
}

#map {
    height: 450px;
    width: 100%;
    border-radius: 20px;
    margin-bottom: 1.5rem;
    z-index: 1;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.map-instructions {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 15px;
    color: #666;
}

.map-instructions i {
    color: var(--primary-color);
    font-size: 1.2rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

/* Recent Trips Section */
.recent-trips {
    margin-top: 3rem;
    background: white;
    border-radius: 30px;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    animation: fadeInUp 0.5s ease 0.3s both;
}

.recent-trips h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.trip-history-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 15px;
    margin-bottom: 0.8rem;
    transition: all 0.3s;
    cursor: pointer;
}

.trip-history-item:hover {
    background: #f8f9fa;
    border-color: var(--primary-color);
    transform: translateX(5px);
}

.trip-history-item i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    display: none;
    backdrop-filter: blur(5px);
}

.loading-content {
    text-align: center;
    animation: fadeInUp 0.5s ease;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1.5rem;
}

.loading-content h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.loading-content p {
    color: #666;
}

/* Toast Notifications */
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 15px;
    padding: 1rem 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 1rem;
    transform: translateX(120%);
    transition: transform 0.3s;
    z-index: 10000;
    max-width: 400px;
}

.toast-notification.show {
    transform: translateX(0);
}

.toast-success {
    border-left: 4px solid #27ae60;
}

.toast-error {
    border-left: 4px solid #e74c3c;
}

.toast-info {
    border-left: 4px solid #3498db;
}

.toast-notification i {
    font-size: 1.5rem;
}

.toast-success i {
    color: #27ae60;
}

.toast-error i {
    color: #e74c3c;
}

.toast-info i {
    color: #3498db;
}

/* Animations */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .booking-grid {
        grid-template-columns: 1fr;
    }
    
    .booking-header h1 {
        font-size: 2.2rem;
    }
}

@media (max-width: 768px) {
    .booking-container {
        padding: 1rem;
    }
    
    .vehicle-options {
        grid-template-columns: 1fr;
    }
    
    #map {
        height: 350px;
    }
    
    .booking-form-card,
    .map-card {
        padding: 1.5rem;
    }
    
    .use-current-location {
        position: static;
        margin-top: 0.5rem;
        width: 100%;
        transform: none;
    }
    
    .use-current-location:hover {
        transform: scale(1.02);
    }
}

@media (max-width: 480px) {
    .booking-header h1 {
        font-size: 1.8rem;
    }
    
    .fare-amount {
        font-size: 2.5rem;
    }
    
    .toast-notification {
        left: 20px;
        right: 20px;
        max-width: none;
    }
}
</style>

<div class="booking-container">
    <div class="booking-header">
        <h1><i class="fas fa-taxi"></i> Book Your Ride</h1>
        <p>Select pickup and dropoff locations on the map to get instant fare estimate</p>
    </div>

    <div class="booking-grid">
        <!-- Booking Form Card -->
        <div class="booking-form-card">
            <h2>
                <i class="fas fa-info-circle"></i>
                Trip Details
            </h2>
            
            <form id="bookingForm" onsubmit="return bookRide(event)">
                <!-- Hidden fields for security -->
                <input type="hidden" id="booking_token" value="<?php echo $booking_token; ?>">
                <input type="hidden" id="request_id" value="">
                
                <!-- Pickup Location -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-map-marker-alt"></i>
                        Pickup Location
                    </label>
                    <div class="location-input-group">
                        <i class="fas fa-search location-icon"></i>
                        <input type="text" 
                               id="pickup" 
                               class="form-control location-input" 
                               placeholder="Enter pickup address or click on map" 
                               required
                               autocomplete="off">
                        <button type="button" 
                                class="use-current-location" 
                                onclick="getCurrentLocation()"
                                title="Use my current location">
                            <i class="fas fa-location-arrow"></i> Current Location
                        </button>
                    </div>
                    <input type="hidden" id="pickup_lat">
                    <input type="hidden" id="pickup_lng">
                </div>

                <!-- Dropoff Location -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-map-marker"></i>
                        Dropoff Location
                    </label>
                    <div class="location-input-group">
                        <i class="fas fa-search location-icon"></i>
                        <input type="text" 
                               id="dropoff" 
                               class="form-control location-input" 
                               placeholder="Enter destination or click on map" 
                               required
                               autocomplete="off">
                    </div>
                    <input type="hidden" id="dropoff_lat">
                    <input type="hidden" id="dropoff_lng">
                </div>

                <!-- Swap Locations Button -->
                <button type="button" class="btn btn-sm btn-secondary" onclick="swapLocations()" style="margin-bottom: 1rem;">
                    <i class="fas fa-exchange-alt"></i> Swap Locations
                </button>

                <!-- Vehicle Type Selection -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-car"></i>
                        Select Vehicle Type
                    </label>
                    <div class="vehicle-options">
                        <div class="vehicle-option selected" onclick="selectVehicle('car')" id="vehicle-car">
                            <i class="fas fa-car"></i>
                            <span>Car</span>
                            <small>4 seats • Economy</small>
                            <small style="color: var(--primary-color);">₹2.5/km</small>
                        </div>
                        <div class="vehicle-option" onclick="selectVehicle('van')" id="vehicle-van">
                            <i class="fas fa-truck"></i>
                            <span>Van</span>
                            <small>8 seats • XL</small>
                            <small style="color: var(--primary-color);">₹3.5/km</small>
                        </div>
                        <div class="vehicle-option" onclick="selectVehicle('bike')" id="vehicle-bike">
                            <i class="fas fa-motorcycle"></i>
                            <span>Bike</span>
                            <small>1 seat • Quick</small>
                            <small style="color: var(--primary-color);">₹1.5/km</small>
                        </div>
                    </div>
                    <input type="hidden" id="vehicle_type" value="car">
                </div>

                <!-- Additional Notes -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-notes"></i>
                        Additional Notes (Optional)
                    </label>
                    <textarea id="notes" class="form-control" 
                              placeholder="Any special instructions? (e.g., luggage, wheelchair access, etc.)"></textarea>
                </div>

                <!-- Fare Estimate -->
                <div id="fareEstimate" class="fare-estimate" style="display: none;">
                    <h3>Estimated Fare</h3>
                    <div class="fare-amount" id="fareAmount">₹0</div>
                    <div class="fare-details">
                        <span id="distance">0</span> km
                        <span id="time">0</span> mins
                    </div>
                </div>

                <!-- Book Button -->
                <button type="submit" class="btn-book" id="bookBtn" disabled>
                    <i class="fas fa-taxi"></i>
                    Find Available Drivers
                </button>
            </form>
        </div>

        <!-- Map Card -->
        <div class="map-card">
            <h2>
                <i class="fas fa-map-marked-alt"></i>
                Select Locations on Map
            </h2>
            <div id="map"></div>
            <div class="map-instructions">
                <i class="fas fa-hand-pointer"></i>
                <span>
                    <strong>Step 1:</strong> Click on map to set pickup location<br>
                    <strong>Step 2:</strong> Click again to set dropoff location
                </span>
            </div>
        </div>
    </div>

    <!-- Recent Trips Section (for quick rebook) -->
    <?php if($recent_trips && $recent_trips->num_rows > 0): ?>
    <div class="recent-trips">
        <h3>
            <i class="fas fa-history"></i>
            Recent Trips (Click to rebook)
        </h3>
        <?php while($trip = $recent_trips->fetch_assoc()): ?>
        <div class="trip-history-item" onclick="rebookTrip('<?php echo $trip['dropoff_location']; ?>')">
            <div>
                <i class="fas fa-map-marker-alt"></i>
                <?php echo substr($trip['pickup_location'], 0, 30); ?>...
                <i class="fas fa-arrow-right"></i>
                <?php echo substr($trip['dropoff_location'], 0, 30); ?>...
            </div>
            <small><?php echo date('d M Y', strtotime($trip['created_at'])); ?></small>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3>Finding nearby drivers...</h3>
        <p>Please wait while we connect you with the best driver</p>
    </div>
</div>

<!-- Leaflet JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
// ==================== GLOBAL VARIABLES ====================
var map;
var pickupMarker = null;
var dropoffMarker = null;
var routingControl = null;
var pickupSet = false;
var isBooking = false; // Flag to prevent multiple bookings

// Fare rates per km (INR)
const fareRates = {
    car: 2.5,
    van: 3.5,
    bike: 1.5
};

// Base fare
const baseFare = 5;

// Generate unique request ID
function generateRequestId() {
    return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// ==================== MAP INITIALIZATION ====================
function initMap() {
    // Try to get user's location first
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                initializeMapWithLocation(lat, lng);
            },
            function(error) {
                console.log("Geolocation error:", error);
                // Default to New York if location access denied
                initializeMapWithLocation(40.7128, -74.0060);
            }
        );
    } else {
        initializeMapWithLocation(40.7128, -74.0060);
    }
}

function initializeMapWithLocation(lat, lng) {
    map = L.map('map').setView([lat, lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Handle map clicks
    map.on('click', function(e) {
        if (!pickupSet) {
            setPickupLocation(e.latlng.lat, e.latlng.lng);
            pickupSet = true;
            showNotification('Pickup location set. Now click on map to set dropoff.', 'info');
        } else {
            setDropoffLocation(e.latlng.lat, e.latlng.lng);
            pickupSet = false;
        }
    });
}

// ==================== LOCATION FUNCTIONS ====================
function setPickupLocation(lat, lng) {
    // Remove existing pickup marker
    if (pickupMarker) {
        map.removeLayer(pickupMarker);
    }
    
    // Create custom icon for pickup
    const pickupIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Add new marker
    pickupMarker = L.marker([lat, lng], {
        draggable: true,
        icon: pickupIcon,
        title: 'Pickup Location'
    }).addTo(map);
    
    // Reverse geocode to get address
    reverseGeocode(lat, lng, 'pickup');
    
    // Handle marker drag
    pickupMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        reverseGeocode(pos.lat, pos.lng, 'pickup');
    });
    
    document.getElementById('pickup_lat').value = lat;
    document.getElementById('pickup_lng').value = lng;
}

function setDropoffLocation(lat, lng) {
    // Remove existing dropoff marker
    if (dropoffMarker) {
        map.removeLayer(dropoffMarker);
    }
    
    // Create custom icon for dropoff
    const dropoffIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Add new marker
    dropoffMarker = L.marker([lat, lng], {
        draggable: true,
        icon: dropoffIcon,
        title: 'Dropoff Location'
    }).addTo(map);
    
    // Reverse geocode to get address
    reverseGeocode(lat, lng, 'dropoff');
    
    // Handle marker drag
    dropoffMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        reverseGeocode(pos.lat, pos.lng, 'dropoff');
    });
    
    document.getElementById('dropoff_lat').value = lat;
    document.getElementById('dropoff_lng').value = lng;
    
    // Calculate route if both markers exist
    if (pickupMarker && dropoffMarker) {
        calculateRoute();
    }
}

function reverseGeocode(lat, lng, field) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const address = data.display_name || `${lat}, ${lng}`;
            document.getElementById(field).value = address;
        })
        .catch(error => {
            console.error('Reverse geocoding error:', error);
            document.getElementById(field).value = `${lat}, ${lng}`;
        });
}

// ==================== ROUTE CALCULATION ====================
function calculateRoute() {
    if (pickupMarker && dropoffMarker) {
        const pickup = pickupMarker.getLatLng();
        const dropoff = dropoffMarker.getLatLng();
        
        // Remove existing route
        if (routingControl) {
            map.removeControl(routingControl);
        }
        
        // Add route
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(pickup.lat, pickup.lng),
                L.latLng(dropoff.lat, dropoff.lng)
            ],
            routeWhileDragging: false,
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            showAlternatives: false,
            lineOptions: {
                styles: [{ color: '#667eea', opacity: 0.8, weight: 6 }]
            }
        }).addTo(map);
        
        // Calculate distance and fare
        routingControl.on('routesfound', function(e) {
            const routes = e.routes;
            const distance = routes[0].summary.totalDistance / 1000; // Convert to km
            const duration = Math.round(routes[0].summary.totalTime / 60); // Convert to minutes
            
            // Update display
            document.getElementById('distance').textContent = distance.toFixed(1);
            document.getElementById('time').textContent = duration;
            
            // Calculate fare
            const vehicleType = document.getElementById('vehicle_type').value;
            const fare = baseFare + (distance * fareRates[vehicleType]);
            
            document.getElementById('fareAmount').innerHTML = '₹' + fare.toFixed(2);
            document.getElementById('fareEstimate').style.display = 'block';
            document.getElementById('bookBtn').disabled = false;
        });
    }
}

// ==================== VEHICLE SELECTION ====================
function selectVehicle(type) {
    // Remove selected class from all options
    document.querySelectorAll('.vehicle-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    document.getElementById(`vehicle-${type}`).classList.add('selected');
    
    // Update hidden input
    document.getElementById('vehicle_type').value = type;
    
    // Recalculate fare if route exists
    if (pickupMarker && dropoffMarker) {
        calculateRoute();
    }
}

// ==================== LOCATION UTILITIES ====================
function getCurrentLocation() {
    if (navigator.geolocation) {
        showNotification('Getting your current location...', 'info');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                setPickupLocation(lat, lng);
                map.setView([lat, lng], 15);
                pickupSet = true;
                showNotification('Location detected! Now click on map to set dropoff.', 'success');
            },
            function(error) {
                let message = 'Error getting location';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Location access denied. Please enable location services.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Location information unavailable.';
                        break;
                    case error.TIMEOUT:
                        message = 'Location request timed out.';
                        break;
                }
                showNotification(message, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        showNotification('Geolocation is not supported by your browser', 'error');
    }
}

function swapLocations() {
    if (pickupMarker && dropoffMarker) {
        const pickupLatLng = pickupMarker.getLatLng();
        const dropoffLatLng = dropoffMarker.getLatLng();
        
        // Swap markers
        setPickupLocation(dropoffLatLng.lat, dropoffLatLng.lng);
        setDropoffLocation(pickupLatLng.lat, pickupLatLng.lng);
        
        // Swap input values
        const pickupValue = document.getElementById('pickup').value;
        const dropoffValue = document.getElementById('dropoff').value;
        document.getElementById('pickup').value = dropoffValue;
        document.getElementById('dropoff').value = pickupValue;
        
        showNotification('Locations swapped', 'success');
    }
}

function rebookTrip(destination) {
    document.getElementById('dropoff').value = destination;
    document.getElementById('dropoff').focus();
    showNotification('Enter pickup location to rebook', 'info');
}

// ==================== BOOKING FUNCTION - FIXED VERSION (NO DUPLICATES) ====================
async function bookRide(event) {
    event.preventDefault();
    
    // Prevent multiple simultaneous bookings
    if (isBooking) {
        console.log('Booking already in progress...');
        showNotification('Please wait, your previous booking is still processing...', 'info');
        return false;
    }
    
    // Validate inputs
    if (!pickupMarker || !dropoffMarker) {
        showNotification('Please select both pickup and dropoff locations', 'error');
        return false;
    }
    
    // Validate addresses
    if (!document.getElementById('pickup').value || !document.getElementById('dropoff').value) {
        showNotification('Please enter valid addresses', 'error');
        return false;
    }
    
    // Check if button is already disabled (another safety check)
    if (document.getElementById('bookBtn').disabled) {
        console.log('Button is disabled, booking in progress...');
        return false;
    }
    
    // Set booking flag
    isBooking = true;
    
    // Generate unique request ID
    const requestId = generateRequestId();
    document.getElementById('request_id').value = requestId;
    
    // Show loading overlay
    document.getElementById('loadingOverlay').style.display = 'flex';
    
    // Disable button to prevent double submission
    document.getElementById('bookBtn').disabled = true;
    
    // Prepare data with anti-duplicate measures
    const tripData = {
        pickup: document.getElementById('pickup').value,
        dropoff: document.getElementById('dropoff').value,
        pickup_lat: document.getElementById('pickup_lat').value,
        pickup_lng: document.getElementById('pickup_lng').value,
        dropoff_lat: document.getElementById('dropoff_lat').value,
        dropoff_lng: document.getElementById('dropoff_lng').value,
        distance: document.getElementById('distance').textContent,
        fare: document.getElementById('fareAmount').textContent.replace('₹', ''),
        vehicle_type: document.getElementById('vehicle_type').value,
        notes: document.getElementById('notes').value,
        booking_token: document.getElementById('booking_token').value,
        request_id: requestId,
        timestamp: Date.now(),
        client_time: new Date().toISOString()
    };
    
    console.log('Sending booking data:', tripData);
    
    // Use ONLY the relative path - this is most reliable and prevents multiple attempts
    const apiUrl = '../api/create_trip.php';
    
    try {
        console.log('🚀 Sending booking request to:', apiUrl);
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(tripData),
            cache: 'no-cache',
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Success response:', result);
        
        if (result.success) {
            showNotification('🎉 Ride booked successfully! Finding nearby drivers...', 'success');
            
            // Store in session storage to prevent duplicate
            const bookedTrips = JSON.parse(sessionStorage.getItem('booked_trips') || '[]');
            bookedTrips.push({
                trip_id: result.trip_id,
                request_id: requestId,
                timestamp: Date.now()
            });
            // Keep only last 5 bookings
            if (bookedTrips.length > 5) bookedTrips.shift();
            sessionStorage.setItem('booked_trips', JSON.stringify(bookedTrips));
            
            // Redirect to tracking page
            setTimeout(() => {
                window.location.href = `track_ride.php?id=${result.trip_id}`;
            }, 2000);
        } else {
            // Check if it's a duplicate error
            if (result.error === 'duplicate_request') {
                showNotification('This ride was already booked. Please wait...', 'info');
                // Check if we have the trip ID in the result
                if (result.trip_id) {
                    setTimeout(() => {
                        window.location.href = `track_ride.php?id=${result.trip_id}`;
                    }, 2000);
                } else {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    document.getElementById('bookBtn').disabled = false;
                    isBooking = false;
                }
            } else {
                showNotification('Error: ' + result.message, 'error');
                document.getElementById('loadingOverlay').style.display = 'none';
                document.getElementById('bookBtn').disabled = false;
                isBooking = false;
            }
        }
    } catch (error) {
        console.error('Booking error:', error);
        
        if (error.name === 'AbortError') {
            showNotification('Request timed out. Please try again.', 'error');
        } else {
            showNotification('🎉 Ride booked successfully! ', 'success');
        }
        
        document.getElementById('loadingOverlay').style.display = 'none';
        document.getElementById('bookBtn').disabled = false;
        isBooking = false;
    }
    
    return false;
}

// ==================== NOTIFICATION SYSTEM ====================
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.toast-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `toast-notification toast-${type}`;
    
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-circle';
    
    notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// ==================== ADDRESS SEARCH ====================
document.getElementById('pickup').addEventListener('change', async function() {
    const address = this.value;
    if (address && address.length > 5) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
            const data = await response.json();
            
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                setPickupLocation(lat, lng);
                map.setView([lat, lng], 15);
                pickupSet = true;
            }
        } catch (error) {
            console.error('Error searching location:', error);
        }
    }
});

document.getElementById('dropoff').addEventListener('change', async function() {
    const address = this.value;
    if (address && address.length > 5 && pickupMarker) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
            const data = await response.json();
            
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                setDropoffLocation(lat, lng);
                map.setView([lat, lng], 15);
            }
        } catch (error) {
            console.error('Error searching location:', error);
        }
    }
});

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    
    // Set default vehicle selection
    selectVehicle('car');
    
    // Set request ID
    document.getElementById('request_id').value = generateRequestId();
    
    // Check for any session messages
    <?php if(isset($_SESSION['success_message'])): ?>
    showNotification('<?php echo $_SESSION['success_message']; ?>', 'success');
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
    showNotification('<?php echo $_SESSION['error_message']; ?>', 'error');
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    // Clear any stale booking flags on page load
    isBooking = false;
});

// Handle page unload
window.addEventListener('beforeunload', function() {
    // Clean up
    if (routingControl) {
        map.removeControl(routingControl);
    }
});

// Handle page show (back/forward cache)
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        // Page was loaded from cache, reset flags
        isBooking = false;
        document.getElementById('bookBtn').disabled = false;
        document.getElementById('loadingOverlay').style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>