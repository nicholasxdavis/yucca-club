<?php
/**
 * Migration: Add role column to users table
 * Run this once to add the role column to your database
 * NOTE: This can be run by anyone during setup. Delete this file after migration.
 */

require_once 'config.php';

$conn = db_connect();

echo "<h1>Database Migration: Add Role Column</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #f0f0f0; padding: 1rem; border-radius: 6px; margin: 1rem 0; }
</style>";

// Check if role column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");

if ($result->num_rows > 0) {
    echo "<div class='info'>";
    echo "<p class='success'>✓ The 'role' column already exists in the users table.</p>";
    echo "<p>No migration needed.</p>";
    echo "</div>";
} else {
    echo "<div class='info'>";
    echo "<p>Adding 'role' column to users table...</p>";
    
    // Add the role column
    $sql = "ALTER TABLE users ADD COLUMN role ENUM('user', 'editor', 'admin') DEFAULT 'user' AFTER password";
    
    if ($conn->query($sql)) {
        echo "<p class='success'>✓ Successfully added 'role' column to users table!</p>";
        
        // Add index
        $sql_index = "ALTER TABLE users ADD INDEX idx_role (role)";
        if ($conn->query($sql_index)) {
            echo "<p class='success'>✓ Successfully added index on 'role' column!</p>";
        }
        
        // Set admin user as admin
        $admin_email = 'nic@blacnova.net';
        $sql_admin = "UPDATE users SET role = 'admin' WHERE email = ?";
        $stmt = $conn->prepare($sql_admin);
        $stmt->bind_param("s", $admin_email);
        if ($stmt->execute()) {
            echo "<p class='success'>✓ Set nic@blacnova.net as admin role!</p>";
        }
        $stmt->close();
        
    } else {
        echo "<p class='error'>✗ Error adding column: " . $conn->error . "</p>";
    }
    echo "</div>";
}

$conn->close();

echo "<div class='info'>";
echo "<h2>Migration Complete!</h2>";
echo "<p>You can now <a href='index.php'>go back to the site</a>.</p>";
echo "</div>";

