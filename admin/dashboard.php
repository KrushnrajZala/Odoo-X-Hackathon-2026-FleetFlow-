<?php
include '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Admin Dashboard';
include '../includes/header.php';

// Get statistics
$stats = [
    'total_drivers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role='driver' AND status='active'")->fetch_assoc()['count'],
    'total_passengers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role='passenger' AND status='active'")->fetch_assoc()['count'],
    'active_trips' => $conn->query("SELECT COUNT(*) as count FROM trips WHERE status IN ('accepted', 'started')")->fetch_assoc()['count'],
    'available_vehicles' => $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status='available'")->fetch_assoc()['count'],
    'pending_trips' => $conn->query("SELECT COUNT(*) as count FROM trips WHERE status='pending'")->fetch_assoc()['count'],
    'completed_today' => $conn->query("SELECT COUNT(*) as count FROM trips WHERE status='completed' AND DATE(created_at) = CURDATE()")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(actual_fare) as total FROM trips WHERE status='completed'")->fetch_assoc()['total'],
    'revenue_today' => $conn->query("SELECT SUM(actual_fare) as total FROM trips WHERE status='completed' AND DATE(created_at) = CURDATE()")->fetch_assoc()['total']
];

// Get recent trips with more details
$recent_trips = $conn->query("
    SELECT t.*, 
           u1.full_name as driver_name,
           u1.phone as driver_phone,
           u2.full_name as passenger_name,
           u2.phone as passenger_phone,
           v.vehicle_name,
           v.license_plate,
           v.vehicle_type
    FROM trips t
    LEFT JOIN users u1 ON t.driver_id = u1.id
    LEFT JOIN users u2 ON t.passenger_id = u2.id
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
?>

<style>
/* Enhanced Dashboard Styles */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.welcome-section h1 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 0.3rem;
}

.welcome-section .date {
    color: #666;
    font-size: 0.95rem;
}

.quick-actions {
    display: flex;
    gap: 0.8rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    transform: scaleX(0);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}

.stat-details h3 {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.3rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    line-height: 1.2;
}

.stat-small {
    font-size: 0.85rem;
    color: #27ae60;
    margin-top: 0.2rem;
}

/* Trip Modal Styles */
.trip-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

.trip-modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 30px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    animation: slideIn 0.3s;
    position: relative;
}

.trip-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.trip-modal-header h2 {
    font-size: 1.8rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.trip-modal-header h2 i {
    color: var(--primary-color);
}

.close-modal {
    font-size: 2rem;
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
}

.close-modal:hover {
    color: var(--danger-color);
}

/* Trip Details Grid */
.trip-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-section {
    background: #f8f9fa;
    border-radius: 20px;
    padding: 1.5rem;
}

.detail-section h3 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 0.5rem;
}

.detail-section h3 i {
    color: var(--primary-color);
}

.detail-item {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
}

.detail-item .label {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.3rem;
}

.detail-item .value {
    font-size: 1rem;
    color: #333;
    font-weight: 500;
    word-break: break-word;
}

.detail-item .value.highlight {
    color: var(--primary-color);
    font-size: 1.2rem;
    font-weight: 700;
}

