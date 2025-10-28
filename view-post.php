<?php
/**
 * Dynamic Post Viewer
 * Displays user posts, stories, guides, and community posts based on slug
 */

require_once 'config.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? 'community'; // community, story, guide

if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

$post = null;
$error = null;

try {
    $conn = db_connect();
    
    // Based on type, query different tables
    if ($type === 'story') {
        $stmt = $conn->prepare("SELECT * FROM stories WHERE slug = ? AND status = 'published'");
    } elseif ($type === 'guide') {
        $stmt = $conn->prepare("SELECT * FROM guides WHERE slug = ? AND status = 'published'");
    } else {
        // Community post
        $stmt = $conn->prepare("SELECT up.*, u.email as user_email FROM user_posts up JOIN users u ON up.user_id = u.id WHERE up.slug = ? AND up.status = 'published'");
    }
    
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $post = $result->fetch_assoc();
    } else {
        $error = "Post not found.";
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Post view error: " . $e->getMessage());
    $error = "Unable to load post.";
}

if ($error || !$post) {
    header('HTTP/1.0 404 Not Found');
    echo "<html><head><title>404 - Post Not Found</title></head><body style='font-family: Arial; text-align: center; padding: 50px;'><h1>404</h1><p>Post not found.</p><a href='index.php'>Go Home</a></body></html>";
    exit;
}

$page_title = htmlspecialchars($post['title']) . " - Yucca Club";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/png" href="ui/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Lora:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="ui/css/styles.css">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars(substr($post['content'], 0, 160)) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(substr($post['content'], 0, 160)) ?>">
    <?php if (!empty($post['featured_image'])): ?>
    <meta property="og:image" content="<?= htmlspecialchars($post['featured_image']) ?>">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a href="index.php" class="site-logo">
                <img class="logo-light" src="ui/img/logo.png" alt="Yucca Club Logo" style="width:180px;">
                <img class="logo-dark" src="ui/img/logo_dark.png" alt="Yucca Club Logo Dark" style="width:180px;">
            </a>
            <nav class="primary-nav">
                <ul>
                    <li><a href="nav/stories/index.php">Stories</a></li>
                    <li><a href="nav/guides/index.php">Guides</a></li>
                    <li><a href="nav/events/index.php">Events</a></li>
                    <li><a href="https://yucca.printify.me/" target="_blank">Shop</a></li>
                    <li><a href="nav/community/index.php">Community</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main style="max-width: 900px; margin: 3rem auto; padding: 0 2rem;">
        <article class="post-content">
            <?php if (!empty($post['featured_image'])): ?>
            <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width: 100%; border-radius: 8px; margin-bottom: 2rem;">
            <?php endif; ?>
            
            <p style="color: var(--yucca-yellow); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.875rem; margin-bottom: 1rem;"><?= htmlspecialchars($post['category'] ?: 'Post') ?></p>
            
            <?php if (isset($post['user_email'])): ?>
            <p style="color: #666; font-size: 0.875rem; margin-bottom: 1.5rem;">By <?= htmlspecialchars($post['user_email']) ?> • <?= date('F j, Y', strtotime($post['created_at'])) ?></p>
            <?php else: ?>
            <p style="color: #666; font-size: 0.875rem; margin-bottom: 1.5rem;">Published on <?= date('F j, Y', strtotime($post['created_at'])) ?></p>
            <?php endif; ?>
            
            <h1 style="font-size: 3rem; font-weight: 700; margin-bottom: 1.5rem; line-height: 1.2;"><?= htmlspecialchars($post['title']) ?></h1>
            
            <?php if (!empty($post['excerpt'])): ?>
            <p style="font-size: 1.25rem; color: #666; margin-bottom: 2rem; font-style: italic;"><?= htmlspecialchars($post['excerpt']) ?></p>
            <?php endif; ?>
            
            <div style="font-size: 1.125rem; line-height: 1.8; color: #333;">
                <?php
                $rendered = false;
                // Try to render JSON blocks if present
                $content_raw = $post['content'] ?? '';
                $blocks = json_decode($content_raw, true);
                if (is_array($blocks)) {
                    $rendered = true;
                    foreach ($blocks as $block) {
                        $type = $block['type'] ?? 'paragraph';
                        $data = $block['data'] ?? [];
                        if ($type === 'heading') {
                            $text = htmlspecialchars($data['text'] ?? '');
                            echo "<h2 style=\"font-size:2rem; margin:1.25rem 0 0.5rem;\">$text</h2>";
                        } elseif ($type === 'subheading') {
                            $text = htmlspecialchars($data['text'] ?? '');
                            echo "<h3 style=\"font-size:1.5rem; margin:1rem 0 0.5rem;\">$text</h3>";
                        } elseif ($type === 'paragraph') {
                            $text = nl2br(htmlspecialchars($data['text'] ?? ''));
                            echo "<p style=\"margin:0.75rem 0;\">$text</p>";
                        } elseif ($type === 'blockquote') {
                            $text = nl2br(htmlspecialchars($data['text'] ?? ''));
                            echo "<blockquote style=\"border-left:4px solid var(--yucca-yellow); padding-left:1rem; margin:1rem 0; color:#555; font-style:italic;\">$text</blockquote>";
                        } elseif ($type === 'list') {
                            $items = $data['items'] ?? [];
                            echo "<ul style=\"margin:0.75rem 0 0.75rem 1.25rem;\">";
                            foreach ($items as $it) {
                                echo "<li>" . htmlspecialchars($it) . "</li>";
                            }
                            echo "</ul>";
                        } elseif ($type === 'image') {
                            $url = htmlspecialchars($data['url'] ?? '');
                            $alt = htmlspecialchars($data['alt'] ?? '');
                            if (!empty($url)) {
                                echo "<figure style=\"margin:1.25rem 0;\"><img src=\"$url\" alt=\"$alt\" style=\"width:100%; border-radius:8px;\"><figcaption style=\"font-size:0.9rem; opacity:0.8; margin-top:0.25rem;\">$alt</figcaption></figure>";
                            }
                        }
                    }
                }
                if (!$rendered) {
                    echo nl2br(htmlspecialchars($post['content']));
                }
                ?>
            </div>
        </article>
        
        <div style="margin-top: 4rem; padding-top: 2rem; border-top: 2px solid var(--darker-sand);">
            <a href="index.php" class="cta-button">← Back to Home</a>
        </div>
    </main>

    <footer class="site-footer" style="margin-top: 4rem;">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Yucca Club. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="ui/js/main.js"></script>
</body>
</html>

