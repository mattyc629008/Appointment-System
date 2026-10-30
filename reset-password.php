<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isCustomerLoggedIn()) { header('Location: /dashboard.php'); exit; }

$token = trim($_GET['token'] ?? '');
$error = '';
$success = false;
$valid_token = false;
$token_row = null;

// ── Validate token ──────────────────────────────────────────────────────────
if ($token === '') {
    $error = 'Invalid or missing reset link. Please request a new one.';
} else {
    $stmt = $conn->prepare("
        SELECT * FROM password_resets
        WHERE token = ?
          AND used = 0
          AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $token_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$token_row) {
        $error = 'This reset link is invalid or has expired. Please <a href="/forgot-password.php">request a new one</a>.';
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
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the customer's password
        $upd = $conn->prepare("UPDATE customers SET password = ? WHERE email = ?");
        $upd->bind_param("ss", $hashed, $token_row['email']);
        $upd->execute();
        $upd->close();

        // Mark token as used
        $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $mark->bind_param("s", $token);
        $mark->execute();
        $mark->close();

        $success = true;
        $valid_token = false; // hide the form
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);font-style:italic;">Allen and Rej</div>
        </div>
        <h2>Reset Password</h2>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                ✅ Your password has been reset successfully!
            </div>
            <p style="text-align:center;margin-top:8px;">
                <a href="/login.php" class="btn btn-gold" style="display:inline-block;padding:12px 32px;">Login with New Password</a>
            </p>

        <?php elseif ($error): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">
                <?= $error ?>
            </div>
            <div class="auth-switch" style="margin-top:20px;">
                <a href="/forgot-password.php">← Request a new reset link</a>
            </div>

        <?php elseif ($valid_token): ?>
            <p>Enter your new password below.</p>
            <form method="POST" action="/reset-password.php?token=<?= urlencode($token) ?>">
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
            <div class="auth-switch" style="margin-top:20px;">
                <a href="/login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
