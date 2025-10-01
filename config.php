<?php
// File: config.php

// Database credentials
define('DB_SERVER', 'hgwg84ws8wokgs8os4040kso');
// FIX: Temporarily using root to bypass host permission issues
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'FSqUNROiUNj96cGLvHnASl5OgU11vaKG1c0C2SgykUSIRc67noNVA0djo5XwRK2W');
define('DB_NAME', 'yucca_club');

// Start session
session_start();

// Function to establish database connection
function db_connect() {
    // The password is wrapped in a SensitiveParameterValue to enhance security
    $conn = new mysqli(DB_SERVER, DB_USERNAME, new SensitiveParameterValue(DB_PASSWORD), DB_NAME);
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
