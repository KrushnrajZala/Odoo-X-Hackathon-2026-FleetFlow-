<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Trip Management';
include '../includes/header.php';

// Filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "
    SELECT t.*, 
           u1.full_name as passenger_name,
           u1.phone as passenger_phone,
           u2.full_name as driver_name,
           u2.phone as driver_phone,
           v.vehicle_name,
           v.license_plate
    FROM trips t
    LEFT JOIN users u1 ON t.passenger_id = u1.id
    LEFT JOIN users u2 ON t.driver_id = u2.id
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    WHERE 1=1
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
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM trips")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM trips WHERE status='completed'")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM trips WHERE status='pending'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM trips WHERE status='cancelled'")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(actual_fare) as total FROM trips WHERE status='completed'")->fetch_assoc()['total']
];
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-list"></i> Trip Management</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-route"></i>
            </div>
            <div class="stat-details">
                <h3>Total Trips</h3>
                <p><?php echo $stats['total']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3>Completed</h3>
                <p><?php echo $stats['completed']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h3>Pending</h3>
                <p><?php echo $stats['pending']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-details">
                <h3>Cancelled</h3>
                <p><?php echo $stats['cancelled']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-details">
                <h3>Total Revenue</h3>
                <p>₹<?php echo number_format($stats['total_revenue'] ?: 0, 2); ?></p>
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
                        <a href="trips.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Trips Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> All Trips</h3>
            <div class="card-tools">
                <button onclick="exportToExcel()" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="tripsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date/Time</th>
                            <th>Passenger</th>
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
                        <?php while($trip = $trips->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $trip['id']; ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($trip['created_at'])); ?></td>
                            <td>
                                <strong><?php echo $trip['passenger_name']; ?></strong><br>
                                <small><?php echo $trip['passenger_phone']; ?></small>
                            </td>
                            <td>
                                <?php if($trip['driver_name']): ?>
                                    <strong><?php echo $trip['driver_name']; ?></strong><br>
                                    <small><?php echo $trip['driver_phone']; ?></small>
                                <?php else: ?>
                                    <span class="badge badge-warning">Unassigned</span>
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
                            <td><?php echo substr($trip['pickup_location'], 0, 30); ?>...</td>
                            <td><?php echo substr($trip['dropoff_location'], 0, 30); ?>...</td>
                            <td><?php echo $trip['distance_km'] ? number_format($trip['distance_km'], 1) . ' km' : 'N/A'; ?></td>
                            <td>
                                <strong>₹<?php echo number_format($trip['actual_fare'] ?: $trip['estimated_fare'] ?: 0, 2); ?></strong>
                            </td>
                            <td>
                                <span class="status-pill status-<?php echo $trip['status']; ?>">
                                    <?php echo ucfirst($trip['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_trip.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($trip['status'] == 'pending'): ?>
                                        <a href="assign_driver.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-user-plus"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Export to Excel function
function exportToExcel() {
    const table = document.getElementById('tripsTable');
    const rows = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        if (th.textContent.trim() !== 'Actions') {
            headers.push(th.textContent.trim());
        }
    });
    rows.push(headers);
    
    // Get data
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach((td, index) => {
            if (index < headers.length) {
                // Clean up the text (remove HTML)
                row.push(td.textContent.trim());
            }
        });
        rows.push(row);
    });
    
    // Create CSV
    const csvContent = rows.map(row => row.join(',')).join('\n');
    
    // Download
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'trips_export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Auto-refresh for new trips (every 60 seconds)
setTimeout(function() {
    location.reload();
}, 60000);
</script>

<?php include '../includes/footer.php'; ?>