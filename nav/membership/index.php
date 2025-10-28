<?php
// Membership Page
require_once '../../config.php';

$page_title = "Membership - Yucca Club";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/png" href="../../ui/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Lora:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../ui/css/styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a href="../../index.php" class="site-logo">
                <img class="logo-light" src="../../ui/img/logo.png" alt="Yucca Club Logo" style="width:180px;">
                <img class="logo-dark" src="../../ui/img/logo_dark.png" alt="Yucca Club Logo Dark" style="width:180px;">
            </a>
            <nav class="primary-nav">
                <ul>
                    <li><a href="../stories/">Stories</a></li>
                    <li><a href="../guides/">Guides</a></li>
                    <li><a href="../events/">Events</a></li>
                    <li><a href="https://yucca.printify.me/" target="_blank">Shop</a></li>
                    <li><a href="index.php" class="active">Membership</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <button id="theme-toggle"><i class="fas fa-moon"></i><i class="fas fa-sun"></i></button>
            </div>
        </div>
    </header>

    <main>
        <div class="container" style="max-width: 800px; margin: 0 auto; padding: 3rem 2rem;">
            <h1 class="page-title">Join the Club</h1>
            
            <div class="pricing-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem;">
                <div class="pricing-card" style="background: var(--off-white); padding: 2rem; border-radius: 12px; border: 2px solid var(--yucca-yellow);">
                    <h2 style="font-size: 2rem; margin-bottom: 1rem;">Free</h2>
                    <div style="font-size: 3rem; font-weight: 700; color: var(--yucca-yellow);">$0</div>
                    <p style="color: #666; margin-bottom: 2rem;">Access to limited content</p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 0.5rem 0;">✓ Monthly newsletter</li>
                        <li style="padding: 0.5rem 0;">✓ Public stories & guides</li>
                    </ul>
                </div>
                
                <div class="pricing-card" style="background: var(--yucca-yellow); padding: 2rem; border-radius: 12px; color: white;">
                    <h2 style="font-size: 2rem; margin-bottom: 1rem;">Member</h2>
                    <div style="font-size: 3rem; font-weight: 700;">$5<span style="font-size: 1.5rem;">/month</span></div>
                    <p style="margin-bottom: 2rem;">All-access pass</p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 0.5rem 0;">✓ Ad-free experience</li>
                        <li style="padding: 0.5rem 0;">✓ Exclusive content</li>
                        <li style="padding: 0.5rem 0;">✓ Early access to stories</li>
                        <li style="padding: 0.5rem 0;">✓ Support local journalism</li>
                    </ul>
                    <a href="<?= is_logged_in() ? '#' : '../../index.php#account-modal' ?>" class="cta-button" style="display: block; text-align: center; margin-top: 2rem;">Join Now</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Yucca Club. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="../../ui/js/main.js"></script>
</body>
</html>

