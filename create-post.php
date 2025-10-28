<?php
// Create Post Page - Staff Only
require_once 'config.php';

// Only editors and admins can create community posts
if (!is_editor() && !is_admin()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = htmlspecialchars($_SESSION['user_email']);
$user_role = $_SESSION['user_role'] ?? 'user';

$page_title = "Create Post - Yucca Club";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/png" href="ui/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="ui/css/styles.css">
</head>
<body style="background: var(--desert-sand); min-height: 100vh; padding: 2rem 0;">
    <div class="container" style="max-width: 900px; margin: 0 auto; background: var(--off-white); border-radius: 12px; padding: 2rem;">
        <h1 style="margin-bottom: 1rem;">Create Community Post</h1>
        
        <div style="background: var(--yucca-yellow); padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <p style="margin: 0; font-weight: 700;">Staff Post Creation - Create content for the community</p>
        </div>
        
        <form id="post-form">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700;">Title *</label>
                <input type="text" id="post-title" required style="width: 100%; padding: 0.75rem; border: 2px solid var(--lobo-gray); border-radius: 6px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700;">Category</label>
                <select id="post-category" style="width: 100%; padding: 0.75rem; border: 2px solid var(--lobo-gray); border-radius: 6px;">
                    <option value="">Select a category</option>
                    <option value="Food & Drink">Food & Drink</option>
                    <option value="Outdoors">Outdoors</option>
                    <option value="Events">Events</option>
                    <option value="Culture">Culture</option>
                    <option value="News">News</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700;">Featured Image URL</label>
                <input type="url" id="post-image" style="width: 100%; padding: 0.75rem; border: 2px solid var(--lobo-gray); border-radius: 6px;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700;">Content *</label>
                <textarea id="post-content" required rows="10" style="width: 100%; padding: 0.75rem; border: 2px solid var(--lobo-gray); border-radius: 6px;"></textarea>
            </div>
            
            <button type="submit" class="cta-button" style="width: 100%;">Submit Post</button>
            <a href="nav/community/index.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--lobo-gray);">Cancel</a>
        </form>
    </div>
    
    <script>
        document.getElementById('post-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const title = document.getElementById('post-title').value;
            const content = document.getElementById('post-content').value;
            const category = document.getElementById('post-category').value;
            const image = document.getElementById('post-image').value;
            
            try {
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('title', title);
                formData.append('content', content);
                formData.append('category', category);
                formData.append('featured_image', image);
                
                const response = await fetch('api/user_posts_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Post submitted successfully! It will be reviewed before publication.');
                    window.location.href = 'nav/community/index.php';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error submitting post: ' + error.message);
            }
        });
    </script>
</body>
</html>

