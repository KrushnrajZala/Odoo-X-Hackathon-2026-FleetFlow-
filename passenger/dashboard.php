<?php
include '../includes/config.php';

// Check if user is logged in and is passenger
if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!hasRole('passenger')) {
    redirect('../index.php');
}

$page_title = 'Passenger Dashboard';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get passenger's recent trips
$recent_trips = $conn->query("
    SELECT t.*, 
           u.full_name as driver_name,
           u.phone as driver_phone,
           v.vehicle_name,
           v.license_plate
    FROM trips t
    LEFT JOIN users u ON t.driver_id = u.id
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    WHERE t.passenger_id = $user_id
    ORDER BY t.created_at DESC
    LIMIT 5
");

// Get active trip if any
$active_trip = $conn->query("
    SELECT t.*, 
           u.full_name as driver_name,
           u.phone as driver_phone,
           v.vehicle_name,
           v.license_plate
    FROM trips t
    LEFT JOIN users u ON t.driver_id = u.id
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    WHERE t.passenger_id = $user_id 
    AND t.status IN ('pending', 'accepted', 'started')
    ORDER BY t.created_at DESC
    LIMIT 1
")->fetch_assoc();

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_trips,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_trips,
        SUM(actual_fare) as total_spent
    FROM trips 
    WHERE passenger_id = $user_id
")->fetch_assoc();
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-home"></i> Passenger Dashboard</h1>
        <a href="book_ride.php" class="btn btn-primary">
            <i class="fas fa-taxi"></i> Book a Ride
        </a>
    </div>

    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="welcome-content">
            <h2>Welcome back, <?php echo $_SESSION['user_name']; ?>!</h2>
            <p>Ready for your next ride? Book a ride now and track it in real-time.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-route"></i>
            </div>
            <div class="stat-details">
                <h3>Total Trips</h3>
                <p><?php echo $stats['total_trips'] ?: 0; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3>Completed</h3>
                <p><?php echo $stats['completed_trips'] ?: 0; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h3>Pending</h3>
                <p><?php echo $conn->query("SELECT COUNT(*) as count FROM trips WHERE passenger_id = $user_id AND status = 'pending'")->fetch_assoc()['count']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-details">
                <h3>Total Spent</h3>
                <p>₹<?php echo number_format($stats['total_spent'] ?: 0, 2); ?></p>
            </div>
        </div>
    </div>

    <?php if($active_trip): ?>
    <!-- Active Trip Card -->
    <div class="active-trip-card">
        <div class="card-header">
            <h3><i class="fas fa-road"></i> Active Trip #<?php echo $active_trip['trip_number']; ?></h3>
            <span class="status-pill status-<?php echo $active_trip['status']; ?>">
                <?php echo ucfirst($active_trip['status']); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="trip-info-grid">
                <div class="info-item">
                    <label>Status</label>
                    <strong><?php echo ucfirst($active_trip['status']); ?></strong>
                </div>
                <?php if($active_trip['driver_name']): ?>
                <div class="info-item">
                    <label>Driver</label>
                    <strong><?php echo $active_trip['driver_name']; ?></strong>
                </div>
                <div class="info-item">
                    <label>Driver Phone</label>
                    <a href="tel:<?php echo $active_trip['driver_phone']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-phone"></i> Call
                    </a>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <label>Pickup</label>
                    <p><?php echo substr($active_trip['pickup_location'], 0, 50); ?></p>
                </div>
                <div class="info-item">
                    <label>Dropoff</label>
                    <p><?php echo substr($active_trip['dropoff_location'], 0, 50); ?></p>
                </div>
                <div class="info-item">
                    <label>Fare</label>
                    <strong>₹<?php echo number_format($active_trip['estimated_fare'], 2); ?></strong>
                </div>
            </div>
            <div class="action-buttons">
                <a href="track_ride.php?id=<?php echo $active_trip['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-map-marked-alt"></i> Track Ride
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        <div class="actions-grid">
            <a href="book_ride.php" class="action-card">
                <i class="fas fa-taxi"></i>
                <span>Book a Ride</span>
            </a>
            <a href="history.php" class="action-card">
                <i class="fas fa-history"></i>
                <span>Trip History</span>
            </a>
            <a href="profile.php" class="action-card">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="support.php" class="action-card">
                <i class="fas fa-headset"></i>
                <span>Support</span>
            </a>
        </div>
    </div>

    <!-- Recent Trips -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Trips</h3>
            <a href="history.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Trip #</th>
                            <th>Date</th>
                            <th>Driver</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_trips && $recent_trips->num_rows > 0): ?>
                            <?php while($trip = $recent_trips->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $trip['trip_number']; ?></td>
                                <td><?php echo date('d M Y', strtotime($trip['created_at'])); ?></td>
                                <td><?php echo $trip['driver_name'] ?: 'Not Assigned'; ?></td>
                                <td><?php echo substr($trip['pickup_location'], 0, 20); ?>...</td>
                                <td><?php echo substr($trip['dropoff_location'], 0, 20); ?>...</td>
                                <td>₹<?php echo number_format($trip['actual_fare'] ?: $trip['estimated_fare'], 2); ?></td>
                                <td>
                                    <span class="status-pill status-<?php echo $trip['status']; ?>">
                                        <?php echo ucfirst($trip['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No trips found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.welcome-card {
    background: linear-gradient(135deg, var(--primary-color), #ff8c5a);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(255,107,53,0.3);
}

.welcome-content h2 {
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
}

.welcome-content p {
    opacity: 0.9;
    font-size: 1.1rem;
}

.active-trip-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border-left: 5px solid var(--success-color);
}

.trip-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}

.quick-actions {
    margin-bottom: 2rem;
}

.quick-actions h3 {
    margin-bottom: 1rem;
    color: #333;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.action-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    color: #333;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    color: var(--primary-color);
}

.action-card i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.action-card span {
    display: block;
    font-weight: 500;
}

@media (max-width: 768px) {
    .trip-info-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php include '../includes/footer.php'; ?>