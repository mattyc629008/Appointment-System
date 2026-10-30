<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (isCustomerLoggedIn()) { header('Location: /dashboard.php'); exit; }

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Look up the customer
        $stmt = $conn->prepare("SELECT id, first_name, last_name FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Always show the same success message whether email exists or not
        // (prevents email enumeration attacks)
        $message = 'If that email is registered, you will receive a password reset link shortly. Please check your inbox (and spam folder).';
        $message_type = 'success';

        if ($user) {
            // Invalidate any existing unused tokens for this email
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();
            $del->close();

            // Generate a secure token
            $token = bin2hex(random_bytes(32)); // 64-char hex string
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save to DB
            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $token, $expires_at);
            $ins->execute();
            $ins->close();

            // Build reset link
            $reset_link = 'https://allen-rej-boutique.infinityfree.me/reset-password.php?token=' . urlencode($token);
            $full_name  = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);

            // Build HTML email
            $html_body = '
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f9f6f1;font-family:Georgia,serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f6f1;padding:40px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,0.08);">
        <tr>
          <td style="background:#1a1a1a;padding:32px 40px;text-align:center;">
            <div style="font-family:Georgia,serif;font-size:30px;color:#B8912A;font-style:italic;font-weight:300;letter-spacing:0.04em;">Allen and Rej</div>
            <div style="color:#888;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;margin-top:6px;">A Gown As Unique As You Are</div>
          </td>
        </tr>
        <tr>
          <td style="padding:48px 40px;">
            <h2 style="font-family:Georgia,serif;color:#1a1a1a;font-weight:400;margin:0 0 16px 0;font-size:24px;">Password Reset Request</h2>
            <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 12px 0;">Hello, <strong>' . $full_name . '</strong>,</p>
            <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 28px 0;">
              We received a request to reset the password for your Allen and Rej account. Click the button below to set a new password. This link expires in <strong>1 hour</strong>.
            </p>
            <table cellpadding="0" cellspacing="0" style="margin:0 0 28px 0;">
              <tr>
                <td style="background:#B8912A;border-radius:3px;">
                  <a href="' . $reset_link . '" style="display:inline-block;padding:14px 36px;color:#fff;text-decoration:none;font-family:Georgia,serif;font-size:15px;letter-spacing:0.06em;">Reset My Password</a>
                </td>
              </tr>
            </table>
            <p style="color:#888;font-size:13px;line-height:1.6;margin:0 0 8px 0;">
              If the button above does not work, copy and paste this link into your browser:
            </p>
            <p style="word-break:break-all;font-size:12px;color:#B8912A;margin:0 0 28px 0;">' . $reset_link . '</p>
            <hr style="border:none;border-top:1px solid #eee;margin:0 0 24px 0;">
            <p style="color:#aaa;font-size:12px;line-height:1.6;margin:0;">
              If you did not request a password reset, please ignore this email. Your password will not change.<br>
              For security, do not share this link with anyone.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f9f6f1;padding:20px 40px;text-align:center;">
            <p style="color:#bbb;font-size:11px;margin:0;">© ' . date('Y') . ' Allen and Rej Bridal &nbsp;·&nbsp; Dragon 8 Mall, Tondo, Manila</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

            send_email($email, $user['first_name'] . ' ' . $user['last_name'], 'Reset Your Password — Allen and Rej Bridal', $html_body);
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);font-style:italic;">Allen and Rej</div>
        </div>
        <h2>Forgot Password</h2>
        <p>Enter your email address and we'll send you a link to reset your password.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($message_type !== 'success'): ?>
        <form method="POST">
            <div class="form-group" style="margin-bottom:20px;">
                <label>Email Address</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="your@email.com">
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;">Send Reset Link</button>
        </form>
        <?php endif; ?>

        <div class="auth-switch" style="margin-top:20px;">
            Remembered it? <a href="/login.php">Back to Login</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
