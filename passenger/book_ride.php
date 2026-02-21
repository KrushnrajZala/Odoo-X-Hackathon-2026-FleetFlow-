<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('passenger')) {
    redirect('../login.php');
}

$page_title = 'Book a Ride';
include '../includes/header.php';

// Get available vehicles for selection
$vehicles = $conn->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY vehicle_type");
?>

<style>
.booking-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.booking-header {
    text-align: center;
    margin-bottom: 3rem;
}

.booking-header h1 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 0.5rem;
}

.booking-header p {
    color: #666;
    font-size: 1.1rem;
}

.booking-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 2rem;
}

.booking-form-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.booking-form-card h2 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.booking-form-card h2 i {
    color: var(--primary-color);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
    font-weight: 500;
}

.form-group label i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.location-input-group {
    position: relative;
}

.location-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.location-input {
    padding-left: 2.5rem;
}

.use-current-location {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 0.9rem;
}

.use-current-location:hover {
    text-decoration: underline;
}

.vehicle-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 0.5rem;
}

.vehicle-option {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.vehicle-option:hover {
    border-color: var(--primary-color);
    background: #fff5f0;
}

.vehicle-option.selected {
    border-color: var(--primary-color);
    background: #fff5f0;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255,107,53,0.2);
}

.vehicle-option i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.vehicle-option span {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.3rem;
}

.vehicle-option small {
    display: block;
    color: #666;
    font-size: 0.8rem;
}

.fare-estimate {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin: 1.5rem 0;
    text-align: center;
}

.fare-estimate h3 {
    color: #666;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.fare-amount {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.fare-details {
    color: #666;
    font-size: 0.9rem;
}

.fare-details span {
    margin: 0 0.5rem;
}

.btn-book {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, var(--primary-color), #ff8c5a);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-book:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(255,107,53,0.3);
}

.btn-book:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.map-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.map-card h2 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.map-card h2 i {
    color: var(--primary-color);
}

#map {
    height: 400px;
    width: 100%;
    border-radius: 15px;
    margin-bottom: 1rem;
    z-index: 1;
}

.map-instructions {
    display: flex;
    gap: 1rem;
    color: #666;
    font-size: 0.9rem;
}

.map-instructions i {
    color: var(--primary-color);
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    display: none;
}

.loading-content {
    text-align: center;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .booking-grid {
        grid-template-columns: 1fr;
    }
    
    .booking-header h1 {
        font-size: 2rem;
    }
    
    .vehicle-options {
        grid-template-columns: repeat(2, 1fr);
    }
    
    #map {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .booking-container {
        padding: 1rem;
    }
    
    .vehicle-options {
        grid-template-columns: 1fr;
    }
    
    .fare-amount {
        font-size: 2rem;
    }
}
</style>

<div class="booking-container">
    <div class="booking-header">
        <h1><i class="fas fa-taxi"></i> Book Your Ride</h1>
        <p>Select pickup and dropoff locations to get fare estimate</p>
    </div>

    <div class="booking-grid">
        <!-- Booking Form -->
        <div class="booking-form-card">
            <h2><i class="fas fa-info-circle"></i> Trip Details</h2>
            
            <form id="bookingForm" onsubmit="return bookRide(event)">
                <!-- Pickup Location -->
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Pickup Location</label>
                    <div class="location-input-group">
                        <i class="fas fa-search location-icon"></i>
                        <input type="text" id="pickup" class="form-control location-input" 
                               placeholder="Enter pickup address" required>
                        <button type="button" class="use-current-location" onclick="getCurrentLocation('pickup')">
                            <i class="fas fa-location-arrow"></i> Current Location
                        </button>
                    </div>
                    <input type="hidden" id="pickup_lat">
                    <input type="hidden" id="pickup_lng">
                </div>

                <!-- Dropoff Location -->
                <div class="form-group">
                    <label><i class="fas fa-map-marker"></i> Dropoff Location</label>
                    <div class="location-input-group">
                        <i class="fas fa-search location-icon"></i>
                        <input type="text" id="dropoff" class="form-control location-input" 
                               placeholder="Enter destination" required>
                    </div>
                    <input type="hidden" id="dropoff_lat">
                    <input type="hidden" id="dropoff_lng">
                </div>

                <!-- Vehicle Type Selection -->
                <div class="form-group">
                    <label><i class="fas fa-car"></i> Select Vehicle Type</label>
                    <div class="vehicle-options">
                        <div class="vehicle-option selected" onclick="selectVehicle('car')" id="vehicle-car">
                            <i class="fas fa-car"></i>
                            <span>Car</span>
                            <small>4 seats • ₹2.5/km</small>
                        </div>
                        <div class="vehicle-option" onclick="selectVehicle('van')" id="vehicle-van">
                            <i class="fas fa-truck"></i>
                            <span>Van</span>
                            <small>8 seats • ₹3.5/km</small>
                        </div>
                        <div class="vehicle-option" onclick="selectVehicle('bike')" id="vehicle-bike">
                            <i class="fas fa-motorcycle"></i>
                            <span>Bike</span>
                            <small>1 seat • ₹1.5/km</small>
                        </div>
                    </div>
                    <input type="hidden" id="vehicle_type" value="car">
                </div>

                <!-- Additional Notes -->
                <div class="form-group">
                    <label><i class="fas fa-notes"></i> Additional Notes (Optional)</label>
                    <textarea id="notes" class="form-control" placeholder="Any special instructions?"></textarea>
                </div>

                <!-- Fare Estimate -->
                <div id="fareEstimate" class="fare-estimate" style="display: none;">
                    <h3>Estimated Fare</h3>
                    <div class="fare-amount" id="fareAmount">₹0</div>
                    <div class="fare-details">
                        <span id="distance">0</span> km • 
                        <span id="time">0</span> mins
                    </div>
                </div>

                <!-- Book Button -->
                <button type="submit" class="btn-book" id="bookBtn" disabled>
                    <i class="fas fa-taxi"></i> Find Available Drivers
                </button>
            </form>
        </div>

        <!-- Map Card -->
        <div class="map-card">
            <h2><i class="fas fa-map-marked-alt"></i> Select Locations on Map</h2>
            <div id="map"></div>
            <div class="map-instructions">
                <i class="fas fa-info-circle"></i>
                <span>Click on the map to set pickup location, then click again to set dropoff location</span>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3>Finding nearby drivers...</h3>
        <p>Please wait</p>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
