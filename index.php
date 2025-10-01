<?php
// File: index.php
include 'config.php'; // Include the configuration and start the session

$conn = db_connect();

// Check if user is logged in (after connection for the logout button)
$is_logged_in = is_logged_in();
$user_email = $is_logged_in ? htmlspecialchars($_SESSION['user_email']) : '';

// --- Handle Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// --- Handle Login and Registration Forms ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_logged_in) {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = "Email and password are required.";
        } elseif ($action == 'register' && isset($_POST['password_confirm'])) {
            // --- Registration Logic ---
            $password_confirm = $_POST['password_confirm'];
            if ($password !== $password_confirm) {
                $error = "Passwords do not match.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['user_email'] = $email;
                    header('Location: index.php'); // Log in immediately
                    exit;
                } else {
                    if ($conn->errno == 1062) {
                        $error = "The email is already registered. Try logging in.";
                    } else {
                        $error = "Registration failed: " . $stmt->error;
                    }
                }
                $stmt->close();
            }

        } elseif ($action == 'login') {
            // --- Login Logic ---
            $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
                    <li><a href="nav/stories/index.html">Stories</a></li>
                    <li><a href="nav/guides/index.html">Guides</a></li>
                    <li><a href="nav/events/index.html">Events</a></li>
                    <li><a href="https://yucca.printify.me/" target="_blank" rel="noopener noreferrer">Shop</a></li>
                    <li><a href="nav/membership/index.html">Membership</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if ($is_logged_in): ?>
                    <span style="font-size: 14px; font-weight: 700; color: var(--lobo-gray);"><?= $user_email ?></span>
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
                <article class="bento-item item-featured post-card fade-in-on-scroll">
                    <div class="card-image"><img src="https://placehold.co/1200/600/A8AA19/F5F1E9?text=Organ+Mountains+Hike" alt="A dramatic sunrise view of the jagged Organ Mountains in Las Cruces." loading="lazy"></div>
                    <div class="card-content">
                        <p class="card-tag">Adventure</p>
                        <h2 class="card-title">Sunrise on the Organ Mountains: A Hiker's Guide to the Dripping Springs Trail</h2>
                        <p class="card-excerpt">Discover the magic of a New Mexico sunrise from one of Las Cruces' most iconic trails. We break down everything you need to know for a perfect morning hike.</p>
                        <a href="stories/placeholder.html" class="card-cta">Read The Story </a>
                    </div>
                </article>
               <section class="bento-item item-guides-promo guides-promo fade-in-on-scroll">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="promo-icon-svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <h3>In-Depth Local Guides</h3>
                    <p>From the best green chile spots to hiking guides for the Organ Mountains, find your next adventure.</p>
                    <a href="guides.html" class="cta-button">Explore All Guides</a>
                </section>
                <section class="bento-item item-newsletter newsletter-promo fade-in-on-scroll">
                    <img src="ui/img/icon_dark.png" alt="" class="promo-icon-img" aria-hidden="true">
                    <h3>Join the Club</h3>
                    <p>Get the latest stories and guides from the heart of the Southwest delivered to your inbox.</p>
                    <form class="newsletter-form">
                        <label for="newsletter-email-home" class="visually-hidden">Email for newsletter</label>
                        <input id="newsletter-email-home" type="email" class="form-input" placeholder="your-email@example.com" required>
                        <button type="submit" class="cta-button">Subscribe</button>
                    </form>
                </section>
                <article class="bento-item item-recent-1 post-card fade-in-on-scroll">
                    <div class="card-image"><img src="https://placehold.co/800/600/A8AA19/F5F1E9?text=Green+Chile+Quest" alt="Freshly roasted green chile peppers." loading="lazy"></div>
                    <div class="card-content">
                        <p class="card-tag">Food & Drink</p>
                        <h2 class="card-title">The Green Chile Quest: Finding the Best Burger in Old Mesilla</h2>
                        <a href="stories/placeholder.html" class="card-cta">Read More </a>
                    </div>
                </article>
                <article class="bento-item item-recent-2 post-card fade-in-on-scroll">
                    <div class="card-image"><img src="https://placehold.co/800/600/63666A/F5F1E9?text=Farmers+Market" alt="Brightly colored Talavera pottery." loading="lazy"></div>
                    <div class="card-content">
                        <p class="card-tag">Art & Culture</p>
                        <h2 class="card-title">A Weekend Guide to the Las Cruces Arts & Farmers Market</h2>
                        <a href="stories/placeholder.html" class="card-cta">Read More </a>
                    </div>
                </article>
            </div>
            <div class="view-more-container">
                <a href="nav/stories/" class="cta-button">View More Stories</a>
            </div>
        </div>
    </main>
    
    <section class="membership-cta fade-in-on-scroll">
        <div class="container">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-above-title">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <h2>Go Deeper Into The Southwest</h2>
            <p>Become a Yucca Club member for just $5/month to unlock exclusive stories, an ad-free experience, and support local, independent writing. </p>
            <a href="nav/membership/" class="cta-button">Explore Membership</a>
        </div>
    </section>
    
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

    <script src="ui/js/main.js"></script>
</body>
</html>