.contact-info {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.contact-btn {
    flex: 1;
    padding: 0.8rem;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
    text-decoration: none;
}

.contact-btn.call {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.contact-btn.sms {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.contact-btn.email {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.contact-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.accepted { background: #d1ecf1; color: #0c5460; }
.status-badge.started { background: #cce5ff; color: #004085; }
.status-badge.completed { background: #d4edda; color: #155724; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

/* Timeline */
.trip-timeline {
    grid-column: span 2;
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid #f0f0f0;
}

.timeline-steps {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    position: relative;
}

.timeline-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e0e0e0;
    z-index: 1;
}

.timeline-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 2;
}

.step-dot {
    width: 40px;
    height: 40px;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    transition: all 0.3s;
}

.step-dot.completed {
    background: var(--success-color);
    border-color: var(--success-color);
    color: white;
}

.step-dot.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    animation: pulse 2s infinite;
}

.step-label {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.3rem;
}

.step-time {
    font-size: 0.8rem;
    color: #999;
}

/* Action Buttons */
.trip-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 2px solid #f0f0f0;
}

.trip-actions .btn {
    flex: 1;
    padding: 1rem;
    font-size: 1rem;
}

/* Enhanced Table Styles */
.trip-row {
    transition: background-color 0.3s;
}

.trip-row:hover {
    background-color: #f8f9fa;
}

.trip-row td {
    vertical-align: middle;
}

.status-pill {
    cursor: pointer;
    transition: transform 0.2s;
}

.status-pill:hover {
    transform: scale(1.05);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-view {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-track {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-track:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.btn-edit {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
}

/* Map Modal */
.map-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.map-modal-content {
    background-color: white;
    margin: 3% auto;
    padding: 2rem;
    border-radius: 30px;
    width: 90%;
    max-width: 1000px;
    height: 80vh;
}

#tripMap {
    height: calc(100% - 60px);
    width: 100%;
    border-radius: 15px;
    margin-top: 1rem;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .trip-details-grid {
        grid-template-columns: 1fr;
    }
    
    .trip-timeline {
        grid-column: span 1;
    }
    
    .timeline-steps {
        flex-direction: column;
        gap: 1rem;
    }
    
    .timeline-steps::before {
        display: none;
    }
    
    .timeline-step {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-align: left;
    }
    
    .step-dot {
        margin: 0;
    }
    
    .trip-actions {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
}

/* Print Styles */
@media print {
    .navbar,
    .quick-actions,
    .action-buttons,
    .btn,
    .contact-btn,
    .trip-actions {
        display: none !important;
    }
    
    .trip-modal-content {
        margin: 0;
        padding: 1rem;
        box-shadow: none;
    }
    
    .detail-section {
        break-inside: avoid;
    }
}
</style>

<div class="container-fluid">
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1><i class="fas fa-tachometer-alt"></i> Welcome back, <?php echo $_SESSION['user_name']; ?>!</h1>
            <div class="date">
                <i class="fas fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
        <div class="quick-actions">
            <a href="add_driver.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Driver
            </a>
            <a href="vehicles.php" class="btn btn-success">
                <i class="fas fa-truck"></i> Add Vehicle
            </a>
            <a href="reports.php" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3>Total Drivers</h3>
                <div class="stat-number"><?php echo $stats['total_drivers']; ?></div>
                <div class="stat-small">
                    <i class="fas fa-arrow-up"></i> Active
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="stat-details">
                <h3>Total Passengers</h3>
                <div class="stat-number"><?php echo $stats['total_passengers']; ?></div>
                <div class="stat-small">
                    <i class="fas fa-user-check"></i> Active
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-details">
                <h3>Active Trips</h3>
                <div class="stat-number"><?php echo $stats['active_trips']; ?></div>
                <div class="stat-small">
                    <i class="fas fa-clock"></i> <?php echo $stats['pending_trips']; ?> pending
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-car"></i>
            </div>
            <div class="stat-details">
                <h3>Available Vehicles</h3>
                <div class="stat-number"><?php echo $stats['available_vehicles']; ?></div>
                <div class="stat-small">
                    <i class="fas fa-check-circle"></i> Ready to use
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-details">
                <h3>Today's Revenue</h3>
                <div class="stat-number">₹<?php echo number_format($stats['revenue_today'] ?? 0, 2); ?></div>
                <div class="stat-small">
                    <i class="fas fa-calendar-day"></i> <?php echo $stats['completed_today']; ?> trips
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-details">
                <h3>Total Revenue</h3>
                <div class="stat-number">₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                <div class="stat-small">
                    <i class="fas fa-history"></i> All time
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Trips Section -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Trips</h3>
            <div class="card-tools">
                <a href="trips.php" class="btn btn-sm btn-primary">View All Trips</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Trip #</th>
                            <th>Date/Time</th>
                            <th>Passenger</th>
                            <th>Driver</th>
                            <th>Vehicle</th>
                            <th>Distance</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_trips && $recent_trips->num_rows > 0): ?>
                            <?php while($trip = $recent_trips->fetch_assoc()): ?>
                            <tr class="trip-row" id="trip-<?php echo $trip['id']; ?>">
                                <td>
                                    <strong>#<?php echo $trip['trip_number']; ?></strong>
                                </td>
                                <td>
                                    <i class="fas fa-clock"></i> <?php echo date('d M H:i', strtotime($trip['created_at'])); ?>
                                </td>
                                <td>
                                    <div><i class="fas fa-user"></i> <?php echo $trip['passenger_name']; ?></div>
                                    <small><i class="fas fa-phone"></i> <?php echo $trip['passenger_phone']; ?></small>
                                </td>
                                <td>
                                    <?php if($trip['driver_name']): ?>
                                        <div><i class="fas fa-user"></i> <?php echo $trip['driver_name']; ?></div>
                                        <small><i class="fas fa-phone"></i> <?php echo $trip['driver_phone']; ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($trip['vehicle_name']): ?>
                                        <div><i class="fas fa-car"></i> <?php echo $trip['vehicle_name']; ?></div>
                                        <small><?php echo $trip['license_plate']; ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($trip['distance_km'], 1); ?> km</td>
                                <td>
                                    <strong class="text-success">₹<?php echo number_format($trip['actual_fare'] ?: $trip['estimated_fare'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="status-pill status-<?php echo $trip['status']; ?>" 
                                          onclick="showStatusHistory(<?php echo $trip['id']; ?>)"
                                          title="Click to view status history">
                                        <?php echo ucfirst($trip['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="viewTripDetails(<?php echo htmlspecialchars(json_encode($trip)); ?>)" 
                                                class="btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <?php if($trip['status'] == 'started' || $trip['status'] == 'accepted'): ?>
                                        <button onclick="trackTrip(<?php echo $trip['id']; ?>, 
                                                '<?php echo $trip['pickup_lat']; ?>', 
                                                '<?php echo $trip['pickup_lng']; ?>', 
                                                '<?php echo $trip['dropoff_lat']; ?>', 
                                                '<?php echo $trip['dropoff_lng']; ?>')" 
                                                class="btn-track">
                                            <i class="fas fa-map-marked-alt"></i> Track
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button onclick="editTrip(<?php echo $trip['id']; ?>)" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-taxi fa-3x"></i>
                                        <p>No trips found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Trip Details Modal -->
<div id="tripModal" class="trip-modal">
    <div class="trip-modal-content">
        <div class="trip-modal-header">
            <h2><i class="fas fa-info-circle"></i> <span id="modalTitle">Trip Details</span></h2>
            <span class="close-modal" onclick="closeTripModal()">&times;</span>
        </div>
        <div id="tripDetailsContent"></div>
    </div>
</div>

<!-- Track Trip Modal -->
<div id="trackModal" class="map-modal">
    <div class="map-modal-content">
        <div class="trip-modal-header">
            <h2><i class="fas fa-map-marked-alt"></i> Live Trip Tracking</h2>
            <span class="close-modal" onclick="closeTrackModal()">&times;</span>
        </div>
        <div id="tripMap"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
// Function to view trip details
function viewTripDetails(trip) {
    const statusColors = {
        pending: '#fff3cd',
        accepted: '#d1ecf1',
        started: '#cce5ff',
        completed: '#d4edda',
        cancelled: '#f8d7da'
    };
    
    const content = `
        <div class="trip-details-grid">
            <div class="detail-section">
                <h3><i class="fas fa-info-circle"></i> Trip Information</h3>
                <div class="detail-item">
                    <span class="label">Trip Number</span>
                    <span class="value highlight">#${trip.trip_number}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Status</span>
                    <span class="status-badge ${trip.status}" style="background: ${statusColors[trip.status]}">
                        ${trip.status.charAt(0).toUpperCase() + trip.status.slice(1)}
                    </span>
                </div>
                <div class="detail-item">
                    <span class="label">Created At</span>
                    <span class="value">${new Date(trip.created_at).toLocaleString()}</span>
                </div>
                ${trip.completed_at ? `
                <div class="detail-item">
                    <span class="label">Completed At</span>
                    <span class="value">${new Date(trip.completed_at).toLocaleString()}</span>
                </div>
                ` : ''}
                <div class="detail-item">
                    <span class="label">Distance</span>
                    <span class="value">${trip.distance_km} km</span>
                </div>
                <div class="detail-item">
                    <span class="label">Estimated Fare</span>
                    <span class="value">₹${parseFloat(trip.estimated_fare).toFixed(2)}</span>
                </div>
                ${trip.actual_fare ? `
                <div class="detail-item">
                    <span class="label">Actual Fare</span>
                    <span class="value highlight">₹${parseFloat(trip.actual_fare).toFixed(2)}</span>
                </div>
                ` : ''}
            </div>

            <div class="detail-section">
                <h3><i class="fas fa-user"></i> Passenger Details</h3>
                <div class="detail-item">
                    <span class="label">Name</span>
                    <span class="value">${trip.passenger_name || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Phone</span>
                    <span class="value">${trip.passenger_phone || 'N/A'}</span>
                </div>
                <div class="contact-info">
                    ${trip.passenger_phone ? `
                    <a href="tel:${trip.passenger_phone}" class="contact-btn call">
                        <i class="fas fa-phone"></i> Call
                    </a>
                    <a href="sms:${trip.passenger_phone}" class="contact-btn sms">
                        <i class="fas fa-comment"></i> SMS
                    </a>
                    ` : ''}
                </div>
            </div>

            <div class="detail-section">
                <h3><i class="fas fa-truck"></i> Driver Details</h3>
                ${trip.driver_name ? `
                <div class="detail-item">
                    <span class="label">Name</span>
                    <span class="value">${trip.driver_name}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Phone</span>
                    <span class="value">${trip.driver_phone || 'N/A'}</span>
                </div>
                <div class="contact-info">
                    ${trip.driver_phone ? `
                    <a href="tel:${trip.driver_phone}" class="contact-btn call">
                        <i class="fas fa-phone"></i> Call
                    </a>
                    <a href="sms:${trip.driver_phone}" class="contact-btn sms">
                        <i class="fas fa-comment"></i> SMS
                    </a>
                    ` : ''}
                </div>
                ` : '<p class="text-muted">No driver assigned</p>'}
            </div>

            <div class="detail-section">
                <h3><i class="fas fa-car"></i> Vehicle Details</h3>
                ${trip.vehicle_name ? `
                <div class="detail-item">
                    <span class="label">Vehicle</span>
                    <span class="value">${trip.vehicle_name}</span>
                </div>
                <div class="detail-item">
                    <span class="label">License Plate</span>
                    <span class="value">${trip.license_plate || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Type</span>
                    <span class="value">${trip.vehicle_type || 'N/A'}</span>
                </div>
                ` : '<p class="text-muted">No vehicle assigned</p>'}
            </div>

            <div class="detail-section" style="grid-column: span 2;">
                <h3><i class="fas fa-map-marker-alt"></i> Locations</h3>
                <div class="detail-item">
                    <span class="label">Pickup</span>
                    <span class="value">${trip.pickup_location}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Dropoff</span>
                    <span class="value">${trip.dropoff_location}</span>
                </div>
                ${trip.pickup_lat && trip.dropoff_lat ? `
                <div style="margin-top: 1rem;">
                    <button onclick="showRouteOnMap(${trip.pickup_lat}, ${trip.pickup_lng}, ${trip.dropoff_lat}, ${trip.dropoff_lng})" 
                            class="btn btn-primary btn-sm">
                        <i class="fas fa-route"></i> View Route
                    </button>
                </div>
                ` : ''}
            </div>

            <div class="trip-timeline">
                <h3><i class="fas fa-clock"></i> Trip Timeline</h3>
                <div class="timeline-steps">
                    <div class="timeline-step">
                        <div class="step-dot ${trip.created_at ? 'completed' : ''}">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">Created</div>
                        <div class="step-time">${trip.created_at ? new Date(trip.created_at).toLocaleTimeString() : '-'}</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-dot ${trip.accepted_at ? 'completed' : (trip.status == 'accepted' || trip.status == 'started' ? 'active' : '')}">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">Accepted</div>
                        <div class="step-time">${trip.accepted_at ? new Date(trip.accepted_at).toLocaleTimeString() : '-'}</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-dot ${trip.started_at ? 'completed' : (trip.status == 'started' ? 'active' : '')}">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">Started</div>
                        <div class="step-time">${trip.started_at ? new Date(trip.started_at).toLocaleTimeString() : '-'}</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-dot ${trip.completed_at ? 'completed' : ''}">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-label">Completed</div>
                        <div class="step-time">${trip.completed_at ? new Date(trip.completed_at).toLocaleTimeString() : '-'}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="trip-actions">
            ${trip.status == 'pending' ? `
            <button onclick="assignDriver(${trip.id})" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Assign Driver
            </button>
            ` : ''}
            
            ${trip.status == 'started' ? `
            <button onclick="trackTrip(${trip.id}, ${trip.pickup_lat}, ${trip.pickup_lng}, ${trip.dropoff_lat}, ${trip.dropoff_lng})" 
                    class="btn btn-success">
                <i class="fas fa-map-marked-alt"></i> Track Live
            </button>
            ` : ''}
            
            <button onclick="printTripDetails(${trip.id})" class="btn btn-info">
                <i class="fas fa-print"></i> Print
            </button>
            
            <button onclick="exportTripDetails(${trip.id})" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    `;
    
    document.getElementById('tripDetailsContent').innerHTML = content;
    document.getElementById('tripModal').style.display = 'block';
}

// Function to track trip
function trackTrip(tripId, pickupLat, pickupLng, dropoffLat, dropoffLng) {
    document.getElementById('trackModal').style.display = 'block';
    
    setTimeout(() => {
        const map = L.map('tripMap').setView([pickupLat, pickupLng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add pickup marker
        L.marker([pickupLat, pickupLng], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(map).bindPopup('Pickup Location').openPopup();
        
        // Add dropoff marker
        L.marker([dropoffLat, dropoffLng], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(map).bindPopup('Dropoff Location');
        
        // Add route
        L.Routing.control({
            waypoints: [
                L.latLng(pickupLat, pickupLng),
                L.latLng(dropoffLat, dropoffLng)
            ],
            routeWhileDragging: false,
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true
        }).addTo(map);
        
        // Simulate live tracking (in real app, fetch from API)
        setInterval(async () => {
            try {
                const response = await fetch(`../api/get_trip_location.php?trip_id=${tripId}`);
                const data = await response.json();
                if (data.success && data.location) {
                    // Update driver marker
                    // This would be implemented based on your API
                }
            } catch (error) {
                console.error('Error fetching location:', error);
            }
        }, 5000);
    }, 500);
}

// Function to show route on map
function showRouteOnMap(pickupLat, pickupLng, dropoffLat, dropoffLng) {
    closeTripModal();
    trackTrip(null, pickupLat, pickupLng, dropoffLat, dropoffLng);
}

// Function to close trip modal
function closeTripModal() {
    document.getElementById('tripModal').style.display = 'none';
}

// Function to close track modal
function closeTrackModal() {
    document.getElementById('trackModal').style.display = 'none';
    // Clean up map
    const mapContainer = document.getElementById('tripMap');
    if (mapContainer._leaflet_id) {
        mapContainer.innerHTML = '';
    }
}

// Function to show status history
function showStatusHistory(tripId) {
    // Implement status history modal
    alert(`Status history for trip #${tripId} - Coming soon!`);
}

// Function to assign driver
function assignDriver(tripId) {
    window.location.href = `assign_driver.php?id=${tripId}`;
}

// Function to edit trip
function editTrip(tripId) {
    window.location.href = `edit_trip.php?id=${tripId}`;
}

// Function to print trip details
function printTripDetails(tripId) {
    window.open(`print_trip.php?id=${tripId}`, '_blank');
}

// Function to export trip details
function exportTripDetails(tripId) {
    window.location.href = `export_trip.php?id=${tripId}`;
}

// Close modals when clicking outside
window.onclick = function(event) {
    const tripModal = document.getElementById('tripModal');
    const trackModal = document.getElementById('trackModal');
    
    if (event.target == tripModal) {
        closeTripModal();
    }
    if (event.target == trackModal) {
        closeTrackModal();
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTripModal();
        closeTrackModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>