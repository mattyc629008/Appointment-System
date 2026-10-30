<?php
// admin/reset-password.php
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: /admin/'); exit; }

require_once __DIR__ . '/../includes/db.php';

$token       = trim($_GET['token'] ?? '');
$error       = '';
$success     = false;
$valid_token = false;
$token_row   = null;

// ── Validate token ──────────────────────────────────────────────────────────
if ($token === '') {
    $error = 'Invalid or missing reset link. Please request a new one.';
} else {
    // Admin tokens are stored with 'admin:' prefix on the email field
    $stmt = $conn->prepare("
        SELECT * FROM password_resets
        WHERE token = ?
          AND used = 0
          AND expires_at > NOW()
          AND email LIKE 'admin:%'
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $token_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$token_row) {
        $error = 'This reset link is invalid or has expired. Please <a href="/admin/forgot-password.php">request a new one</a>.';
    } else {
        $valid_token = true;
    }
}

// ── Handle new password submission ─────────────────────────────────────────
if ($valid_token && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password     = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Strip the 'admin:' prefix to get the real email
        $real_email = substr($token_row['email'], 6);
        $hashed     = password_hash($new_password, PASSWORD_DEFAULT);

        // Update admin password by matching email
        $upd = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $upd->bind_param("ss", $hashed, $real_email);
        $upd->execute();
        $upd->close();

        // Mark token as used
        $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $mark->bind_param("s", $token);
        $mark->execute();
        $mark->close();

        $success     = true;
        $valid_token = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password – Allen and Rej Admin</title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body style="background:var(--dark);">
<div class="auth-wrap" style="background:transparent;">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);font-style:italic;">Allen and Rej</div>
            <div style="font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gray);margin-top:4px;">Admin Panel</div>
        </div>
        <h2>Reset Admin Password</h2>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                ✅ Password reset successfully!
            </div>
            <p style="text-align:center;margin-top:8px;">
                <a href="/admin/admin-login.php" class="btn btn-gold" style="display:inline-block;padding:12px 32px;">Back to Admin Login</a>
            </p>

        <?php elseif ($error): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">
                <?= $error ?>
            </div>
            <div style="text-align:center;margin-top:20px;font-size:13px;">
                <a href="/admin/forgot-password.php" style="color:var(--gray);">← Request a new reset link</a>
            </div>

        <?php elseif ($valid_token): ?>
            <p>Enter your new admin password below.</p>
            <form method="POST" action="/admin/admin-reset-password.php?token=<?= urlencode($token) ?>">
                <div class="form-group" style="margin-bottom:20px;">
                    <label>New Password</label>
                    <input type="password" name="password" required minlength="8" placeholder="At least 8 characters">
                </div>
                <div class="form-group" style="margin-bottom:28px;">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="8" placeholder="Repeat your new password">
                </div>
                <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;">Set New Password</button>
            </form>
            <div style="text-align:center;margin-top:20px;font-size:13px;">
                <a href="/admin/admin-login.php" style="color:var(--gray);">← Back to Admin Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
