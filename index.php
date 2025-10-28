<?php
// File: index.php
include 'config.php'; // Include the configuration and start the session

// Check for maintenance mode
if (file_exists('.maintenance') && !is_admin()) {
    include 'maintenance.php';
    exit;
}

// Initialize connection variable
$conn = null;

// Try to connect to database with error handling
try {
    $conn = db_connect();
    
    // Run migration if role column doesn't exist (one-time)
    if ($conn) {
        $result_check = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($result_check && $result_check->num_rows === 0) {
            echo "<div style='padding: 2rem; max-width: 800px; margin: 0 auto;'>";
            echo "<h1>Database Migration Required</h1>";
            echo "<p>The 'role' column is missing from the users table.</p>";
            echo "<p><a href='migrate_add_role_column.php' style='display: inline-block; padding: 1rem 2rem; background: #a8aa19; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Run Migration Now</a></p>";
            echo "<p>Or run: <code>php init.php</code> to recreate all tables.</p>";
            echo "</div>";
            exit;
        }
    }
} catch (Exception $e) {
    // Database connection failed or tables don't exist
    error_log("Database error: " . $e->getMessage());
    $conn = null;
}

// Check if user is logged in via session
$is_logged_in = is_logged_in();
$user_email = $is_logged_in ? htmlspecialchars($_SESSION['user_email']) : '';

