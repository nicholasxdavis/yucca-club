<?php
/**
 * User Posts API
 * Handles community post submissions and management
 */

require_once '../config.php';

header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $conn = db_connect();
    
    if ($method === 'GET' && $action === 'list') {
        // Get all user posts (for non-staff)
        // Staff can see all posts, users only see their own
        if ($user_role === 'admin' || $user_role === 'editor') {
            $stmt = $conn->prepare("SELECT up.*, u.email as user_email FROM user_posts up JOIN users u ON up.user_id = u.id ORDER BY up.created_at DESC");
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("SELECT up.*, u.email as user_email FROM user_posts up JOIN users u ON up.user_id = u.id WHERE up.user_id = ? ORDER BY up.created_at DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        
        $result = $stmt->get_result();
        $posts = [];
        
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        echo json_encode(['data' => $posts, 'success' => true]);
        $stmt->close();
        
    } elseif ($method === 'GET' && $action === 'get') {
        // Get single post
        $id = intval($_GET['id'] ?? 0);
        
        if ($user_role === 'admin' || $user_role === 'editor') {
            $stmt = $conn->prepare("SELECT up.*, u.email as user_email FROM user_posts up JOIN users u ON up.user_id = u.id WHERE up.id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conn->prepare("SELECT up.*, u.email as user_email FROM user_posts up JOIN users u ON up.user_id = u.id WHERE up.id = ? AND up.user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Post not found']);
        } else {
            echo json_encode(['data' => $result->fetch_assoc(), 'success' => true]);
        }
        
        $stmt->close();
        
    } elseif ($method === 'POST' && $action === 'create') {
        // Check post usage limit (5 posts per month)
        $current_month = date('Y-m');
        
        // Get or create usage record
        $stmt = $conn->prepare("SELECT post_count FROM post_usage WHERE user_id = ? AND month = ?");
        $stmt->bind_param("is", $user_id, $current_month);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $post_count = $row['post_count'];
        } else {
            $post_count = 0;
        }
        $stmt->close();
        
        // Check limit
        if ($post_count >= 5) {
            http_response_code(429);
            echo json_encode(['error' => 'You have reached your monthly limit of 5 posts. Try again next month.']);
            exit;
        }
        
        // Create post
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $category = trim($_POST['category'] ?? '');
        $featured_image = trim($_POST['featured_image'] ?? '');
        
        if (empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required']);
            exit;
        }
        
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        // Ensure unique slug
        $slug_base = $slug;
        $slug_counter = 1;
        while (true) {
            $check_stmt = $conn->prepare("SELECT id FROM user_posts WHERE slug = ?");
            $check_stmt->bind_param("s", $slug);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                break;
            }
            
            $slug = $slug_base . '-' . $slug_counter;
            $slug_counter++;
            $check_stmt->close();
        }
        
        $stmt = $conn->prepare("INSERT INTO user_posts (user_id, title, slug, content, category, featured_image, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isssss", $user_id, $title, $slug, $content, $category, $featured_image);
        
        if ($stmt->execute()) {
            $post_id = $conn->insert_id;
            
            // Update or create usage record
            if ($post_count > 0) {
                $update_stmt = $conn->prepare("UPDATE post_usage SET post_count = post_count + 1 WHERE user_id = ? AND month = ?");
                $update_stmt->bind_param("is", $user_id, $current_month);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO post_usage (user_id, month, post_count) VALUES (?, ?, 1)");
                $insert_stmt->bind_param("is", $user_id, $current_month);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            
            echo json_encode(['data' => ['id' => $post_id], 'success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create post']);
        }
        
        $stmt->close();
        
    } elseif ($method === 'POST' && $action === 'update_status') {
        // Update post status (admin/editor only)
        if ($user_role !== 'admin' && $user_role !== 'editor') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        
        if (!in_array($status, ['pending', 'approved', 'rejected', 'published'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE user_posts SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update status']);
        }
        
        $stmt->close();
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("User posts API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

