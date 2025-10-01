<?php
// File: config.php

// Database credentials
// Using environment variables for security
define('DB_SERVER', getenv('DB_SERVER_ENV'));
define('DB_USERNAME', getenv('DB_USERNAME_ENV')); 
define('DB_PASSWORD', getenv('DB_PASSWORD_ENV'));
define('DB_NAME', getenv('DB_NAME_ENV'));

// Stripe Credentials
// Using environment variables for security
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY_ENV'));
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY_ENV'));

// Start session
session_start();

// Function to establish database connection
function db_connect() {
    // FIX: Removed SensitiveParameterValue wrapper. mysqli::__construct requires a string for the password.
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        // Include connect_error for better debugging
        die("Database connection failed. Please check the credentials and ensure the database is running: " . $conn->connect_error);
    }
    return $conn;
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Global variable for storing errors
$error = '';
?>
