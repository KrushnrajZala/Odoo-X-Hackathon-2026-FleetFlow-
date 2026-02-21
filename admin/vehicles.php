<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Vehicle Management';
include '../includes/header.php';

// Handle vehicle status update
if (isset($_POST['update_status'])) {
    $vehicle_id = intval($_POST['vehicle_id']);
    $status = sanitize($_POST['status']);
    
    $sql = "UPDATE vehicles SET status = '$status' WHERE id = $vehicle_id";
    if ($conn->query($sql)) {
        $success = "Vehicle status updated successfully!";
    } else {
        $error = "Error updating vehicle: " . $conn->error;
    }
}

// Handle delete vehicle
if (isset($_GET['delete'])) {
    $vehicle_id = intval($_GET['delete']);
    
    // Check if vehicle has any trips
    $check = $conn->query("SELECT id FROM trips WHERE vehicle_id = $vehicle_id LIMIT 1");
    if ($check->num_rows > 0) {
        $error = "Cannot delete vehicle with existing trips. Mark as retired instead.";
    } else {
        $sql = "DELETE FROM vehicles WHERE id = $vehicle_id";
        if ($conn->query($sql)) {
            $success = "Vehicle deleted successfully!";
        } else {
            $error = "Error deleting vehicle: " . $conn->error;
        }
    }
}

// Handle add vehicle
if (isset($_POST['add_vehicle'])) {
    $vehicle_name = sanitize($_POST['vehicle_name']);
    $license_plate = sanitize($_POST['license_plate']);
    $vehicle_type = sanitize($_POST['vehicle_type']);
    $max_capacity = floatval($_POST['max_capacity']);
    $current_odometer = intval($_POST['current_odometer']);
    $fuel_type = sanitize($_POST['fuel_type']);
    
    $sql = "INSERT INTO vehicles (vehicle_name, license_plate, vehicle_type, max_capacity_kg, current_odometer, fuel_type) 
            VALUES ('$vehicle_name', '$license_plate', '$vehicle_type', $max_capacity, $current_odometer, '$fuel_type')";
    
    if ($conn->query($sql)) {
        $success = "Vehicle added successfully!";
    } else {
        $error = "Error adding vehicle: " . $conn->error;
    }
}

// Get all vehicles with stats
$vehicles = $conn->query("
    SELECT v.*, 
           COUNT(DISTINCT t.id) as total_trips,
           SUM(t.actual_fare) as total_revenue,
           (SELECT COUNT(*) FROM trips WHERE vehicle_id = v.id AND status = 'completed') as completed_trips
    FROM vehicles v
    LEFT JOIN trips t ON v.id = t.vehicle_id
    GROUP BY v.id
    ORDER BY v.created_at DESC
");
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-truck"></i> Vehicle Management</h1>
        <button class="btn btn-primary" onclick="openAddVehicleModal()">
            <i class="fas fa-plus"></i> Add New Vehicle
        </button>
    </div>

    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-details">
                <h3>Total Vehicles</h3>
                <p><?php echo $vehicles->num_rows; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3>Available</h3>
                <p><?php echo $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status='available'")->fetch_assoc()['count']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-road"></i>
            </div>
            <div class="stat-details">
                <h3>On Trip</h3>
                <p><?php echo $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status='on_trip'")->fetch_assoc()['count']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-details">
                <h3>In Shop</h3>
                <p><?php echo $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status='in_shop'")->fetch_assoc()['count']; ?></p>
            </div>
        </div>
    </div>

    <!-- Vehicles Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Vehicle Registry</h3>
            <div class="card-tools">
                <input type="text" id="searchVehicle" placeholder="Search vehicles..." class="form-control">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="vehiclesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vehicle</th>
                            <th>License Plate</th>
                            <th>Type</th>
                            <th>Capacity (kg)</th>
                            <th>Odometer</th>
                            <th>Status</th>
                            <th>Total Trips</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($vehicle = $vehicles->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $vehicle['id']; ?></td>
                            <td><?php echo $vehicle['vehicle_name']; ?></td>
                            <td><strong><?php echo $vehicle['license_plate']; ?></strong></td>
                            <td><?php echo ucfirst($vehicle['vehicle_type']); ?></td>
                            <td><?php echo number_format($vehicle['max_capacity_kg']); ?> kg</td>
                            <td><?php echo number_format($vehicle['current_odometer']); ?> km</td>
                            <td>
                                <span class="status-pill status-<?php echo $vehicle['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $vehicle['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $vehicle['total_trips'] ?: 0; ?></td>
                            <td>₹<?php echo number_format($vehicle['total_revenue'] ?: 0, 2); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button onclick="editVehicle(<?php echo $vehicle['id']; ?>)" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="updateStatus(<?php echo $vehicle['id']; ?>)" class="btn btn-sm btn-warning">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <a href="?delete=<?php echo $vehicle['id']; ?>" 
                                       class="btn btn-sm btn-danger confirm-delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
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

<!-- Add Vehicle Modal -->
<div id="addVehicleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Vehicle</h3>
            <span class="close">&times;</span>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Vehicle Name/Model</label>
                    <input type="text" name="vehicle_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>License Plate</label>
                    <input type="text" name="license_plate" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <select name="vehicle_type" class="form-control" required>
                        <option value="truck">Truck</option>
                        <option value="van">Van</option>
                        <option value="car">Car</option>
                        <option value="bike">Bike</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Max Capacity (kg)</label>
                    <input type="number" name="max_capacity" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Current Odometer (km)</label>
                    <input type="number" name="current_odometer" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type" class="form-control" required>
                        <option value="petrol">Petrol</option>
                        <option value="diesel">Diesel</option>
                        <option value="electric">Electric</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="add_vehicle" class="btn btn-primary">Add Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Vehicle Status</h3>
            <span class="close">&times;</span>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="vehicle_id" id="status_vehicle_id">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="on_trip">On Trip</option>
                        <option value="in_shop">In Shop</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openAddVehicleModal() {
    document.getElementById('addVehicleModal').style.display = 'block';
}

function updateStatus(vehicleId) {
    document.getElementById('status_vehicle_id').value = vehicleId;
    document.getElementById('statusModal').style.display = 'block';
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

// Close modal when clicking on X or outside
document.querySelectorAll('.close').forEach(btn => {
    btn.onclick = closeModal;
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal();
    }
}

// Search functionality
document.getElementById('searchVehicle').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#vehiclesTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});

// Edit vehicle function
function editVehicle(vehicleId) {
    // Implement edit functionality
    window.location.href = `edit_vehicle.php?id=${vehicleId}`;
}
</script>

<?php include '../includes/footer.php'; ?>