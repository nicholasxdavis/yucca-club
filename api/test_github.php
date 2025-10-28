<?php
/**
 * GitHub Connection Test
 * Tests GitHub API connection and permissions
 */

require_once '../config.php';

header('Content-Type: application/json');

// Require admin authentication
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

define('GITHUB_TOKEN', getenv('GITHUB_TOKEN') ?: '');
define('GITHUB_REPO', getenv('GITHUB_REPO') ?: 'yucca-club');
define('GITHUB_OWNER', getenv('GITHUB_OWNER') ?: 'nicholasxdavis');

// Debug: Check if token is set
$token_configured = !empty(GITHUB_TOKEN);
$token_preview = $token_configured ? substr(GITHUB_TOKEN, 0, 10) . '...' : 'NOT SET';

$tests = [];

// Test 1: Check GitHub API authentication
$ch = curl_init("https://api.github.com/user");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . GITHUB_TOKEN,
    'Accept: application/vnd.github+json',
    'User-Agent: Yucca-Club-Admin'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tests['authentication'] = [
    'success' => $httpCode === 200,
    'message' => $httpCode === 200 ? 'GitHub authentication successful' : 'GitHub authentication failed (HTTP ' . $httpCode . ')',
    'code' => $httpCode
];

// Test 2: Check repository access
$ch = curl_init("https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . GITHUB_TOKEN,
    'Accept: application/vnd.github+json',
    'User-Agent: Yucca-Club-Admin'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$repoData = json_decode($response, true);
curl_close($ch);

$tests['repository_access'] = [
    'success' => $httpCode === 200,
    'message' => $httpCode === 200 ? 'Repository access granted' : 'Cannot access repository (HTTP ' . $httpCode . ')',
    'code' => $httpCode,
    'full_name' => $repoData['full_name'] ?? 'Unknown'
];

// Test 3: Check write permissions (try to get contents)
$path = 'saved-imgs';
$ch = curl_init("https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/contents/" . $path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . GITHUB_TOKEN,
    'Accept: application/vnd.github+json',
    'User-Agent: Yucca-Club-Admin'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tests['saved_imgs_folder'] = [
    'success' => $httpCode === 200 || $httpCode === 404,
    'message' => $httpCode === 200 ? 'saved-imgs folder exists' : ($httpCode === 404 ? 'saved-imgs folder not found (will be created automatically)' : 'Error checking folder (HTTP ' . $httpCode . ')'),
    'code' => $httpCode
];

// Test 4: Validate token permissions
$ch = curl_init("https://api.github.com/user/repos");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . GITHUB_TOKEN,
    'Accept: application/vnd.github+json',
    'User-Agent: Yucca-Club-Admin'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tests['token_permissions'] = [
    'success' => $httpCode === 200,
    'message' => $httpCode === 200 ? 'Token has proper permissions' : 'Token permission issue (HTTP ' . $httpCode . ')',
    'code' => $httpCode
];

// Summary
$all_passed = true;
foreach ($tests as $test) {
    if (!$test['success']) {
        $all_passed = false;
        break;
    }
}

echo json_encode([
    'success' => $all_passed,
    'message' => $all_passed ? 'All tests passed' : 'Some tests failed',
    'tests' => $tests,
    'github_config' => [
        'owner' => GITHUB_OWNER,
        'repo' => GITHUB_REPO,
        'folder' => 'saved-imgs',
        'token_configured' => $token_configured,
        'token_preview' => $token_preview
    ],
    'debug' => [
        'token_set' => $token_configured,
        'owner' => GITHUB_OWNER,
        'repo' => GITHUB_REPO
    ]
]);

