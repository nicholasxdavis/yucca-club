<?php
// Stories Page - Connected to Database
require_once '../../config.php';

// Get real stories from database
$stories = [];
$featured_story = null;

try {
    $conn = db_connect();
    
    // Get all published stories excluding placeholders
    $stmt = $conn->prepare("SELECT * FROM stories WHERE status = 'published' AND title NOT LIKE '%placeholder%' ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }
    
    // Get featured story (latest with image)
    $stmt = $conn->prepare("SELECT * FROM stories WHERE status = 'published' AND featured_image IS NOT NULL AND featured_image != '' AND title NOT LIKE '%placeholder%' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $featured_story = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Stories page error: " . $e->getMessage());
}

$page_title = "Stories from the Southwest - Yucca Club";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/png" href="../../ui/img/favicon.png">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Explore our collection of stories about adventure, food, and culture in Las Cruces, El Paso, and the greater Southwest.">
    <meta name="keywords" content="Las Cruces stories, El Paso blog, Southern New Mexico culture, adventure travel, food blog, Southwest writing">
    <meta name="author" content="Yucca Club">
    <link rel="canonical" href="https://www.yuccaclub.com/nav/stories/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.yuccaclub.com/nav/stories/">
    <meta property="og:title" content="Yucca Club | Stories from the Southwest">
    <meta property="og:description" content="Explore our collection of stories about adventure, food, and culture in the heart of the Southwest.">
    <meta property="og:image" content="https://www.yuccaclub.com/ui/img/social-share.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://www.yuccaclub.com/nav/stories/">
    <meta property="twitter:title" content="Yucca Club | Stories from the Southwest">
    <meta property="twitter:description" content="Explore our collection of stories about adventure, food, and culture in the heart of the Southwest.">
    <meta property="twitter:image" content="https://www.yuccaclub.com/ui/img/social-share.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Lora:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../ui/css/styles.css">

    <!-- JSON-LD for Rich Snippets -->
    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Blog",
          "name": "Yucca Club Stories",
          "url": "https://www.yuccaclub.com/nav/stories/",
          "description": "In-depth stories about the culture, food, and adventures waiting in Southern New Mexico and West Texas.",
          "publisher": {
            "@type": "Organization",
            "name": "Yucca Club",
            "logo": {
              "@type": "ImageObject",
              "url": "https://www.yuccaclub.com/ui/img/logo.png"
            }
          }
        }
    </script>
</head>
<body>
    <div id="top-loader-bar"></div>
    
    <header class="site-header">
        <div class="container header-content">
            <a href="../../index.php" class="site-logo" aria-label="Yucca Club Homepage">
                <img class="logo-light" src="../../ui/img/logo.png" alt="Yucca Club Logo Light" style="width:180px; height:auto;">
                <img class="logo-dark" src="../../ui/img/logo_dark.png" alt="Yucca Club Logo Dark" style="width:180px; height:auto;">
            </a>
            <nav class="primary-nav" aria-label="Main Navigation">
                <ul>
                    <li><a href="index.php" class="active">Stories</a></li>
                    <li><a href="../guides/index.php">Guides</a></li>
                    <li><a href="../events/index.php">Events</a></li>
                    <li><a href="https://yucca.printify.me/" target="_blank" rel="noopener noreferrer">Shop</a></li>
                    <li><a href="../community/index.php">Community</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="#" id="account-trigger" aria-label="Account">
                    <i class="fas fa-user" aria-hidden="true"></i>
                </a>
                <button id="theme-toggle" aria-label="Toggle dark mode">
                    <i class="fas fa-moon" aria-hidden="true"></i>
                    <i class="fas fa-sun" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </header>
    
    <div class="live-conditions-bar" id="live-conditions" aria-live="polite">
        <span>Loading regional conditions...</span>
    </div>

    <main>
        <div id="shimmer-loader">
            <div class="container">
                <div style="height: 100px; margin-bottom: 2rem;" class="shimmer-placeholder"></div>
                <div class="stories-grid">
                    <div class="shimmer-placeholder featured-story" style="height: 450px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                </div>
            </div>
        </div>
        
        <div class="container stories-container hidden">
