<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('passenger')) {
    redirect('../login.php');
}

$page_title = 'My Trips';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "
    SELECT t.*, 
           u.full_name as driver_name,
           u.phone as driver_phone,
           v.vehicle_name,
           v.license_plate
    FROM trips t
    LEFT JOIN users u ON t.driver_id = u.id
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    WHERE t.passenger_id = $user_id
";

if ($status_filter) {
    $query .= " AND t.status = '$status_filter'";
}

if ($date_from) {
    $query .= " AND DATE(t.created_at) >= '$date_from'";
}

if ($date_to) {
    $query .= " AND DATE(t.created_at) <= '$date_to'";
}

$query .= " ORDER BY t.created_at DESC";

$trips = $conn->query($query);

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_trips,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_trips,
        SUM(actual_fare) as total_spent,
        AVG(actual_fare) as avg_fare
    FROM trips 
    WHERE passenger_id = $user_id
")->fetch_assoc();
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-history"></i> My Trip History</h1>
        <a href="book_ride.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Book New Ride
        </a>
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
            <div class="stat-icon bg-danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-details">
                <h3>Cancelled</h3>
                <p><?php echo $stats['cancelled_trips'] ?: 0; ?></p>
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

    <!-- Filters -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filter Trips</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="accepted" <?php echo $status_filter == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="started" <?php echo $status_filter == 'started' ? 'selected' : ''; ?>>Started</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="history.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Trips Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Trip History</h3>
            <button onclick="exportToCSV()" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Trip #</th>
                            <th>Date & Time</th>
                            <th>Driver</th>
                            <th>Vehicle</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Distance</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($trips && $trips->num_rows > 0): ?>
                            <?php while($trip = $trips->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $trip['trip_number']; ?></strong></td>
                                <td><?php echo date('d M Y H:i', strtotime($trip['created_at'])); ?></td>
                                <td>
                                    <?php if($trip['driver_name']): ?>
                                        <?php echo $trip['driver_name']; ?><br>
                                        <small><?php echo $trip['driver_phone']; ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($trip['vehicle_name']): ?>
                                        <?php echo $trip['vehicle_name']; ?><br>
                                        <small><?php echo $trip['license_plate']; ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo substr($trip['pickup_location'], 0, 20); ?>...</td>
                                <td><?php echo substr($trip['dropoff_location'], 0, 20); ?>...</td>
                                <td><?php echo number_format($trip['distance_km'], 1); ?> km</td>
                                <td><strong>₹<?php echo number_format($trip['actual_fare'] ?: $trip['estimated_fare'], 2); ?></strong></td>
                                <td>
                                    <span class="status-pill status-<?php echo $trip['status']; ?>">
                                        <?php echo ucfirst($trip['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($trip['status'] == 'completed'): ?>
                                    <a href="rate_driver.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-star"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-taxi fa-3x"></i>
                                        <p>No trips found</p>
                                        <a href="book_ride.php" class="btn btn-primary">Book Your First Ride</a>
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

<script>
function exportToCSV() {
    let csv = [];
    let rows = document.querySelectorAll('table tr');
    
    rows.forEach(row => {
        let rowData = [];
        row.querySelectorAll('td, th').forEach(cell => {
            if (cell.querySelector('.status-pill')) {
                rowData.push(cell.querySelector('.status-pill').textContent.trim());
            } else if (!cell.querySelector('a') && !cell.querySelector('button')) {
                rowData.push(cell.textContent.trim());
            }
        });
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });
    
    let csvContent = csv.join('\n');
    let blob = new Blob([csvContent], { type: 'text/csv' });
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'trip_history.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<style>
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #999;
}

.empty-state i {
    color: #ddd;
    margin-bottom: 1rem;
}

.empty-state p {
    margin-bottom: 1rem;
}

.badge {
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}
</style>

<?php include '../includes/footer.php'; ?>