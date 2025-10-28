<?php
/**
 * Contact Form Handler
 * Saves contact form submissions to database
 */

require_once '../config.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Validate and sanitize input
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate input
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

// Validate name length
if (strlen($name) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'Name is too long']);
    exit;
}

// Validate email length
if (strlen($email) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is too long']);
    exit;
}

// Validate message length
if (strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is too long']);
    exit;
}

// Sanitize inputs
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

try {
    $conn = db_connect();
    
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, message, status) VALUES (?, ?, ?, 'new')");
    $stmt->bind_param("sss", $name, $email, $message);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your message! We will get back to you soon.'
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Contact handler error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to send message. Please try again later.']);
}