<header class="text-center py-8">
    <div class="flex justify-center mb-4">
        <!-- Heroicon: Book Open -->
        <svg xmlns="http://www.w3.org/2000/svg" 
             fill="none" 
             viewBox="0 0 24 24" 
             stroke-width="1.5" 
             stroke="currentColor" 
             class="w-16 h-16"
             style="color: #b8ba20;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c-2.755-1.134-5.648-1.36-8.25-.703a.75.75 0 00-.57.729v11.25c0 .332.228.623.55.704 2.565.658 5.416.46 8.27-.741m0-11.239c2.755-1.134 5.648-1.36 8.25-.703a.75.75 0 01.57.729v11.25c0 .332-.228.623-.55.704-2.565.658-5.416.46-8.27-.741m0-11.239v11.239" />
        </svg>
    </div>
    <h1 class="text-5xl font-serif mb-2">Stories from the Southwest</h1>
    <p class="text-xl max-w-2xl mx-auto">
        An ongoing collection of dispatches covering adventure, food, art, 
        and the unique culture of our desert home.
    </p>
</header>


            <div class="stories-grid">
                <?php if ($featured_story): ?>
                <article class="post-card featured-story fade-in-on-scroll">
                    <div class="card-image"><img src="<?= htmlspecialchars($featured_story['featured_image']) ?>" alt="<?= htmlspecialchars($featured_story['title']) ?>" loading="lazy"></div>
                    <div class="card-content">
                        <p class="card-tag"><?= htmlspecialchars($featured_story['category'] ?: 'Story') ?></p>
                        <h2 class="card-title"><?= htmlspecialchars($featured_story['title']) ?></h2>
                        <p class="card-excerpt"><?= htmlspecialchars($featured_story['excerpt'] ?: substr($featured_story['content'], 0, 150)) ?>...</p>
                        <a href="../../view-post.php?slug=<?= htmlspecialchars($featured_story['slug']) ?>&type=story" class="card-cta">Read The Story</a>
                    </div>
                </article>
                <?php endif; ?>

                <?php foreach (array_slice($stories, 1, 6) as $story): ?>
                <article class="post-card fade-in-on-scroll">
                    <div class="card-image"><img src="<?= htmlspecialchars($story['featured_image']) ?>" alt="<?= htmlspecialchars($story['title']) ?>" loading="lazy"></div>
                    <div class="card-content">
                        <p class="card-tag"><?= htmlspecialchars($story['category'] ?: 'Story') ?></p>
                        <h2 class="card-title"><?= htmlspecialchars($story['title']) ?></h2>
                        <a href="../../view-post.php?slug=<?= htmlspecialchars($story['slug']) ?>&type=story" class="card-cta">Read More</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <nav class="pagination fade-in-on-scroll" aria-label="Blog post navigation">
                <a href="#" class="prev disabled" aria-disabled="true">Previous</a>
                <a href="#" class="current" aria-current="page">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#" class="next">Next</a>
            </nav>
        </div>
    </main>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content site-footer-main">
                <p>&copy; 2025 Yucca Club. All Rights Reserved.</p>
                <nav class="footer-nav" aria-label="Footer Navigation">
                    <ul>
                        <li><a href="#" id="contact-trigger">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </nav>
            </div>
            <p class="sustainability-statement">
                Crafted with love in Las Cruces, New Mexico
            </p>
        </div>
    </footer>

    <script src="../../ui/js/main.js"></script>
    <script>
        // Hide shimmer loader and show content after load
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.getElementById('shimmer-loader').style.display = 'none';
                document.querySelector('.stories-container').classList.remove('hidden');
            }, 300);
        });
    </script>
</body>
</html>
