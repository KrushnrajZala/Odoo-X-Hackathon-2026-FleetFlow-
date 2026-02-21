<?php
include '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

// Get statistics
$stats = [
    'total_drivers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role='driver'")->fetch_assoc()['count'],
    'total_passengers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role='passenger'")->fetch_assoc()['count'],
    'active_trips' => $conn->query("SELECT COUNT(*) as count FROM trips WHERE status IN ('accepted', 'started')")->fetch_assoc()['count'],
    'available_vehicles' => $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status='available'")->fetch_assoc()['count']
];

// Get recent trips
$recent_trips = $conn->query("
    SELECT t.*, 
           u1.full_name as driver_name,
           u2.full_name as passenger_name,
           v.vehicle_name
    FROM trips t
    LEFT JOIN users u1 ON t.driver_id = u1.id
    LEFT JOIN users u2 ON t.passenger_id = u2.id
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FleetFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">🚀 FleetFlow Admin</a>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="add_driver.php"><i class="fas fa-user-plus"></i> Add Driver</a>
            <a href="vehicles.php"><i class="fas fa-truck"></i> Vehicles</a>
            <a href="trips.php"><i class="fas fa-list"></i> Trips</a>
            <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div style="padding: 2rem;">
        <h1 style="margin-bottom: 2rem;">Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
        
        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary-color);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Drivers</h3>
                    <span class="stat-number"><?php echo $stats['total_drivers']; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--success-color);">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Passengers</h3>
                    <span class="stat-number"><?php echo $stats['total_passengers']; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--warning-color);">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Trips</h3>
                    <span class="stat-number"><?php echo $stats['active_trips']; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--secondary-color);">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-info">
                    <h3>Available Vehicles</h3>
                    <span class="stat-number"><?php echo $stats['available_vehicles']; ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Trips Table -->
        <div class="table-container" style="margin-top: 2rem;">
            <h2 style="margin-bottom: 1rem;">Recent Trips</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Passenger</th>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Pickup</th>
                        <th>Dropoff</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($trip = $recent_trips->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $trip['id']; ?></td>
                        <td><?php echo $trip['passenger_name']; ?></td>
                        <td><?php echo $trip['driver_name'] ?? 'Unassigned'; ?></td>
                        <td><?php echo $trip['vehicle_name'] ?? 'N/A'; ?></td>
                        <td>
                            <span class="status-pill status-<?php echo $trip['status']; ?>">
                                <?php echo ucfirst($trip['status']); ?>
                            </span>
                        </td>
                        <td><?php echo substr($trip['pickup_location'], 0, 30); ?>...</td>
                        <td><?php echo substr($trip['dropoff_location'], 0, 30); ?>...</td>
                        <td>
                            <a href="view_trip.php?id=<?php echo $trip['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 1rem;">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>