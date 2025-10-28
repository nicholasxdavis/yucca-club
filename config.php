<?php
// File: config.php

// Database credentials - MariaDB
// Get from environment variables
define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'default');

// GitHub configuration defaults
define('GITHUB_OWNER_DEFAULT', 'nicholasxdavis');
define('GITHUB_REPO_DEFAULT', 'yucca-club');
define('GITHUB_FOLDER_DEFAULT', 'saved-imgs');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to establish database connection
function db_connect() {
    static $connection = null;
    
    // Return existing connection if available
    if ($connection !== null && $connection instanceof mysqli && !$connection->connect_error) {
        return $connection;
    }
    
    $conn = null;
    $error = '';
    $attempts = [
        DB_SERVER,
        DB_SERVER . '.internal',
        explode('.', DB_SERVER)[0]
    ];
    
    foreach ($attempts as $hostname) {
        $conn = @new mysqli($hostname, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (!$conn->connect_error) {
            $conn->set_charset('utf8mb4');
            $connection = $conn; // Store for reuse
            return $conn;
        }
        $error = $conn->connect_error;
    }
    
    // If all attempts failed, show helpful error
    error_log("Database connection failed. Last error: $error");
    die("<h1>Database Connection Failed</h1>
        <p>Unable to connect to database server.</p>
        <p><strong>Attempted hostnames:</strong></p>
        <ul>" . 
        implode('', array_map(function($h) { return "<li>$h</li>"; }, $attempts)) . 
        "</ul>
        <p><strong>Last error:</strong> $error</p>
        <p><a href='test_connection.php'>Run connection test</a></p>");
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    // Check if user has admin role in database OR is the hardcoded admin email
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    // Also check for hardcoded admin email (backup)
    if (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'nic@blacnova.net') {
        return true;
    }
    return false;
}

// Function to check if user is editor
function is_editor() {
    if (!isset($_SESSION['user_id'])) return false;
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['editor', 'admin']);
}

// Function to check if user has specific role
function has_role($role) {
    if (!isset($_SESSION['user_id'])) return false;
    if ($role === 'admin') return isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'nic@blacnova.net';
    if ($role === 'editor') return is_editor();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to require authentication
function require_auth() {
    if (!is_logged_in()) {
        header('Location: ../index.php');
        exit;
    }
}

// Function to require admin
function require_admin() {
    if (!is_admin()) {
        header('Location: ../index.php');
        exit;
    }
}

// Function to require editor or admin
function require_editor() {
    if (!is_editor() && !is_admin()) {
        header('Location: ../index.php');
        exit;
    }
}

// Global variable for storing errors
$error = '';
?>