// Map variables
var map;
var pickupMarker = null;
var dropoffMarker = null;
var routingControl = null;
var pickupSet = false;

// Fare rates per km
const fareRates = {
    car: 2.5,
    van: 3.5,
    bike: 1.5
};

// Base fare
const baseFare = 5;

// Initialize map
function initMap() {
    // Default location (New York)
    const defaultLat = 40.7128;
    const defaultLng = -74.0060;
    
    map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Handle map clicks
    map.on('click', function(e) {
        if (!pickupSet) {
            // Set pickup location
            setPickupLocation(e.latlng.lat, e.latlng.lng);
            pickupSet = true;
        } else {
            // Set dropoff location
            setDropoffLocation(e.latlng.lat, e.latlng.lng);
            pickupSet = false;
        }
    });
}

// Set pickup location
function setPickupLocation(lat, lng) {
    // Remove existing pickup marker
    if (pickupMarker) {
        map.removeLayer(pickupMarker);
    }
    
    // Add new marker
    pickupMarker = L.marker([lat, lng], {
        draggable: true,
        title: 'Pickup Location'
    }).addTo(map);
    
    // Reverse geocode to get address
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('pickup').value = data.display_name;
            document.getElementById('pickup_lat').value = lat;
            document.getElementById('pickup_lng').value = lng;
        });
    
    // Handle marker drag
    pickupMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        setPickupLocation(pos.lat, pos.lng);
    });
    
    // Show notification
    showNotification('Pickup location set. Now click on map to set dropoff location.', 'info');
}

// Set dropoff location
function setDropoffLocation(lat, lng) {
    // Remove existing dropoff marker
    if (dropoffMarker) {
        map.removeLayer(dropoffMarker);
    }
    
    // Add new marker
    dropoffMarker = L.marker([lat, lng], {
        draggable: true,
        title: 'Dropoff Location'
    }).addTo(map);
    
    // Reverse geocode to get address
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('dropoff').value = data.display_name;
            document.getElementById('dropoff_lat').value = lat;
            document.getElementById('dropoff_lng').value = lng;
            
            // Calculate route
            calculateRoute();
        });
    
    // Handle marker drag
    dropoffMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        setDropoffLocation(pos.lat, pos.lng);
    });
}

// Calculate route between pickup and dropoff
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
            showAlternatives: false
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
            
            document.getElementById('fareAmount').textContent = '₹' + fare.toFixed(2);
            document.getElementById('fareEstimate').style.display = 'block';
            document.getElementById('bookBtn').disabled = false;
        });
    }
}

// Get current location for pickup
function getCurrentLocation(type) {
    if (navigator.geolocation) {
        showNotification('Getting your current location...', 'info');
        
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            setPickupLocation(lat, lng);
            map.setView([lat, lng], 15);
        }, function(error) {
            showNotification('Error getting location: ' + error.message, 'error');
        });
    } else {
        showNotification('Geolocation is not supported by your browser', 'error');
    }
}

// Select vehicle type
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

// Book ride
async function bookRide(event) {
    event.preventDefault();
    
    // Validate inputs
    if (!pickupMarker || !dropoffMarker) {
        showNotification('Please select both pickup and dropoff locations', 'error');
        return false;
    }
    
    // Show loading overlay
    document.getElementById('loadingOverlay').style.display = 'flex';
    
    // Prepare data
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
        notes: document.getElementById('notes').value
    };
    
    try {
        // Send request to create trip
        const response = await fetch('../api/create_trip.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(tripData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Ride booked successfully! Finding nearby drivers...', 'success');
            
            // Redirect to tracking page
            setTimeout(() => {
                window.location.href = `track_ride.php?id=${result.trip_id}`;
            }, 2000);
        } else {
            showNotification('Error: ' + result.message, 'error');
            document.getElementById('loadingOverlay').style.display = 'none';
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error. Please try again.', 'error');
        document.getElementById('loadingOverlay').style.display = 'none';
    }
    
    return false;
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `toast-notification toast-${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
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

// Search for location
document.getElementById('pickup').addEventListener('change', async function() {
    const address = this.value;
    if (address) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
            const data = await response.json();
            
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                setPickupLocation(lat, lng);
                map.setView([lat, lng], 15);
            }
        } catch (error) {
            console.error('Error searching location:', error);
        }
    }
});

document.getElementById('dropoff').addEventListener('change', async function() {
    const address = this.value;
    if (address) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
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

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
</script>

<?php include '../includes/footer.php'; ?>