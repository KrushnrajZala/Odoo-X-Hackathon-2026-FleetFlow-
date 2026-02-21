<?php
include '../includes/config.php';

// Check if user is logged in and is driver
if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!hasRole('driver')) {
    redirect('../index.php');
}

$page_title = 'Driver Dashboard';
include '../includes/header.php';

$driver_id = $_SESSION['user_id'];

// Get driver details
$driver_details = $conn->query("
    SELECT d.*, u.phone, u.email, u.full_name 
    FROM driver_details d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.user_id = $driver_id
")->fetch_assoc();

// Get pending ride requests
$pending_requests = $conn->query("
    SELECT t.*, 
           u.full_name as passenger_name,
           u.phone as passenger_phone
    FROM trips t
    JOIN users u ON t.passenger_id = u.id
    WHERE t.status = 'pending' 
    AND t.driver_id IS NULL
    ORDER BY t.created_at DESC
");

// Get active ride
$active_ride = $conn->query("
    SELECT t.*, 
           u.full_name as passenger_name,
           u.phone as passenger_phone
    FROM trips t
    JOIN users u ON t.passenger_id = u.id
    WHERE t.driver_id = $driver_id 
    AND t.status IN ('accepted', 'started')
    ORDER BY t.created_at DESC
    LIMIT 1
")->fetch_assoc();

// Get today's stats
$today_stats = $conn->query("
    SELECT 
        COUNT(*) as today_trips,
        SUM(actual_fare) as today_earnings
    FROM trips 
    WHERE driver_id = $driver_id 
    AND DATE(created_at) = CURDATE()
    AND status = 'completed'
")->fetch_assoc();

// Get weekly stats
$weekly_stats = $conn->query("
    SELECT 
        COUNT(*) as weekly_trips,
        SUM(actual_fare) as weekly_earnings
    FROM trips 
    WHERE driver_id = $driver_id 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND status = 'completed'
")->fetch_assoc();

// Update driver status
if (isset($_GET['status'])) {
    $status = sanitize($_GET['status']);
    $allowed_status = ['available', 'off_duty'];
    
    if (in_array($status, $allowed_status)) {
        $conn->query("UPDATE driver_details SET current_status = '$status' WHERE user_id = $driver_id");
        redirect('dashboard.php');
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt"></i> Driver Dashboard</h1>
    </div>

    <!-- Driver Status Bar -->
    <div class="status-bar">
        <div class="status-info">
            <span class="status-label">Your Status:</span>
            <span class="status-pill status-<?php echo $driver_details['current_status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $driver_details['current_status'])); ?>
            </span>
        </div>
        <div class="status-actions">
            <?php if($driver_details['current_status'] == 'off_duty'): ?>
                <a href="?status=available" class="btn btn-success">
                    <i class="fas fa-power-off"></i> Go Online
                </a>
            <?php else: ?>
                <a href="?status=off_duty" class="btn btn-danger">
                    <i class="fas fa-power-off"></i> Go Offline
                </a>
            <?php endif; ?>
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
                <p><?php echo $driver_details['total_trips'] ?: 0; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-details">
                <h3>Today's Trips</h3>
                <p><?php echo $today_stats['today_trips'] ?: 0; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-details">
                <h3>Today's Earnings</h3>
                <p>₹<?php echo number_format($today_stats['today_earnings'] ?: 0, 2); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-details">
                <h3>Safety Score</h3>
                <p><?php echo $driver_details['safety_score']; ?> / 5</p>
            </div>
        </div>
    </div>

    <?php if($active_ride): ?>
    <!-- Active Ride Card -->
    <div class="active-ride-card">
        <div class="card-header">
            <h3><i class="fas fa-road"></i> Active Ride #<?php echo $active_ride['trip_number']; ?></h3>
            <span class="status-pill status-<?php echo $active_ride['status']; ?>">
                <?php echo ucfirst($active_ride['status']); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="ride-info-grid">
                <div class="info-item">
                    <label>Passenger</label>
                    <strong><?php echo $active_ride['passenger_name']; ?></strong>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <a href="tel:<?php echo $active_ride['passenger_phone']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-phone"></i> Call
                    </a>
                </div>
                <div class="info-item">
                    <label>Pickup</label>
                    <p><?php echo substr($active_ride['pickup_location'], 0, 50); ?></p>
                </div>
                <div class="info-item">
                    <label>Dropoff</label>
                    <p><?php echo substr($active_ride['dropoff_location'], 0, 50); ?></p>
                </div>
                <div class="info-item">
                    <label>Fare</label>
                    <strong>₹<?php echo number_format($active_ride['estimated_fare'], 2); ?></strong>
                </div>
            </div>
            <div class="action-buttons">
                <a href="active_ride.php" class="btn btn-primary">
                    <i class="fas fa-play-circle"></i> Go to Active Ride
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pending Requests -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Available Ride Requests</h3>
        </div>
        <div class="card-body">
            <?php if($pending_requests && $pending_requests->num_rows > 0): ?>
                <div class="requests-grid">
                    <?php while($request = $pending_requests->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <h4>Ride #<?php echo $request['trip_number']; ?></h4>
                            <span class="badge badge-warning">Pending</span>
                        </div>
                        <div class="request-body">
                            <p><i class="fas fa-user"></i> <?php echo $request['passenger_name']; ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> From: <?php echo substr($request['pickup_location'], 0, 30); ?>...</p>
                            <p><i class="fas fa-map-marker"></i> To: <?php echo substr($request['dropoff_location'], 0, 30); ?>...</p>
                            <p><i class="fas fa-rupee-sign"></i> Fare: ₹<?php echo number_format($request['estimated_fare'], 2); ?></p>
                            <p><i class="fas fa-clock"></i> <?php echo date('d M Y H:i', strtotime($request['created_at'])); ?></p>
                        </div>
                        <div class="request-footer">
                            <form action="../api/accept_ride.php" method="POST">
                                <input type="hidden" name="trip_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="driver_id" value="<?php echo $driver_id; ?>">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-check"></i> Accept Ride
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-taxi fa-3x"></i>
                    <p>No ride requests available at the moment</p>
                    <?php if($driver_details['current_status'] == 'off_duty'): ?>
                        <p class="text-muted">Go online to start receiving ride requests</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.status-bar {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.status-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-label {
    font-weight: 600;
    color: #333;
}

.active-ride-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border-left: 5px solid var(--success-color);
}

.ride-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}

.requests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.request-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    transition: transform 0.3s, box-shadow 0.3s;
}

.request-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.request-header h4 {
    margin: 0;
    color: #333;
    font-size: 1rem;
}

.request-body p {
    margin-bottom: 0.5rem;
    color: #555;
    font-size: 0.9rem;
}

.request-body p i {
    width: 20px;
    color: var(--primary-color);
}

.request-footer {
    margin-top: 1rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #999;
}

.empty-state i {
    color: #ddd;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .status-bar {
        flex-direction: column;
        text-align: center;
    }
    
    .ride-info-grid {
        grid-template-columns: 1fr;
    }
    
    .requests-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>