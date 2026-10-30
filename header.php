<?php

// ── Security Headers ──────────────────────────────────────
// Prevent clickjacking
header('X-Frame-Options: SAMEORIGIN');
// Prevent MIME-type sniffing
header('X-Content-Type-Options: nosniff');
// Block reflected XSS in older browsers
header('X-XSS-Protection: 1; mode=block');
// Limit referrer info sent to third parties
header('Referrer-Policy: same-origin');
// Content Security Policy — allows Pusher JS and Font Awesome CDN, blocks inline eval()
// Per-request nonce replaces 'unsafe-inline' — Google Safe Browsing requirement
if (!defined('CSP_NONCE')) {
    define('CSP_NONCE', base64_encode(random_bytes(16)));
}
header("Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' 'nonce-" . CSP_NONCE . "' https://js.pusher.com https://cdnjs.cloudflare.com; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
    "img-src 'self' data: https:; " .
    "connect-src 'self' wss://*.pusher.com https://*.pusher.com https://sockjs-ap1.pusher.com; " .
    "frame-src https://www.google.com https://maps.google.com https://www.google.com/maps; " .
    "frame-ancestors 'self';"
);
// Restrict browser feature access
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Alenn and Rej Bridal' ?> - A Gown As Unique As You Are</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="topbar">
    <span>Call / Text: <a href="tel:09173872470">0917 387 2470</a> &nbsp;|&nbsp; Dragon 8 Mall, Tondo, Manila &nbsp;|&nbsp; Open 7 days a week</span>
</div>

<header>
    <div class="nav-wrap">
        <a href="/" class="logo">
            <span style="font-family:'Cormorant Garamond',serif;font-size:28px;color:#B8912A;font-style:italic;font-weight:300;letter-spacing:0.02em;">Alenn and Rej</span>
            <span>A Gown As Unique As You Are</span>
        </a>
        <nav>
            <a href="/index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">Home</a>
            <a href="/gowns.php">Gowns</a>
            <a href="/about.php" class="<?= $currentPage === 'about' ? 'active' : '' ?>">About</a>
            <a href="/contact.php" class="<?= $currentPage === 'contact' ? 'active' : '' ?>">Contact</a>
        </nav>
        <div class="nav-right">
            <div class="nav-icons">
                <?php if (isCustomerLoggedIn()): ?>
                    <a href="/dashboard.php" title="My Account"><i class="fas fa-user-circle"></i></a>
                    <a href="/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="/login.php" title="Login"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
            <a href="/appointment.php" class="btn btn-gold">Book an Appointment</a>
            <!-- Hamburger -->
            <button class="hamburger" id="hamburger" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Nav Drawer -->
<div class="mobile-nav" id="mobileNav">
    <div class="mobile-nav-inner">
        <div class="mobile-nav-header">
            <span class="brand">Allen and Rej</span>
            <button class="mobile-nav-close" id="mobileNavClose">✕</button>
        </div>
        <div class="mobile-nav-links">
            <div class="mobile-nav-divider"></div>
                        <a href="/gowns.php"><i class="fas fa-chevron-right" style="font-size:10px;color:var(--gold);"></i> Gowns</a>
            <a href="/our-brides.php"><i class="fas fa-chevron-right" style="font-size:10px;color:var(--gold);"></i> Our Brides</a>
            <a href="/about.php"><i class="fas fa-chevron-right" style="font-size:10px;color:var(--gold);"></i> About Us</a>
            <a href="/about.php#faq"><i class="fas fa-chevron-right" style="font-size:10px;color:var(--gold);"></i> FAQ</a>
            <a href="/contact.php"><i class="fas fa-chevron-right" style="font-size:10px;color:var(--gold);"></i> Contact</a>
        </div>
        <div class="mobile-nav-actions">
            <a href="/appointment.php" class="btn btn-gold">Book an Appointment</a>
            <?php if (isCustomerLoggedIn()): ?>
            <a href="/dashboard.php" class="btn btn-outline">My Dashboard</a>
            <a href="/logout.php" style="display:block;text-align:center;color:var(--gray);font-size:13px;margin-top:12px;text-decoration:none;">Logout</a>
            <?php else: ?>
            <a href="/login.php" class="btn btn-outline">Login / Register</a>
            <?php endif; ?>
        </div>
        <?php if (isCustomerLoggedIn()): ?>
        <div class="mobile-nav-user">
            <strong>👤 <?= htmlspecialchars($_SESSION['customer_name'] ?? 'My Account') ?></strong>
            Logged in
        </div>
        <?php endif; ?>
    </div>
</div>

<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
(function(){
    var ham = document.getElementById('hamburger');
    var nav = document.getElementById('mobileNav');
    var close = document.getElementById('mobileNavClose');
    function openNav(){ ham.classList.add('open'); nav.classList.add('open'); document.body.style.overflow='hidden'; }
    function closeNav(){ ham.classList.remove('open'); nav.classList.remove('open'); document.body.style.overflow=''; }
    if(ham) ham.addEventListener('click', openNav);
    if(close) close.addEventListener('click', closeNav);
    if(nav) nav.addEventListener('click', function(e){ if(e.target === nav) closeNav(); });
})();
</script>