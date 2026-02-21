<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('driver')) {
    redirect('../login.php');
}

$driver_id = $_SESSION['user_id'];
$page_title = 'Trip History';
include '../includes/header.php';

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "
    SELECT t.*, 
           u.full_name as passenger_name,
           u.phone as passenger_phone,
           v.vehicle_name,
           v.license_plate
    FROM trips t
    JOIN users u ON t.passenger_id = u.id
    JOIN vehicles v ON t.vehicle_id = v.id
    WHERE t.driver_id = $driver_id
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
        SUM(actual_fare) as total_earnings,
        AVG(distance_km) as avg_distance
    FROM trips 
    WHERE driver_id = $driver_id
")->fetch_assoc();

// Get monthly earnings for chart
$monthly_earnings = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as trip_count,
        SUM(actual_fare) as earnings
    FROM trips 
    WHERE driver_id = $driver_id AND status = 'completed'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-history"></i> Trip History</h1>
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
            <div class="stat-icon bg-warning">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-details">
                <h3>Total Earnings</h3>
                <p>₹<?php echo number_format($stats['total_earnings'] ?: 0, 2); ?></p>
            </div>
        </div>
    </div>

    <!-- Earnings Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Earnings Overview</h3>
        </div>
        <div class="card-body">
            <canvas id="earningsChart" style="width: 100%; height: 300px;"></canvas>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filter History</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
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
            <div class="card-tools">
                <button onclick="exportToCSV()" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export CSV
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Passenger</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Distance</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($trips->num_rows > 0): ?>
                            <?php while($trip = $trips->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $trip['id']; ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($trip['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo $trip['passenger_name']; ?></strong><br>
                                    <small><?php echo $trip['passenger_phone']; ?></small>
                                </td>
                                <td><?php echo substr($trip['pickup_location'], 0, 30); ?>...</td>
                                <td><?php echo substr($trip['dropoff_location'], 0, 30); ?>...</td>
                                <td><?php echo number_format($trip['distance_km'], 1); ?> km</td>
                                <td>
                                    <strong class="text-success">₹<?php echo number_format($trip['actual_fare'] ?: $trip['estimated_fare'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="status-pill status-<?php echo $trip['status']; ?>">
                                        <?php echo ucfirst($trip['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No trips found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Earnings Chart
var ctx = document.getElementById('earningsChart').getContext('2d');
var chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php 
            $months = [];
            $earnings = [];
            while($row = $monthly_earnings->fetch_assoc()) {
                $months[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                $earnings[] = $row['earnings'];
            }
            echo implode(',', array_reverse($months));
        ?>],
        datasets: [{
            label: 'Earnings (₹)',
            data: [<?php echo implode(',', array_reverse($earnings)); ?>],
            backgroundColor: 'rgba(46, 204, 113, 0.2)',
            borderColor: 'rgba(46, 204, 113, 1)',
            borderWidth: 2,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value;
                    }
                }
            }
        }
    }
});

// Export to CSV
function exportToCSV() {
    var rows = [];
    
    // Add headers
    rows.push(['Trip ID', 'Date', 'Passenger', 'Phone', 'Pickup', 'Dropoff', 'Distance (km)', 'Fare (₹)', 'Status']);
    
    // Add data
    <?php 
    $trips->data_seek(0);
    while($trip = $trips->fetch_assoc()): 
    ?>
    rows.push([
        '<?php echo $trip['id']; ?>',
        '<?php echo date('Y-m-d H:i', strtotime($trip['created_at'])); ?>',
        '<?php echo addslashes($trip['passenger_name']); ?>',
        '<?php echo $trip['passenger_phone']; ?>',
        '<?php echo addslashes($trip['pickup_location']); ?>',
        '<?php echo addslashes($trip['dropoff_location']); ?>',
        '<?php echo $trip['distance_km']; ?>',
        '<?php echo $trip['actual_fare'] ?: $trip['estimated_fare']; ?>',
        '<?php echo $trip['status']; ?>'
    ]);
    <?php endwhile; ?>
    
    // Convert to CSV
    var csvContent = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    
    // Download
    var blob = new Blob([csvContent], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'trip_history.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<?php include '../includes/footer.php'; ?>