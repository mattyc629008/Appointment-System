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
    "frame-ancestors 'self';"
);
// Restrict browser feature access
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');