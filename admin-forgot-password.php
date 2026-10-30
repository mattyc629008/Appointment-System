<?php
// admin/forgot-password.php
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: /admin/'); exit; }

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $message = 'Please enter your username.';
        $message_type = 'error';
    } else {
        // Look up admin by username
        $stmt = $conn->prepare("SELECT id, username, email FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Always show the same message (prevents username enumeration)
        $message = 'If that username exists and has an email on file, a reset link has been sent.';
        $message_type = 'success';

        if ($admin && !empty($admin['email'])) {
            // Delete any existing unused tokens
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $admin['email']);
            $del->execute();
            $del->close();

            // Generate secure token
            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token — store with a prefix so we know it's for admin
            $email_key = 'admin:' . $admin['email'];
            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email_key, $token, $expires_at);
            $ins->execute();
            $ins->close();

            $reset_link = 'https://allen-rej-boutique.infinityfree.me/admin/admin-reset-password.php?token=' . urlencode($token);

            $html_body = '
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#111;font-family:Georgia,serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#111;padding:40px 0;">
    <tr><td align="center">
      <table width="580" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:4px;overflow:hidden;border:1px solid #2a2a2a;">
        <tr>
          <td style="background:#111;padding:28px 40px;text-align:center;border-bottom:1px solid #2a2a2a;">
            <div style="font-family:Georgia,serif;font-size:26px;color:#B8912A;font-style:italic;">Allen and Rej</div>
            <div style="color:#555;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;margin-top:5px;">Admin Panel</div>
          </td>
        </tr>
        <tr>
          <td style="padding:40px;">
            <h2 style="font-family:Georgia,serif;color:#eee;font-weight:400;margin:0 0 16px 0;font-size:22px;">Admin Password Reset</h2>
            <p style="color:#999;font-size:14px;line-height:1.7;margin:0 0 12px 0;">Hello, <strong style="color:#ccc;">' . htmlspecialchars($admin['username']) . '</strong>,</p>
            <p style="color:#999;font-size:14px;line-height:1.7;margin:0 0 28px 0;">
              A password reset was requested for your admin account. Click the button below to set a new password. This link expires in <strong style="color:#ccc;">1 hour</strong>.
            </p>
            <table cellpadding="0" cellspacing="0" style="margin:0 0 28px 0;">
              <tr>
                <td style="background:#B8912A;border-radius:3px;">
                  <a href="' . $reset_link . '" style="display:inline-block;padding:13px 32px;color:#fff;text-decoration:none;font-family:Georgia,serif;font-size:14px;letter-spacing:0.06em;">Reset Admin Password</a>
                </td>
              </tr>
            </table>
            <p style="color:#555;font-size:12px;line-height:1.6;margin:0 0 8px 0;">Or copy this link into your browser:</p>
            <p style="word-break:break-all;font-size:11px;color:#B8912A;margin:0 0 28px 0;">' . $reset_link . '</p>
            <hr style="border:none;border-top:1px solid #2a2a2a;margin:0 0 20px 0;">
            <p style="color:#444;font-size:12px;margin:0;">If you did not request this, ignore this email. Your password will not change.</p>
          </td>
        </tr>
        <tr>
          <td style="background:#111;padding:16px 40px;text-align:center;border-top:1px solid #2a2a2a;">
            <p style="color:#333;font-size:11px;margin:0;">© ' . date('Y') . ' Allen and Rej Bridal — Admin Use Only</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

            send_email($admin['email'], $admin['username'], 'Admin Password Reset — Allen and Rej', $html_body);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password – Allen and Rej Admin</title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body style="background:var(--dark);">
<div class="auth-wrap" style="background:transparent;">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);font-style:italic;">Allen and Rej</div>
            <div style="font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gray);margin-top:4px;">Admin Panel</div>
        </div>
        <h2>Forgot Password</h2>
        <p>Enter your admin username and we'll send a reset link to your registered email.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($message_type !== 'success'): ?>
        <form method="POST">
            <div class="form-group" style="margin-bottom:24px;">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;">Send Reset Link</button>
        </form>
        <?php endif; ?>

        <div style="text-align:center;margin-top:20px;font-size:13px;">
            <a href="/admin/admin-login.php" style="color:var(--gray);">← Back to Admin Login</a>
        </div>
    </div>
</div>
</body>
</html>
