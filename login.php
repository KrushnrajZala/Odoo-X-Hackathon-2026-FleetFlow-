<?php
include 'includes/config.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    switch($_SESSION['user_role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'driver':
            redirect('driver/dashboard.php');
            break;
        case 'passenger':
            redirect('passenger/dashboard.php');
            break;
    }
}

$page_title = 'Login';
include 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']); // Get selected role from dropdown
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required!";
    } else {
        // Query to check user with specific role
        $sql = "SELECT * FROM users WHERE email = '$email' AND role = '$role' AND status = 'active'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Update last login
                $conn->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");
                
                // Create success message
                $success = "Login successful! Redirecting...";
                
                // Redirect based on role
                switch($user['role']) {
                    case 'admin':
                        echo "<script>setTimeout(function(){ window.location.href = 'admin/dashboard.php'; }, 1500);</script>";
                        break;
                    case 'driver':
                        echo "<script>setTimeout(function(){ window.location.href = 'driver/dashboard.php'; }, 1500);</script>";
                        break;
                    case 'passenger':
                        echo "<script>setTimeout(function(){ window.location.href = 'passenger/dashboard.php'; }, 1500);</script>";
                        break;
                }
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "No account found with this email and role combination!";
        }
    }
}
?>

<style>
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
}

.login-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    animation: slideIn 0.5s ease;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-header .logo-icon {
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
    animation: pulse 2s infinite;
}

.login-header h2 {
    color: #333;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.login-header p {
    color: #666;
    font-size: 0.95rem;
}

.role-selector {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    justify-content: center;
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

.role-option small {
    display: block;
    color: #666;
    font-size: 0.8rem;
    margin-top: 0.3rem;
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

.password-input {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #999;
}

.toggle-password:hover {
    color: var(--primary-color);
}

.btn-login {
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

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(255,107,53,0.3);
}

.btn-login:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.login-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.login-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.login-footer a:hover {
    text-decoration: underline;
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

.alert i {
    font-size: 1.2rem;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.remember-me input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.remember-me label {
    margin-bottom: 0;
    cursor: pointer;
}

.forgot-password {
    text-align: right;
    margin-bottom: 1.5rem;
}

.forgot-password a {
    color: #666;
    text-decoration: none;
    font-size: 0.9rem;
}

.forgot-password a:hover {
    color: var(--primary-color);
}

.demo-credentials {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    margin-top: 1.5rem;
}

.demo-credentials h4 {
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.demo-credentials p {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.demo-credentials code {
    background: #e9ecef;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    color: var(--primary-color);
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

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}
</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="logo-icon">
                <i class="fas fa-truck"></i>
            </div>
            <h2>Welcome Back!</h2>
            <p>Sign in to continue to FleetFlow</p>
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
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" onsubmit="return validateForm()">
            <!-- Hidden role input that gets set by the role options -->
            <input type="hidden" name="role" id="selectedRole" value="">
            
            <div class="role-selector">
                <div class="role-option" onclick="selectRole('admin')" id="role-admin">
                    <i class="fas fa-user-cog"></i>
                    <span>Admin</span>
                    <small>Manage System</small>
                </div>
                <div class="role-option" onclick="selectRole('driver')" id="role-driver">
                    <i class="fas fa-truck"></i>
                    <span>Driver</span>
                    <small>Take Rides</small>
                </div>
                <div class="role-option" onclick="selectRole('passenger')" id="role-passenger">
                    <i class="fas fa-user"></i>
                    <span>Passenger</span>
                    <small>Book Rides</small>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-input">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>

            <!-- Demo credentials for testing -->
            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Demo Credentials:</h4>
                <p><strong>Admin:</strong> <code>admin@fleetflow.com</code> / <code>zala123</code></p>
                <p><strong>Driver:</strong> <code>raju@gmail.com</code> / <code>zala123</code></p>
                <p><strong>Passenger:</strong> <code>krushnraj@gmail.com</code> / <code>zala123</code></p>
            </div>
        </form>
    </div>
</div>

<script>
// Select role function
function selectRole(role) {
    // Remove selected class from all options
    document.querySelectorAll('.role-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    document.getElementById(`role-${role}`).classList.add('selected');
    
    // Set hidden input value
    document.getElementById('selectedRole').value = role;
}

// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.querySelector('.toggle-password');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Validate form before submission
function validateForm() {
    const role = document.getElementById('selectedRole').value;
    const email = document.querySelector('input[name="email"]').value;
    const password = document.querySelector('input[name="password"]').value;
    
    if (!role) {
        alert('Please select a role (Admin, Driver, or Passenger)');
        return false;
    }
    
    if (!email || !password) {
        alert('Please fill in all fields');
        return false;
    }
    
    // Disable button to prevent double submission
    document.getElementById('loginBtn').disabled = true;
    document.getElementById('loginBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
    
    return true;
}

// Auto-select last used role from localStorage (optional)
document.addEventListener('DOMContentLoaded', function() {
    const lastRole = localStorage.getItem('lastRole');
    if (lastRole) {
        selectRole(lastRole);
    }
    
    // Save selected role to localStorage when form is submitted
    document.getElementById('loginForm').addEventListener('submit', function() {
        const selectedRole = document.getElementById('selectedRole').value;
        if (selectedRole) {
            localStorage.setItem('lastRole', selectedRole);
        }
    });
});

// Quick fill demo credentials (for testing convenience)
function fillDemoCredentials(role) {
    const credentials = {
        admin: { email: 'admin@fleetflow.com', password: 'zala123' },
        driver: { email: 'raju@gmail.com', password: 'zala123' },
        passenger: { email: 'krushnraj@gmail.com', password: 'zala123' }
    };
    
    if (credentials[role]) {
        document.querySelector('input[name="email"]').value = credentials[role].email;
        document.querySelector('input[name="password"]').value = credentials[role].password;
        selectRole(role);
    }
}

// Add click handlers to demo credentials
document.querySelectorAll('.demo-credentials p').forEach((p, index) => {
    p.style.cursor = 'pointer';
    p.addEventListener('click', function() {
        if (index === 0) fillDemoCredentials('admin');
        else if (index === 1) fillDemoCredentials('driver');
        else if (index === 2) fillDemoCredentials('passenger');
    });
});
</script>

<?php include 'includes/footer.php'; ?>