// Check for remember me cookie if not logged in
if (!$is_logged_in && isset($_COOKIE['remember_token']) && isset($conn)) {
    try {
        $token = $_COOKIE['remember_token'];
        $stmt = $conn->prepare("SELECT id, email, role FROM users WHERE remember_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = isset($user['role']) ? $user['role'] : 'user';
            $is_logged_in = true;
            $user_email = htmlspecialchars($user['email']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Invalid token, continue without login
        error_log("Remember me error: " . $e->getMessage());
    }
}

// --- Handle Logout ---
if (isset($_GET['logout']) || (isset($_POST['logout']) && $_POST['logout'] == 'true')) {
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        // Delete the cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        
        // Clear token from database if logged in
        if (is_logged_in() && isset($conn)) {
            try {
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            } catch (Exception $e) {
                error_log("Logout token clear error: " . $e->getMessage());
            }
        }
    }
    
    session_destroy();
    header('Location: index.php');
    exit;
}

// --- Handle Login and Registration Forms ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_logged_in && isset($conn)) {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($password)) {
            $error = "Email and password are required.";
        } elseif ($action == 'register' && isset($_POST['password_confirm'])) {
            // --- Registration Logic ---
            $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email address.";
            } elseif ($password !== $password_confirm) {
                $error = "Passwords do not match.";
            } elseif (strlen($password) < 8) {
                $error = "Password must be at least 8 characters long.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user'; // Default role for new registrations
                $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = $role; // Set the session role
                    header('Location: index.php'); // Log in immediately
                    exit;
                } else {
                    if ($conn->errno == 1062) {
                        $error = "The email is already registered. Try logging in.";
                    } else {
                        $error = "Registration failed: " . $stmt->error;
                    }
                }
                if (isset($stmt)) {
                    $stmt->close();
                }
            }

        } elseif ($action == 'login') {
            // --- Login Logic ---
            // Check if role column exists
            $has_role = false;
            if (isset($conn)) {
                $result_check = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
                $has_role = $result_check->num_rows > 0;
            }
            
            if (isset($conn)) {
                if ($has_role) {
                    $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
                } else {
                    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
                }
                
                if (isset($stmt)) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $error = "Database connection failed.";
                    $result = null;
                }
            } else {
                $error = "Database connection failed.";
                $result = null;
            }

            if (isset($result) && $result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    // Always set role from database - default to 'user' if missing
                    $_SESSION['user_role'] = isset($user['role']) ? $user['role'] : 'user';
                    
                    // Handle "Remember Me" - set cookie for 30 days
                    if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                        $remember_token = bin2hex(random_bytes(32));
                        
                        // Save token to database
                        $update_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $remember_token, $user['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Set cookie for 30 days
                        setcookie('remember_token', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                    }
                    
                    // Redirect based on role
                    if ($_SESSION['user_role'] === 'admin') {
                        header('Location: admin.php');
                        exit;
                    } else {
                        header('Location: index.php');
                        exit;
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}

// Pre-fetch homepage content (stories) before HTML output
$featured_story = null;
$recent_stories = [];

if (isset($conn)) {
    try {
        // Get featured story
        $stmt = $conn->prepare("SELECT * FROM stories WHERE status = 'published' AND featured_image IS NOT NULL AND featured_image != '' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $featured_story = $result->fetch_assoc();
        $stmt->close();
        
        // Get recent stories
        $stmt = $conn->prepare("SELECT * FROM stories WHERE status = 'published' AND title NOT LIKE '%placeholder%' ORDER BY created_at DESC LIMIT 2");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_stories[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Homepage content fetch error: " . $e->getMessage());
    }
}

// Only close connection if it exists and is open
if (isset($conn)) {
    try {
        $conn->close();
    } catch (Exception $e) {
        // Connection already closed or error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        /* --- Dynamic Background Pattern Styles --- */
.pattern-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
    /* Mask the pattern at the top and bottom edges */
    mask-image: linear-gradient(
        to bottom,
        transparent 0%,
        black 10%,
        black 90%,
        transparent 100%
    );
    -webkit-mask-image: linear-gradient(
        to bottom,
        transparent 0%,
        black 10%,
        black 90%,
        transparent 100%
    );
}

.pattern-icon {
    position: absolute;
    display: block;
    width: 100px;
    height: 100px;
    /* Default icon for light mode */
    background-image: url('ui/img/icon.png');
    background-size: contain;
    background-repeat: no-repeat;
    /* Optimize for movement */
    will-change: transform, opacity;
}

/* Dark mode icon swap */
html[data-theme='dark'] .pattern-icon {
    background-image: url('ui/img/icon_dark.png');
}

/* Animations for main element visibility (if not already in styles.css) */
@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

main {
    visibility: hidden;
    position: relative;
    z-index: 1;
    overflow: hidden;
}
        
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yucca Club | Your Insider's Guide to Las Cruces & El Paso</title>
    <link rel="icon" type="image/png" href="ui/img/favicon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Lora:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
     <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="ui/css/styles.css">

    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "WebSite",
          "name": "Yucca Club",
          "url": "https://www.yuccaclub.com",
          "description": "The definitive insider's guide to the culture, food, and adventure of Southern New Mexico, focusing on Las Cruces and El Paso.",
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
    
    <h1 class="visually-hidden">Your Insider's Guide to Las Cruces, El Paso, and Southern New Mexico</h1>
    
    <header class="site-header">
        <div class="container header-content">
            <a href="index.php" class="site-logo" aria-label="Yucca Club Homepage">
                <img class="logo-light" src="ui/img/logo.png" alt="Yucca Club Logo Light" style="width:180px; height:auto;">
                <img class="logo-dark" src="ui/img/logo_dark.png" alt="Yucca Club Logo Dark" style="width:180px; height:auto;">
            </a>
            <nav class="primary-nav" aria-label="Main Navigation">
                <ul>
                    <li><a href="nav/stories/index.php">Stories</a></li>
                    <li><a href="nav/guides/index.php">Guides</a></li>
                    <li><a href="nav/events/index.php">Events</a></li>
                    <li><a href="https://yucca.printify.me/" target="_blank" rel="noopener noreferrer">Shop</a></li>
                    <li><a href="nav/community/index.php">Community</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if ($is_logged_in): ?>
                    <span style="font-size: 14px; font-weight: 700; color: var(--lobo-gray);"><?= $user_email ?></span>
                    <?php if (is_admin()): ?>
                        <a href="admin.php" id="admin-trigger" aria-label="Admin Panel" title="Admin Panel" style="font-size: 14px; color: var(--yucca-yellow); margin-right: 0.5rem;">
                            <i class="fas fa-cog" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (is_editor() || is_admin()): ?>
                        <a href="upload.html" id="upload-trigger" aria-label="Upload Content" title="Upload Content" style="font-size: 14px; color: var(--yucca-yellow); margin-right: 0.5rem;">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <a href="?logout=true" id="logout-trigger" aria-label="Logout" title="Logout">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    </a>
                <?php else: ?>
                    <a href="#" id="account-trigger" aria-label="Account">
                        <i class="fas fa-user" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
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
                 <div class="bento-grid">
                    <div class="shimmer-placeholder item-featured"></div>
                    <div class="shimmer-placeholder item-guides-promo"></div>
                    <div class="shimmer-placeholder item-newsletter"></div>
                    <div class="shimmer-placeholder" style="height: 400px;"></div>
                    <div class="shimmer-placeholder" style="height: 400px;"></div>
                </div>
            </div>
        </div>
        
        <div class="container bento-container hidden">
            <h2 class="page-title">Homepage</h2>
            <div class="bento-grid">
                <?php
                if ($featured_story):
                ?>
                <article class="bento-item item-featured post-card fade-in-on-scroll">
                    <div class="card-image"><img src="<?= htmlspecialchars($featured_story['featured_image']) ?>" alt="<?= htmlspecialchars($featured_story['title']) ?>" loading="lazy"></div>
                    <div class="card-content">
                        <p class="card-tag"><?= htmlspecialchars($featured_story['category'] ?: 'Story') ?></p>
                        <h2 class="card-title"><?= htmlspecialchars($featured_story['title']) ?></h2>
                        <p class="card-excerpt"><?= htmlspecialchars($featured_story['excerpt'] ?: substr($featured_story['content'], 0, 150) . '...') ?></p>
                        <a href="view-post.php?slug=<?= htmlspecialchars($featured_story['slug']) ?>&type=story" class="card-cta">Read The Story </a>
                    </div>
                </article>
                <?php else: ?>
                <article class="bento-item item-featured post-card fade-in-on-scroll">
                    <div class="card-content" style="text-align: center; padding: 4rem 2rem;">
                        <p style="font-size: 1.2rem; opacity: 0.7;">No featured stories yet. Check back soon!</p>
                    </div>
                </article>
                <?php endif; ?>
                
                <section class="bento-item item-guides-promo guides-promo fade-in-on-scroll">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="promo-icon-svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <h3>In-Depth Local Guides</h3>
                    <p>From the best green chile spots to hiking guides for the Organ Mountains, find your next adventure.</p>
                    <a href="nav/guides/index.php" class="cta-button">Explore All Guides</a>
                </section>
                
                <section class="bento-item item-newsletter newsletter-promo fade-in-on-scroll">
                    <img src="ui/img/icon_dark.png" alt="" class="promo-icon-img" aria-hidden="true">
                    <h3>Join the Club</h3>
                    <p>Get the latest stories and guides from the heart of the Southwest delivered to your inbox.</p>
                    <form class="newsletter-form" id="join-club-form">
                        <label for="newsletter-email-home" class="visually-hidden">Email for newsletter</label>
                        <input id="newsletter-email-home" name="email" type="email" class="form-input" placeholder="your-email@example.com" required>
                        <button type="submit" class="cta-button">Subscribe</button>
                    </form>
                </section>
                
                <?php
                foreach ($recent_stories as $index => $story):
                ?>
                <article class="bento-item item-recent-<?= $index + 1 ?> post-card fade-in-on-scroll">
                    <?php if ($story['featured_image']): ?>
                    <div class="card-image"><img src="<?= htmlspecialchars($story['featured_image']) ?>" alt="<?= htmlspecialchars($story['title']) ?>" loading="lazy"></div>
                    <?php else: ?>
                    <div class="card-image" style="background: var(--darker-sand); display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 60px; height: 60px; opacity: 0.3;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <p class="card-tag"><?= htmlspecialchars($story['category'] ?: 'Story') ?></p>
                        <h2 class="card-title"><?= htmlspecialchars($story['title']) ?></h2>
                        <?php if ($story['excerpt']): ?>
                        <p class="card-excerpt"><?= htmlspecialchars(substr($story['excerpt'], 0, 150)) ?></p>
                        <?php endif; ?>
                        <a href="view-post.php?slug=<?= htmlspecialchars($story['slug']) ?>&type=story" class="card-cta">Read More </a>
                    </div>
                </article>
                <?php 
                endforeach;
                
                // If no stories, show empty state
                if (count($recent_stories) === 0):
                ?>
                <article class="bento-item item-recent-1 post-card fade-in-on-scroll">
                    <div class="card-content" style="text-align: center; padding: 3rem 2rem;">
                        <p style="opacity: 0.7;">More stories coming soon...</p>
                    </div>
                </article>
                <?php endif; ?>
            </div>
            <div class="view-more-container">
                <a href="nav/stories/index.php" class="cta-button">View More Stories</a>
            </div>
        </div>
    </main>
    
    <section class="membership-cta fade-in-on-scroll">
        <div class="container">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-above-title">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <h2>Support Yucca Club</h2>
            <p>Love what we're doing? Help us continue creating authentic content about the Southwest!</p>
            <a href="https://buymeacoffee.com/galore" target="_blank" rel="noopener noreferrer" class="cta-button">Support Us</a>
        </div>
    </section>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content site-footer-main">
                <p>&copy; 2025 Yucca Club. All Rights Reserved.</p>
                <nav class="footer-nav" aria-label="Footer Navigation">
                    <ul>
                        <li><a href="#" id="contact-trigger">Contact</a></li>
                        <li><a href="privacy_policy.php">Privacy Policy</a></li>
                    </ul>
                </nav>
            </div>
            <p class="sustainability-statement">
                Crafted with love in Las Cruces, New Mexico
            </p>
        </div>
    </footer>
    
    <div class="modal-overlay" id="account-modal" role="dialog" aria-modal="true" aria-labelledby="account-modal-title">
        <div class="modal-content">
            <button class="modal-close" aria-label="Close dialog">&times;</button>
            <h2 id="account-modal-title">Member Access</h2>
            
            <?php if (!empty($error)): ?>
                <p style="color: #A81919; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <p id="modal-subtitle">Log in or create an account to access exclusive content.</p>
            
            <form class="modal-form" id="member-form" method="POST" action="index.php">
                <input type="hidden" name="action" id="form-action" value="login">
                
                <label for="account-email" class="visually-hidden">Email</label>
                <input id="account-email" type="email" name="email" class="form-input" placeholder="your-email@example.com" required autocomplete="email" value="<?= $_POST['email'] ?? '' ?>">
                
                <label for="account-password" class="visually-hidden">Password</label>
                <input id="account-password" type="password" name="password" class="form-input" placeholder="Password" required autocomplete="current-password">
                
                <div id="confirm-password-field" style="display:none;">
                    <label for="account-password-confirm" class="visually-hidden">Confirm Password</label>
                    <input id="account-password-confirm" type="password" name="password_confirm" class="form-input" placeholder="Confirm Password" autocomplete="new-password">
                </div>
                
                <label style="display: flex; align-items: center; gap: 0.5rem; margin: 1rem 0;">
                    <input type="checkbox" name="remember_me" value="1" id="remember-me">
                    <span>Remember me</span>
                </label>
                
                <button type="submit" class="cta-button" id="form-submit-btn">Log In</button>
                
                <p class="form-link">
                    <a href="#" id="switch-mode-link">Need an account? Register here.</a>
                </p>
                <p class="form-link login-only-link"><a href="reset_password.html">Forgot password?</a></p>
            </form>
            
            <script>
                document.getElementById('switch-mode-link').addEventListener('click', function(e) {
                    e.preventDefault();
                    const formAction = document.getElementById('form-action');
                    const confirmField = document.getElementById('confirm-password-field');
                    const submitBtn = document.getElementById('form-submit-btn');
                    const subtitle = document.getElementById('modal-subtitle');
                    const loginOnlyLink = document.querySelector('.login-only-link');

                    if (formAction.value === 'login') {
                        // Switch to Register
                        formAction.value = 'register';
                        confirmField.style.display = 'block';
                        document.getElementById('account-password-confirm').setAttribute('required', 'required');
                        submitBtn.textContent = 'Register';
                        this.textContent = 'Already a member? Log in.';
                        if (subtitle) subtitle.textContent = 'Create a new account to join the club.';
                        if (loginOnlyLink) loginOnlyLink.style.display = 'none';
                        document.getElementById('account-password').autocomplete = 'new-password';
                    } else {
                        // Switch to Login
                        formAction.value = 'login';
                        confirmField.style.display = 'none';
                        document.getElementById('account-password-confirm').removeAttribute('required');
                        submitBtn.textContent = 'Log In';
                        this.textContent = 'Need an account? Register here.';
                        if (subtitle) subtitle.textContent = 'Log in or create an account to access exclusive content.';
                        if (loginOnlyLink) loginOnlyLink.style.display = 'block';
                        document.getElementById('account-password').autocomplete = 'current-password';
                    }
                    
                    // Reset error message display on switch
                    const errorP = document.querySelector('.modal-content > p[style*="color: #A81919"]');
                    if (errorP) errorP.style.display = 'none';
                });

                // Open modal if there was an error on submission
                <?php if (!empty($error)): ?>
                    document.addEventListener('DOMContentLoaded', () => {
                        const accountModal = document.getElementById('account-modal');
                        if (accountModal) {
                            accountModal.classList.add('visible');
                            
                            // If error occurred during registration, set modal to registration view
                            <?php if (isset($_POST['action']) && $_POST['action'] == 'register'): ?>
                                // Manually set to register mode (since we can't 'click' the link before DOM is fully loaded/handled)
                                document.getElementById('form-action').value = 'register';
                                document.getElementById('confirm-password-field').style.display = 'block';
                                document.getElementById('account-password-confirm').setAttribute('required', 'required');
                                document.getElementById('form-submit-btn').textContent = 'Register';
                                document.getElementById('switch-mode-link').textContent = 'Already a member? Log in.';
                                const subtitle = document.getElementById('modal-subtitle');
                                if (subtitle) subtitle.textContent = 'Create a new account to join the club.';
                                document.querySelector('.login-only-link').style.display = 'none';
                                document.getElementById('account-password').autocomplete = 'new-password';
                            <?php endif; ?>
                        }
                    });
                <?php endif; ?>
            </script>

        </div>
    </div>
    
    <div class="modal-overlay" id="contact-modal" role="dialog" aria-modal="true" aria-labelledby="contact-modal-title">
        <div class="modal-content">
            <button class="modal-close" aria-label="Close dialog">&times;</button>
            <h2 id="contact-modal-title">Get In Touch</h2>
            <p>Have a question or a story idea? We'd love to hear from you.</p>
            <form class="modal-form">
                <label for="contact-name" class="visually-hidden">Name</label>
                <input id="contact-name" type="text" class="form-input" placeholder="Your Name" required autocomplete="name">
                <label for="contact-email" class="visually-hidden">Email</label>
                <input id="contact-email" type="email" class="form-input" placeholder="Your Email" required autocomplete="email">
                <label for="contact-message" class="visually-hidden">Message</label>
                <textarea id="contact-message" class="form-input" placeholder="Your Message" required></textarea>
                <button type="submit" class="cta-button">Send Message</button>
            </form>
        </div>
    </div>
    
    <div id="toast-container" role="status" aria-live="polite"></div>
    
    <div id="cookie-banner" role="region" aria-label="Cookie consent banner">
        <p>This website uses cookies to ensure you get the best experience.</p>
        <button id="accept-cookies" class="cta-button">Accept</button>
    </div>
    
    <button id="back-to-top" aria-label="Back to top"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Handle contact form submission
            const contactForm = document.querySelector('#contact-modal .modal-form');
            if (contactForm) {
                contactForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const formData = new FormData();
                    formData.append('name', document.getElementById('contact-name').value);
                    formData.append('email', document.getElementById('contact-email').value);
                    formData.append('message', document.getElementById('contact-message').value);
                    
                    try {
                        const response = await fetch('api/contact_handler.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Show success message
                            alert(data.message || 'Thank you for your message! We will get back to you soon.');
                            
                            // Close modal
                            document.getElementById('contact-modal').classList.remove('active');
                            
                            // Reset form
                            contactForm.reset();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    } catch (error) {
                        alert('Error sending message. Please try again later.');
                    }
                });
            }

            function initApp() {
                // --- Theme Toggler ---
                const themeToggle = document.getElementById('theme-toggle');
                const htmlElement = document.documentElement;
                
                const updateThemeIcons = () => {
                    if (!themeToggle) return;
                    const moonIcon = themeToggle.querySelector('.fa-moon');
                    const sunIcon = themeToggle.querySelector('.fa-sun');
                    if (htmlElement.getAttribute('data-theme') === 'dark') {
                        moonIcon.style.display = 'none';
                        sunIcon.style.display = 'inline-block';
                    } else {
                        moonIcon.style.display = 'inline-block';
                        sunIcon.style.display = 'none';
                    }
                };

                const switchTheme = () => {
                    const currentTheme = htmlElement.getAttribute('data-theme');
                    if (currentTheme === 'dark') {
                        htmlElement.setAttribute('data-theme', 'light');
                        localStorage.setItem('theme', 'light');
                    } else {
                        htmlElement.setAttribute('data-theme', 'dark');
                        localStorage.setItem('theme', 'dark');
                    }
                    updateThemeIcons();
                };

                const setInitialTheme = () => {
                    const savedTheme = localStorage.getItem('theme');
                    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
                        htmlElement.setAttribute('data-theme', 'dark');
                    } else {
                        htmlElement.setAttribute('data-theme', 'light');
                    }
                    updateThemeIcons();
                };
                
                if (themeToggle) {
                    themeToggle.addEventListener('click', switchTheme);
                }
                setInitialTheme();

                // --- Live Weather Conditions Bar ---
                const conditionsBar = document.getElementById('live-conditions');
                if (conditionsBar) {
                    const cities = [
                        { name: 'Las Cruces, NM', stationId: 'KLRU' },
                        { name: 'El Paso, TX', stationId: 'KELP' },
                        { name: 'White Sands, NM', stationId: 'KOWI' }
                    ];
                    
                    const fetchAllWeather = async () => {
                        const weatherPromises = cities.map(city =>
                            fetch(`https://api.weather.gov/stations/${city.stationId}/observations/latest`)
                                .then(response => { if (!response.ok) return null; return response.json(); })
                                .then(data => {
                                    if (!data || !data.properties || data.properties.temperature.value === null) return null;
                                    const tempC = data.properties.temperature.value;
                                    const tempF = Math.round((tempC * 9/5) + 32);
                                    const description = data.properties.textDescription;
                                    return { name: city.name, conditions: `${tempF}°F, ${description}` };
                                })
                                .catch(() => null)
                        );
                    
                        try {
                            const results = await Promise.all(weatherPromises);
                            const validResults = results.filter(r => r !== null); 
                            if (validResults.length === 0) throw new Error("All weather API requests failed.");
                            startWeatherCycle(validResults);
                        } catch (error) {
                            console.error("Failed to fetch weather data:", error);
                            conditionsBar.textContent = "Live regional conditions are currently unavailable.";
                            conditionsBar.classList.add('error');
                        }
                    };
                    
                    let currentCityIndex = 0;
                    const startWeatherCycle = (weatherData) => {
                        const displayNextCity = () => {
                            if (weatherData.length === 0) return;
                            const city = weatherData[currentCityIndex];
                            conditionsBar.style.opacity = 0;
                            setTimeout(() => {
                                conditionsBar.innerHTML = `<strong>${city.name}:</strong> ${city.conditions}`;
                                conditionsBar.style.opacity = 1;
                            }, 500);
                            currentCityIndex = (currentCityIndex + 1) % weatherData.length;
                        };
                        displayNextCity();
                        setInterval(displayNextCity, 5000);
                    };
                    
                    fetchAllWeather();
                }

                // --- Toast Notifications ---
                const toastContainer = document.getElementById('toast-container');
                const showToast = (message) => {
                    if (!toastContainer) return;
                    const toast = document.createElement('div');
                    toast.className = 'toast show';
                    toast.textContent = message;
                    toastContainer.appendChild(toast);

                    setTimeout(() => {
                        toast.classList.remove('show');
                        toast.classList.add('hide');
                        toast.addEventListener('animationend', () => toast.remove());
                    }, 3000);
                };

                // --- Modal Logic ---
                const openModal = (modal) => modal.classList.add('visible');
                const closeModal = (modal) => modal.classList.remove('visible');

                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    if(!modal) return;
                    const closeButton = modal.querySelector('.modal-close');
                    if (closeButton) {
                        closeButton.addEventListener('click', () => closeModal(modal));
                    }
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeModal(modal);
                    });
                });
                
                const accountModal = document.getElementById('account-modal');
                const accountTrigger = document.getElementById('account-trigger');
                
                // Only attach listener if accountTrigger is present (i.e., user is logged out)
                if (accountTrigger && accountModal) {
                    accountTrigger.addEventListener('click', (e) => {
                        e.preventDefault();
                        openModal(accountModal);
                    });
                }
                
                if (localStorage.getItem('openLoginModal') === 'true' && accountModal) {
                    openModal(accountModal);
                    localStorage.removeItem('openLoginModal');
                }

                const contactModal = document.getElementById('contact-modal');
                const contactTrigger = document.getElementById('contact-trigger');
                if (contactTrigger && contactModal) {
                    contactTrigger.addEventListener('click', (e) => {
                        e.preventDefault();
                        openModal(contactModal);
                    });
                }

                const handleFormSubmit = async (event) => {
                    // Prevent default form submission for HTML-only forms
                    if (event.target.id !== 'member-form') {
                        event.preventDefault();
                        const form = event.target;
                        const parentModal = form.closest('.modal-overlay');
                        
                        // Handle contact form submission
                        if (form.closest('#contact-modal')) {
                            const formData = new FormData();
                            formData.append('name', document.getElementById('contact-name').value);
                            formData.append('email', document.getElementById('contact-email').value);
                            formData.append('message', document.getElementById('contact-message').value);
                            
                            try {
                                const response = await fetch('api/contact_handler.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                
                                const data = await response.json();
                                
                                if (data.success) {
                                    if (parentModal) closeModal(parentModal);
                                    showToast('Message sent successfully!');
                                    form.reset();
                                } else {
                                    showToast('Error: ' + (data.error || 'Failed to send message'));
                                }
                            } catch (error) {
                                showToast('Error: Failed to send message');
                            }
                        } else if (form.classList.contains('newsletter-form')) {
                            // Newsletter form - submit to contact handler
                            const formData = new FormData();
                            const email = form.querySelector('input[type="email"]').value;
                            formData.append('name', 'Newsletter Subscriber');
                            formData.append('email', email);
                            formData.append('message', 'Newsletter subscription request from homepage');
                            
                            try {
                                const response = await fetch('api/contact_handler.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                
                                const data = await response.json();
                                
                                if (data.success) {
                                    if (parentModal) closeModal(parentModal);
                                    showToast('Thank you for subscribing!');
                                    form.reset();
                                } else {
                                    showToast('Error: ' + (data.error || 'Failed to subscribe'));
                                }
                            } catch (error) {
                                showToast('Error: Failed to subscribe');
                            }
                        }
                    }
                    // PHP handles 'member-form' submission
                };

                document.querySelectorAll('.newsletter-form, .modal-form').forEach(form => {
                    form.addEventListener('submit', handleFormSubmit);
                });

                // --- Cookie Banner ---
                const cookieBanner = document.getElementById('cookie-banner');
                if (cookieBanner) {
                    const acceptCookiesBtn = document.getElementById('accept-cookies');
                    if (!localStorage.getItem('cookiesAccepted')) {
                        setTimeout(() => cookieBanner.classList.add('visible'), 2500);
                    }
                    if (acceptCookiesBtn) {
                        acceptCookiesBtn.addEventListener('click', () => {
                            cookieBanner.classList.remove('visible');
                            localStorage.setItem('cookiesAccepted', 'true');
                        });
                    }
                }

                // --- Back to Top Button ---
                const backToTopBtn = document.getElementById('back-to-top');
                if (backToTopBtn) {
                    window.addEventListener('scroll', () => {
                        if (window.scrollY > 400) {
                            backToTopBtn.classList.add('visible');
                        } else {
                            backToTopBtn.classList.remove('visible');
                        }
                    }, { passive: true });
                    backToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
                }

                // --- Scroll Animations (remains the same) ---
                const scrollAnimatedElements = document.querySelectorAll('.fade-in-on-scroll');
                if (scrollAnimatedElements.length > 0) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });
                    scrollAnimatedElements.forEach(el => observer.observe(el));
                }
            }
            
            // --- Dynamic Background Pattern (COPIED EXACTLY) ---
            const mainElement = document.querySelector('main');
            if (mainElement) {
                const patternContainer = document.createElement('div');
                patternContainer.className = 'pattern-container';
                mainElement.prepend(patternContainer);
                const iconCount = 30, minDistance = 10, placedIcons = [], maxAttempts = 100;
                for (let i = 0; i < iconCount; i++) {
                    let validPosition = false, newIconPos = {}, attempts = 0;
                    while (!validPosition && attempts < maxAttempts) {
                        newIconPos = { top: Math.random() * 100, left: Math.random() * 100 };
                        let isOverlapping = false;
                        for (const placedIcon of placedIcons) {
                            const distTop = newIconPos.top - placedIcon.top;
                            const distLeft = newIconPos.left - placedIcon.left;
                            if (Math.sqrt(distTop * distTop + distLeft * distLeft) < minDistance) { isOverlapping = true; break; }
                        }
                        if (!isOverlapping) validPosition = true;
                        attempts++;
                    }
                    if (validPosition) {
                        placedIcons.push(newIconPos);
                        const icon = document.createElement('span');
                        icon.className = 'pattern-icon';
                        const rotation = Math.random() * 360, scale = 0.7 + Math.random() * 0.6;
                        icon.style.top = `${newIconPos.top}%`;
                        icon.style.left = `${newIconPos.left}%`;
                        icon.style.transform = `translate(-50%, -50%) rotate(${rotation}deg) scale(${scale})`;
                        icon.style.opacity = (0.02 + Math.random() * 0.03).toFixed(2);
                        patternContainer.appendChild(icon);
                    }
                }
            }

            // --- Page Load Animation (remains the same) ---
            const topLoaderBar = document.getElementById('top-loader-bar');
            const shimmerLoader = document.getElementById('shimmer-loader');
            const contentContainer = document.querySelector('.bento-container');
            
            if (mainElement) {
                mainElement.style.visibility = 'visible';
            }

            setTimeout(() => {
                if(topLoaderBar) topLoaderBar.style.transform = 'scaleX(1)';
            }, 10);

            setTimeout(() => {
                if(topLoaderBar) topLoaderBar.style.opacity = '0';
                
                if (shimmerLoader) {
                    shimmerLoader.style.opacity = '0';
                    shimmerLoader.addEventListener('transitionend', () => {
                        shimmerLoader.style.display = 'none';
                        if (contentContainer) {
                            contentContainer.classList.remove('hidden');
                            contentContainer.style.opacity = '0';
                            contentContainer.style.animation = 'fadeInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
                        }
                    }, { once: true });
                }
                
                if(topLoaderBar) {
                    topLoaderBar.addEventListener('transitionend', () => {
                             if(topLoaderBar) topLoaderBar.style.display = 'none';
                    }, { once: true });
                }

                initApp();

            }, 1000);
            
            // Ensure content is shown even if there's an error
            setTimeout(() => {
                const contentContainer = document.querySelector('.bento-container');
                const shimmerLoader = document.getElementById('shimmer-loader');
                if (contentContainer && shimmerLoader) {
                    shimmerLoader.style.display = 'none';
                    contentContainer.classList.remove('hidden');
                }
            }, 1500);
        });
    </script>
</body>
</html>