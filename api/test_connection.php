<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test file to check if API is working
require_once dirname(__DIR__) . '/includes/config.php';

$response = [
    'success' => true,
    'message' => 'API connection successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_NAME'],
    'session_status' => session_status() == PHP_SESSION_ACTIVE ? 'active' : 'inactive',
    'database' => [
        'connected' => $conn ? true : false,
        'database' => DB_NAME,
        'host' => DB_HOST
    ]
];

if ($conn) {
    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        $response['database']['users_count'] = $row['count'];
    } else {
        $response['database']['error'] = $conn->error;
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>