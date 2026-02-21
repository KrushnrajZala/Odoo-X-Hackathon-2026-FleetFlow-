<?php
include 'includes/config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Register';
include 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate CSRF token if not exists
    $csrf_token = generateCSRFToken();
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token!";
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $role = sanitize($_POST['role']);
        
        // Validate inputs
        if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($phone) || empty($role)) {
            $error = "All fields are required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters!";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = "Invalid phone number! Must be 10 digits.";
        } else {
            // Check if username or email exists
            $check = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
            
            if ($check->num_rows > 0) {
                $error = "Username or email already exists!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Begin transaction
                $conn->query("BEGIN");
                
                // Insert user
                $sql = "INSERT INTO users (username, email, password, full_name, phone, role, status) 
                        VALUES ('$username', '$email', '$hashed_password', '$full_name', '$phone', '$role', 'active')";
                
                if ($conn->query($sql)) {
                    $user_id = $conn->insert_id;
                    
                    // If driver, create driver details
                    if ($role == 'driver') {
                        $license_number = sanitize($_POST['license_number']);
                        $license_expiry = $_POST['license_expiry'];
                        $vehicle_type = sanitize($_POST['vehicle_type']);
                        $experience = intval($_POST['experience']);
                        
                        // Validate driver fields
                        if (empty($license_number) || empty($license_expiry) || empty($vehicle_type)) {
                            $error = "All driver fields are required!";
                            $conn->query("ROLLBACK");
                        } else {
                            $driver_sql = "INSERT INTO driver_details (user_id, license_number, license_expiry, vehicle_type, experience_years, current_status) 
                                          VALUES ($user_id, '$license_number', '$license_expiry', '$vehicle_type', $experience, 'off_duty')";
                            
                            if ($conn->query($driver_sql)) {
                                $conn->query("COMMIT");
                                $success = "Registration successful! You can now login.";
                                
                                // Clear POST data
                                $_POST = array();
                            } else {
                                $conn->query("ROLLBACK");
                                $error = "Error creating driver profile: " . $conn->error;
                            }
                        }
                    } else {
                        $conn->query("COMMIT");
                        $success = "Registration successful! You can now login.";
                        
                        // Clear POST data
                        $_POST = array();
                    }
                } else {
                    $conn->query("ROLLBACK");
                    $error = "Registration failed: " . $conn->error;
                }
            }
        }
    }
}

// Generate new CSRF token
$csrf_token = generateCSRFToken();
?>

<style>
.register-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
}

.register-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    animation: slideIn 0.5s ease;
}

.register-header {
    text-align: center;
    margin-bottom: 2rem;
}

.register-header .logo-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), #ff8c5a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 2.5rem;
}

.register-header h2 {
    color: #333;
    margin-bottom: 0.5rem;
}

.register-header p {
    color: #666;
}

.role-selector {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.role-option {
    flex: 1;
    text-align: center;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

.role-option:hover {
    border-color: var(--primary-color);
    background: #fff5f0;
}

.role-option.selected {
    border-color: var(--primary-color);
    background: #fff5f0;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255,107,53,0.2);
}

.role-option i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.role-option span {
    display: block;
    font-weight: 600;
    color: #333;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
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

.btn-register {
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

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(255,107,53,0.3);
}

.register-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.register-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.alert {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: slideIn 0.3s;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .role-selector {
        flex-direction: column;
    }
}
</style>

<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <div class="logo-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Create Account</h2>
            <p>Join FleetFlow today</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
                <script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm" onsubmit="return validateForm()">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="role-selector">
                <div class="role-option" onclick="selectRole('passenger')" id="role-passenger">
                    <i class="fas fa-user"></i>
                    <span>Passenger</span>
                </div>
                <div class="role-option" onclick="selectRole('driver')" id="role-driver">
                    <i class="fas fa-truck"></i>
                    <span>Driver</span>
                </div>
            </div>
            
            <input type="hidden" name="role" id="selectedRole" value="passenger">
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="10 digit mobile number" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <small class="form-text">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            
            <!-- Driver specific fields -->
            <div id="driverFields" style="display: none;">
                <h4 style="margin: 1rem 0; color: var(--primary-color);">Driver Information</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> License Number</label>
                        <input type="text" name="license_number" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> License Expiry</label>
                        <input type="date" name="license_expiry" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-car"></i> Vehicle Type</label>
                        <select name="vehicle_type" class="form-control">
                            <option value="">Select Vehicle Type</option>
                            <option value="car">Car</option>
                            <option value="van">Van</option>
                            <option value="truck">Truck</option>
                            <option value="bike">Bike</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Experience (Years)</label>
                        <input type="number" name="experience" class="form-control" min="0" value="0">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-register" id="registerBtn">
                <i class="fas fa-user-plus"></i> Register
            </button>
            
            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<script>
// Select role function
function selectRole(role) {
    document.querySelectorAll('.role-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    document.getElementById(`role-${role}`).classList.add('selected');
    document.getElementById('selectedRole').value = role;
    
    const driverFields = document.getElementById('driverFields');
    const driverInputs = driverFields.querySelectorAll('input, select');
    
    if (role === 'driver') {
        driverFields.style.display = 'block';
        driverInputs.forEach(input => input.required = true);
    } else {
        driverFields.style.display = 'none';
        driverInputs.forEach(input => {
            input.required = false;
            input.value = '';
        });
    }
}

// Validate form
function validateForm() {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
    }
    
    // Disable button to prevent double submission
    document.getElementById('registerBtn').disabled = true;
    document.getElementById('registerBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
    
    return true;
}

// Set default role
document.addEventListener('DOMContentLoaded', function() {
    selectRole('passenger');
});
</script>

<?php include 'includes/footer.php'; ?>