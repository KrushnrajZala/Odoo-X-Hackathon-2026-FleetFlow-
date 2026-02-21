<?php
include '../includes/config.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $license_number = sanitize($_POST['license_number']);
    $license_expiry = $_POST['license_expiry'];
    $vehicle_type = sanitize($_POST['vehicle_type']);
    $experience_years = intval($_POST['experience_years']);
    
    // Insert user
    $conn->query("BEGIN");
    
    $user_sql = "INSERT INTO users (username, email, password, full_name, phone, role) 
                 VALUES ('$username', '$email', '$password', '$full_name', '$phone', 'driver')";
    
    if ($conn->query($user_sql)) {
        $user_id = $conn->insert_id;
        
        // Insert driver details
        $driver_sql = "INSERT INTO driver_details (user_id, license_number, license_expiry, vehicle_type, experience_years) 
                       VALUES ($user_id, '$license_number', '$license_expiry', '$vehicle_type', $experience_years)";
        
        if ($conn->query($driver_sql)) {
            $conn->query("COMMIT");
            $success = "Driver added successfully!";
        } else {
            $conn->query("ROLLBACK");
            $error = "Error adding driver details: " . $conn->error;
        }
    } else {
        $conn->query("ROLLBACK");
        $error = "Error adding user: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Driver - FleetFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">🚀 FleetFlow Admin</a>
        <div class="navbar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_driver.php">Add Driver</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </nav>

    <div class="form-container">
        <h2 style="text-align: center; margin-bottom: 2rem;">Add New Driver</h2>
        
        <?php if(isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>License Number</label>
                <input type="text" name="license_number" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>License Expiry Date</label>
                <input type="date" name="license_expiry" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Vehicle Type</label>
                <select name="vehicle_type" class="form-control" required>
                    <option value="car">Car</option>
                    <option value="van">Van</option>
                    <option value="truck">Truck</option>
                    <option value="bike">Bike</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Experience (Years)</label>
                <input type="number" name="experience_years" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Add Driver</button>
        </form>
    </div>
</body>
</html>