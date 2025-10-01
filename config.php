<?php
// File: config.php

// Database credentials
define('DB_SERVER', 'hgwg84ws8wokgs8os4040kso');
// We will use a placeholder user 'yucca_user' with the normal password
define('DB_USERNAME', 'yucca_user');
define('DB_PASSWORD', 'hD6cUUBy4eWFDEDXpPGPCaK0ZHS5zzI2U5xD5QyVH7hwOQpvwfMlUN1wNrxOoWPP');
define('DB_NAME', 'yucca_club');

// Start session
session_start();

// Function to establish database connection
function db_connect() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        // Exit script and display a user-friendly error message
        die("Database connection failed. Please check the credentials and ensure the database is running.");
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