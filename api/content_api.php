<?php
/**
 * Content Management API
 * Handles CRUD operations for stories and guides
 */

require_once '../config.php';

header('Content-Type: application/json');

// Require authentication (admin or editor)
if (!is_editor() && !is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$contentType = isset($_GET['type']) && in_array($_GET['type'], ['stories', 'guides', 'events']) ? $_GET['type'] : 'stories';

// Validate content type
if (!in_array($contentType, ['stories', 'guides', 'events'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid content type']);
    exit;
}

if ($method === 'GET') {
    // List content
    if ($action === 'list') {
        $status = isset($_GET['status']) ? $_GET['status'] : 'all';
        
        try {
            $conn = db_connect();
            
            $table = $contentType === 'stories' ? 'stories' : ($contentType === 'events' ? 'events' : 'guides');
            
            // Events have additional fields to select
            if ($contentType === 'events') {
                $query = "SELECT id, title, slug, excerpt, category, status, created_at, updated_at, event_date, location FROM $table";
            } else {
                $query = "SELECT id, title, slug, excerpt, category, status, created_at, updated_at FROM $table";
            }
            
            if ($status !== 'all' && in_array($status, ['draft', 'published', 'archived'])) {
                $query .= " WHERE status = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $status);
            } else {
                $stmt = $conn->prepare($query);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            
            echo json_encode(['success' => true, 'data' => $items]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log("List content error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to retrieve content']);
        }
    }
    
    // Get single content
    elseif ($action === 'get') {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }
        
        try {
            $conn = db_connect();
            $table = $contentType === 'stories' ? 'stories' : ($contentType === 'events' ? 'events' : 'guides');
            
            $stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Content not found']);
            } else {
                echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
            }
            
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Get content error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to retrieve content']);
        }
    }
}

elseif ($method === 'POST') {
    $conn = db_connect();
    
    // Create new content
    if ($action === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $featured_image = isset($_POST['featured_image']) ? trim($_POST['featured_image']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $status = isset($_POST['status']) && in_array($_POST['status'], ['draft', 'published', 'archived']) ? $_POST['status'] : 'draft';
        
        // Validate required fields
        if (empty($title) || empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and slug are required']);
            $conn->close();
            exit;
        }
        
        // Validate slug format
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid slug format']);
            $conn->close();
            exit;
        }
        
        try {
            $table = $contentType === 'stories' ? 'stories' : ($contentType === 'events' ? 'events' : 'guides');
            
            // Events have additional fields (event_date, location)
            if ($contentType === 'events') {
                $event_date = isset($_POST['event_date']) ? $_POST['event_date'] : date('Y-m-d H:i:s');
                $location = isset($_POST['location']) ? trim($_POST['location']) : '';
                $stmt = $conn->prepare("INSERT INTO $table (title, slug, excerpt, content, featured_image, category, status, event_date, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $title, $slug, $excerpt, $content, $featured_image, $category, $status, $event_date, $location);
            } else {
                $stmt = $conn->prepare("INSERT INTO $table (title, slug, excerpt, content, featured_image, category, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $title, $slug, $excerpt, $content, $featured_image, $category, $status);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $conn->insert_id]);
            } else {
                if ($conn->errno == 1062) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Slug already exists']);
                } else {
                    throw new Exception($stmt->error);
                }
            }
            
            $stmt->close();
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Create content error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to create content']);
        }
        $conn->close();
    }
    
    // Update content
    elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $featured_image = isset($_POST['featured_image']) ? trim($_POST['featured_image']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $status = isset($_POST['status']) && in_array($_POST['status'], ['draft', 'published', 'archived']) ? $_POST['status'] : 'draft';
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }
        
        try {
            $table = $contentType === 'stories' ? 'stories' : ($contentType === 'events' ? 'events' : 'guides');
            
            // Check if content exists
            $check_stmt = $conn->prepare("SELECT id FROM $table WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Content not found']);
                $check_stmt->close();
                $conn->close();
                exit;
            }
            $check_stmt->close();
            
            // Events have additional fields to update
            if ($contentType === 'events') {
                $event_date = isset($_POST['event_date']) ? $_POST['event_date'] : null;
                $location = isset($_POST['location']) ? trim($_POST['location']) : '';
                $stmt = $conn->prepare("UPDATE $table SET title=?, slug=?, excerpt=?, content=?, featured_image=?, category=?, status=?, event_date=?, location=? WHERE id=?");
                $stmt->bind_param("sssssssssi", $title, $slug, $excerpt, $content, $featured_image, $category, $status, $event_date, $location, $id);
            } else {
                $stmt = $conn->prepare("UPDATE $table SET title=?, slug=?, excerpt=?, content=?, featured_image=?, category=?, status=? WHERE id=?");
                $stmt->bind_param("sssssssi", $title, $slug, $excerpt, $content, $featured_image, $category, $status, $id);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                if ($conn->errno == 1062) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Slug already exists']);
                } else {
                    throw new Exception($stmt->error);
                }
            }
            
            $stmt->close();
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Update content error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to update content']);
        }
        $conn->close();
    }
    
    // Delete content
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }
        
        try {
            $table = $contentType === 'stories' ? 'stories' : ($contentType === 'events' ? 'events' : 'guides');
            
            // Check if content exists
            $check_stmt = $conn->prepare("SELECT id FROM $table WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Content not found']);
                $check_stmt->close();
                $conn->close();
                exit;
            }
            $check_stmt->close();
            
            $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception($stmt->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Delete content error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to delete content']);
        }
        $conn->close();
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

