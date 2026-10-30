<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireCustomerLogin() {
    if (!isCustomerLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function getCustomer($conn) {
    if (!isCustomerLoggedIn()) return null;
    $id = $_SESSION['customer_id'];
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ─────────────────────────────────────────────────────────
//  CSRF PROTECTION
// ─────────────────────────────────────────────────────────

/** Generate a session-bound CSRF token (creates once per session) */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Output a hidden CSRF input field */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Validate CSRF token on POST — call at the top of any POST handler */
function csrf_verify(): bool {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';
    return $expected !== '' && hash_equals($expected, $submitted);
}