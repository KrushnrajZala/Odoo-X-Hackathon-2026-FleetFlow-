<?php
// Check if user is logged in for protected pages
function isPublicPage() {
    $public_pages = ['index.php', 'login.php', 'register.php', 'forgot_password.php'];
    $current_page = basename($_SERVER['PHP_SELF']);
    return in_array($current_page, $public_pages);
}

// Get current user data if logged in
$current_user = null;
if (isLoggedIn()) {
    $current_user = getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/favicon.ico">
    
    <!-- Meta tags -->
    <meta name="description" content="FleetFlow - Modern Fleet Management System">
    <meta name="keywords" content="fleet management, logistics, vehicle tracking, driver management">
</head>
<body>
    <?php if(!isPublicPage()): ?>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo SITE_URL; ?>" class="navbar-brand">
                <i class="fas fa-truck"></i> FleetFlow
            </a>
            
            <button class="navbar-toggle" id="navbarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-menu" id="navbarMenu">
                <?php if(isset($_SESSION['user_role'])): ?>
                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <a href="<?php echo SITE_URL; ?>admin/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="<?php echo SITE_URL; ?>admin/add_driver.php">
                            <i class="fas fa-user-plus"></i> Add Driver
                        </a>
                        <a href="<?php echo SITE_URL; ?>admin/vehicles.php">
                            <i class="fas fa-truck"></i> Vehicles
                        </a>
                        <a href="<?php echo SITE_URL; ?>admin/trips.php">
                            <i class="fas fa-list"></i> Trips
                        </a>
                        <a href="<?php echo SITE_URL; ?>admin/reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        
                    <?php elseif($_SESSION['user_role'] == 'driver'): ?>
                        <a href="<?php echo SITE_URL; ?>driver/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="<?php echo SITE_URL; ?>driver/active_ride.php">
                            <i class="fas fa-road"></i> Active Ride
                        </a>
                        <a href="<?php echo SITE_URL; ?>driver/history.php">
                            <i class="fas fa-history"></i> Trip History
                        </a>
                        <a href="<?php echo SITE_URL; ?>driver/earnings.php">
                            <i class="fas fa-money-bill"></i> Earnings
                        </a>
                        
                    <?php elseif($_SESSION['user_role'] == 'passenger'): ?>
                        <a href="<?php echo SITE_URL; ?>passenger/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="<?php echo SITE_URL; ?>passenger/book_ride.php">
                            <i class="fas fa-taxi"></i> Book Ride
                        </a>
                        <a href="<?php echo SITE_URL; ?>passenger/history.php">
                            <i class="fas fa-history"></i> My Trips
                        </a>
                    <?php endif; ?>
                    
                    <div class="nav-user-dropdown">
                        <a href="#" class="nav-user-link" id="userDropdownToggle">
                            <?php if(!empty($current_user['profile_image'])): ?>
                                <img src="<?php echo SITE_URL . $current_user['profile_image']; ?>" alt="Profile" class="profile-thumb">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                            <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="<?php echo SITE_URL; ?>profile.php">
                                <i class="fas fa-id-card"></i> My Profile
                            </a>
                            <a href="<?php echo SITE_URL; ?>settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <?php if($_SESSION['user_role'] == 'driver'): ?>
                            <a href="<?php echo SITE_URL; ?>driver/documents.php">
                                <i class="fas fa-file-alt"></i> Documents
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo SITE_URL; ?>logout.php" class="text-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="main-content">