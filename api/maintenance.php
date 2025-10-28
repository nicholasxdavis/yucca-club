<?php
/**
 * Maintenance Mode API
 * Toggle maintenance mode on/off
 */

require_once '../config.php';

header('Content-Type: application/json');

// Require admin authentication
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Get maintenance status
        $maintenance_file = __DIR__ . '/../.maintenance';
        $is_enabled = file_exists($maintenance_file);
        
        echo json_encode(['enabled' => $is_enabled]);
    } catch (Exception $e) {
        error_log("Maintenance API error: " . $e->getMessage());
        echo json_encode(['enabled' => false]);
    }
}

elseif ($method === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $maintenance_file = __DIR__ . '/../.maintenance';
    
    try {
        if ($action === 'enable') {
            // Create maintenance file
            if (file_put_contents($maintenance_file, json_encode([
                'enabled' => true,
                'timestamp' => time(),
                'message' => 'We are currently performing scheduled maintenance. Please check back later.'
            ])) === false) {
                throw new Exception('Failed to create maintenance file');
            }
            
            echo json_encode(['success' => true, 'status' => 'enabled']);
        }
        
        elseif ($action === 'disable') {
            // Delete maintenance file
            if (file_exists($maintenance_file)) {
                if (!unlink($maintenance_file)) {
                    throw new Exception('Failed to delete maintenance file');
                }
            }
            
            echo json_encode(['success' => true, 'status' => 'disabled']);
        }
    } catch (Exception $e) {
        error_log("Maintenance API error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update maintenance mode']);
    }
}

