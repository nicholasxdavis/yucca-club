<?php
/**
 * GitHub Image Upload API
 * Uploads images to GitHub repository and returns public URL
 */

require_once '../config.php';

// GitHub configuration - Get from environment variables
define('GITHUB_TOKEN', getenv('GITHUB_TOKEN') ?: '');
define('GITHUB_REPO', getenv('GITHUB_REPO') ?: 'yucca-club');
define('GITHUB_OWNER', getenv('GITHUB_OWNER') ?: 'nicholasxdavis');
define('GITHUB_FOLDER', getenv('GITHUB_FOLDER') ?: 'saved-imgs');

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if GitHub token is configured
if (empty(GITHUB_TOKEN)) {
    http_response_code(500);
    echo json_encode(['error' => 'GitHub token not configured']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

// Check if user is admin or editor
if (!is_admin() && !is_editor()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $file = $_FILES['image'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only images are allowed.']);
        exit;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Maximum size is 5MB.']);
        exit;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $extension;
    $path = GITHUB_FOLDER . '/' . $filename;
    
    // Read file content
    $fileContent = file_get_contents($file['tmp_name']);
    $base64Content = base64_encode($fileContent);
    
    // Prepare GitHub API request
    $url = "https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/contents/" . $path;
    
    $data = [
        'message' => 'Upload image via admin panel',
        'content' => $base64Content,
        'branch' => 'main'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . GITHUB_TOKEN,
        'Accept: application/vnd.github+json',
        'User-Agent: Yucca-Club-Admin'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $responseData = json_decode($response, true);
        
        // Construct the raw GitHub URL
        $rawUrl = "https://raw.githubusercontent.com/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/main/" . $path;
        
        echo json_encode([
            'success' => true,
            'url' => $rawUrl,
            'filename' => $filename
        ]);
    } else {
        throw new Exception('GitHub API error: ' . $response);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload image: ' . $e->getMessage()]);
}

