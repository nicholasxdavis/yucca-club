<?php
/**
 * Contacts API
 * Returns contact form submissions
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
        $status = $_GET['status'] ?? 'all';
        $conn = db_connect();
        
        $query = "SELECT id, name, email, message, status, created_at FROM contacts";
        
        if ($status !== 'all') {
            $query .= " WHERE status = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $contacts = [];
        
        while ($row = $result->fetch_assoc()) {
            $contacts[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        echo json_encode(['success' => true, 'data' => $contacts]);
    } catch (Exception $e) {
        error_log("Contacts API error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch contacts']);
    }
}

elseif ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid contact ID']);
            exit;
        }
        
        try {
            $conn = db_connect();
            
            // Check if contact exists
            $check_stmt = $conn->prepare("SELECT id FROM contacts WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Contact not found']);
                $check_stmt->close();
                $conn->close();
                exit;
            }
            $check_stmt->close();
            
            $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception($stmt->error);
            }
            
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Delete contact error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to delete contact']);
        }
    }
    
    elseif ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'read';
        
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid contact ID']);
            exit;
        }
        
        // Validate status
        if (!in_array($status, ['new', 'read', 'replied', 'archived'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }
        
        try {
            $conn = db_connect();
            
            // Check if contact exists
            $check_stmt = $conn->prepare("SELECT id FROM contacts WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Contact not found']);
                $check_stmt->close();
                $conn->close();
                exit;
            }
            $check_stmt->close();
            
            $stmt = $conn->prepare("UPDATE contacts SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception($stmt->error);
            }
            
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Update contact status error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to update status']);
        }
    }
}

