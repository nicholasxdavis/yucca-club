<?php
/**
 * Database Initialization Script
 * Creates all necessary tables for Yucca Club
 * Run this once to set up the database structure
 */

require_once 'config.php';

// Connect to database (without selecting a specific database first to create it if needed)
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
if ($conn->query($sql) === TRUE) {
    echo "Database '" . DB_NAME . "' created or already exists.\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Select the database
$conn->select_db(DB_NAME);
$conn->set_charset('utf8mb4');

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'editor', 'admin') DEFAULT 'user',
    `remember_token` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create stories table
$sql = "CREATE TABLE IF NOT EXISTS `stories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `excerpt` TEXT,
    `content` LONGTEXT,
    `featured_image` VARCHAR(255),
    `category` VARCHAR(100),
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'stories' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create guides table
$sql = "CREATE TABLE IF NOT EXISTS `guides` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `excerpt` TEXT,
    `content` LONGTEXT,
    `featured_image` VARCHAR(255),
    `category` VARCHAR(100),
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'guides' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create events table
$sql = "CREATE TABLE IF NOT EXISTS `events` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `excerpt` TEXT,
    `content` LONGTEXT,
    `featured_image` VARCHAR(255),
    `event_date` DATETIME,
    `location` VARCHAR(255),
    `status` ENUM('upcoming', 'active', 'past', 'cancelled') DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_event_date` (`event_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'events' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create user_posts table for community posts
$sql = "CREATE TABLE IF NOT EXISTS `user_posts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` LONGTEXT,
    `category` VARCHAR(100),
    `featured_image` VARCHAR(255),
    `status` ENUM('pending', 'approved', 'rejected', 'published') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'user_posts' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create post_usage table to track user post limits (5 posts per month)
$sql = "CREATE TABLE IF NOT EXISTS `post_usage` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `month` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    `post_count` INT(11) DEFAULT 0,
    `reset_date` TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `user_month` (`user_id`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'post_usage' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create password_resets table
$sql = "CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `expires_at` TIMESTAMP NOT NULL,
    `used` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'password_resets' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create contacts table
$sql = "CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'contacts' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Check if role column exists in users table, add if missing
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($result->num_rows === 0) {
    echo "Adding 'role' column to users table...\n";
    $sql = "ALTER TABLE users ADD COLUMN role ENUM('user', 'editor', 'admin') DEFAULT 'user' AFTER password";
    if ($conn->query($sql)) {
        echo "✓ Role column added.\n";
        
        // Add index
        $sql_index = "ALTER TABLE users ADD INDEX idx_role (role)";
        if ($conn->query($sql_index)) {
            echo "✓ Role index added.\n";
        }
    } else {
        echo "Error adding role column: " . $conn->error . "\n";
    }
} else {
    echo "✓ Role column already exists.\n";
}

// Check if remember_token column exists in users table, add if missing
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
if ($result->num_rows === 0) {
    echo "Adding 'remember_token' column to users table...\n";
    $sql = "ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "✓ Remember token column added.\n";
    } else {
        echo "Error adding remember_token column: " . $conn->error . "\n";
    }
} else {
    echo "✓ Remember token column already exists.\n";
}

// Check if admin user exists
$email = 'nic@blacnova.net';
$sql = "SELECT id, role FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "\nNo admin user found. You can register with nic@blacnova.net email.\n";
} else {
    $user = $result->fetch_assoc();
    if (empty($user['role'])) {
        // Update user role to admin
        $admin_role = 'admin';
        $update_sql = "UPDATE users SET role = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $admin_role, $email);
        if ($update_stmt->execute()) {
            echo "\n✓ Set nic@blacnova.net as admin role.\n";
        }
        $update_stmt->close();
    } else {
        echo "\n✓ Admin user found with role: " . $user['role'] . "\n";
    }
}

$stmt->close();
$conn->close();

echo "\nDatabase initialization complete!\n";

