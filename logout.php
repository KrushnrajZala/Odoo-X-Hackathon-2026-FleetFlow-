<?php
// Start session
session_start();

// Get user role before destroying session (for logging purposes)
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'unknown';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'unknown';

// Optional: Log logout activity (if you have a logs table)
// You can add this to your database if needed
/*
include 'includes/config.php';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
if ($user_id) {
    $conn->query("INSERT INTO user_logs (user_id, action, timestamp) VALUES ($user_id, 'logout', NOW())");
}
*/

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remember me cookies if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Clear any other custom cookies
$cookies_to_clear = ['user_preferences', 'last_location', 'search_history'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// Start new session for message
session_start();
$_SESSION['success_message'] = "You have been successfully logged out. Come back soon!";

// Redirect based on role (for logging purposes only, actual redirect is to login)
// You can customize the redirect message based on role
switch($user_role) {
    case 'admin':
        $_SESSION['logout_message'] = "Admin logged out successfully. All systems are secure.";
        break;
    case 'driver':
        $_SESSION['logout_message'] = "Driver logged out. Have a great day and drive safely!";
        break;
    case 'passenger':
        $_SESSION['logout_message'] = "Passenger logged out. Thank you for riding with FleetFlow!";
        break;
    default:
        $_SESSION['logout_message'] = "Logged out successfully. See you again!";
}

// Redirect to login page
header("Location: login.php");
exit();
?>