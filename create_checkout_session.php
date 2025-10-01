<?php
// File: create_checkout_session.php

// Ensure the config file is included to get constants and start the session
include 'config.php';

// Composer autoloader should be present if they run composer install
require 'vendor/autoload.php';

// Check if a price ID was submitted
if (!isset($_POST['price_id']) || empty($_POST['price_id'])) {
    header('Location: nav/membership/index.html');
    exit;
}

// 1. Get the price ID from the form submission
$priceId = $_POST['price_id'];

// 2. Initialize Stripe with the Secret Key from config.php
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // 3. Create the Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'mode' => 'subscription', // Set mode to subscription for recurring payments
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        // Use the user's email if they are logged in (from the existing session in index.php)
        'customer_email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null,
        // The URLs Stripe redirects to after success or cancellation.
        'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/cancel.php',
    ]);

    // 4. Redirect the user to Stripe Checkout
    header("HTTP/1.1 303 See Other");
    header("Location: " . $session->url);
    exit;
} catch (\Exception $e) {
    // Handle error (log it and redirect the user)
    error_log("Stripe Checkout Error: " . $e->getMessage());
    header('Location: nav/membership/index.html?error=payment_failed');
    exit;
}
?>
