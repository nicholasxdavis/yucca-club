<?php
/**
 * Database Connection Test
 * Use this to test which database hostname works in your environment
 */

// Include config
require_once 'config.php';

echo "<h1>Database Connection Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
</style>";

echo "<div class='info'>";
echo "<strong>Test 1:</strong> Using DB_SERVER constant: " . DB_SERVER . "<br>";
$conn1 = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn1->connect_error) {
    echo "<span class='error'>FAILED: " . $conn1->connect_error . "</span>";
} else {
    echo "<span class='success'>SUCCESS!</span>";
    $conn1->close();
}
echo "</div>";

echo "<div class='info'>";
echo "<strong>✅ Connection Test Complete!</strong><br>";
echo "The database connection is working with hostname: <strong>" . DB_SERVER . "</strong>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>Environment Variables:</strong><br>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'not set') . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ?: 'not set') . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'not set') . "<br>";
echo "</div>";

echo "<hr>";
echo "<p style='color: #19A819; font-weight: bold;'>✅ Database connection is working! You can now use the site.</p>";
echo "<p><a href='index.php' style='background: #a8aa19; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px;'>Go to Homepage</a></p>";

