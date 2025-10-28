<?php
// Guides Page - Connected to Database
require_once '../../config.php';

// Get real guides from database
$guides = [];
$categories = [];

try {
    $conn = db_connect();
    
    // Get all published guides excluding placeholders
    $stmt = $conn->prepare("SELECT * FROM guides WHERE status = 'published' AND title NOT LIKE '%placeholder%' ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $guides[] = $row;
        if (!empty($row['category']) && !in_array($row['category'], $categories)) {
            $categories[] = $row['category'];
        }
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Guides page error: " . $e->getMessage());
}

$page_title = "In-Depth Guides - Yucca Club";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/png" href="../../ui/img/favicon.png">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Your expert source for guides on hiking, food, art, and travel in Southern New Mexico. Find the best of Las Cruces, El Paso, White Sands, and the Organ Mountains.">
    <meta name="keywords" content="Las Cruces guides, El Paso travel, hiking guides, food guides, Organ Mountains, White Sands, Southern New Mexico travel">
    <meta name="author" content="Yucca Club">
    <link rel="canonical" href="https://www.yuccaclub.com/nav/guides/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.yuccaclub.com/nav/guides/">
    <meta property="og:title" content="Yucca Club | In-Depth Guides to Las Cruces & El Paso">
    <meta property="og:description" content="Expert guides on hiking, food, events, and culture in Southern New Mexico.">
    <meta property="og:image" content="https://www.yuccaclub.com/ui/img/social-share.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://www.yuccaclub.com/nav/guides/">
    <meta property="twitter:title" content="Yucca Club | In-Depth Guides to Las Cruces & El Paso">
    <meta property="twitter:description" content="Expert guides on hiking, food, events, and culture in Southern New Mexico.">
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
          "@type": "CollectionPage",
          "name": "Yucca Club Guides",
          "url": "https://www.yuccaclub.com/nav/guides/",
          "description": "A curated collection of expert guides for exploring the best of Southern New Mexico, including hiking, dining, and day trips.",
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
                    <li><a href="../stories/index.php">Stories</a></li>
                    <li><a href="index.php" class="active">Guides</a></li>
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
                <div class="guides-grid">
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                    <div class="shimmer-placeholder" style="height: 380px;"></div>
                </div>
            </div>
        </div>
        
        <div class="container guides-container hidden">
<header class="text-center py-8">
    <div class="flex justify-center mb-4">
        <!-- Heroicon: Map Pin -->
        <svg xmlns="http://www.w3.org/2000/svg" 
             fill="none" 
             viewBox="0 0 24 24" 
             stroke-width="1.5" 
             stroke="currentColor" 
             class="w-16 h-16"
             style="color: #b8ba20;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.125-7.5 11.25-7.5 11.25S4.5 17.625 4.5 10.5a7.5 7.5 0 1115 0z" />
        </svg>
    </div>
    <h1 class="text-5xl font-serif mb-2">In-Depth Local Guides</h1>
    <p class="text-xl max-w-2xl mx-auto">
        Curated, actionable guides to help you explore the authentic Southwest, 
        from the best local eats to unforgettable outdoor adventures.
    </p>
</header>


            <div class="guide-filters" role="tablist" aria-label="Guide Categories">
                <button class="filter-btn active" data-filter="all" role="tab" aria-selected="true">All</button>
                <?php foreach (['Hiking', 'Food & Drink', 'Day Trips'] as $cat): ?>
                <?php if (in_array($cat, $categories)): ?>
                <button class="filter-btn" data-filter="<?= strtolower(str_replace([' ', '&'], ['-', ''], $cat)) ?>" role="tab"><?= $cat ?></button>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="guides-grid">
                <?php if (count($guides) === 0): ?>
                <p style="text-align: center; padding: 3rem; grid-column: 1/-1; opacity: 0.7;">No guides yet. Check back soon!</p>
                <?php else: ?>
                    <?php foreach ($guides as $guide): ?>
                    <article class="guide-card fade-in-on-scroll" data-category="<?= strtolower(str_replace([' ', '&'], ['-', ''], $guide['category'] ?: '')) ?>">
                        <div class="card-image"><img src="<?= htmlspecialchars($guide['featured_image']) ?>" alt="<?= htmlspecialchars($guide['title']) ?>" loading="lazy"></div>
                        <div class="card-content">
                            <p class="card-tag"><?= htmlspecialchars($guide['category'] ?: 'Guide') ?></p>
                            <h2 class="card-title"><?= htmlspecialchars($guide['title']) ?></h2>
                            <a href="../../view-post.php?slug=<?= htmlspecialchars($guide['slug']) ?>&type=guide" class="card-cta">View Guide</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
                document.querySelector('.guides-container').classList.remove('hidden');
            }, 300);
        });
    </script>
</body>
</html>
