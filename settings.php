<?php
require_once __DIR__ . '/includes/admin_header_pre.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $new_user = trim($_POST['username'] ?? '');

    $admin = $conn->query("SELECT * FROM admins WHERE id=".(int)$_SESSION['admin_id'])->fetch_assoc();

    if (!password_verify($current, $admin['password'])) {
        $error = 'Current password is incorrect.';
    } elseif ($new && strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new && $new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        if ($new_user && $new_user !== $admin['username']) {
            $check = $conn->query("SELECT id FROM admins WHERE username='".$conn->real_escape_string($new_user)."' AND id!=".(int)$_SESSION['admin_id']);
            if ($check->num_rows > 0) {
                $error = 'That username is already taken.';
            }
        }
        if (!$error) {
            $u = $conn->real_escape_string($new_user ?: $admin['username']);
            if ($new) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $hash = $conn->real_escape_string($hash);
                $conn->query("UPDATE admins SET username='$u', password='$hash' WHERE id=".(int)$_SESSION['admin_id']);
            } else {
                $conn->query("UPDATE admins SET username='$u' WHERE id=".(int)$_SESSION['admin_id']);
            }
            $success = 'Settings updated successfully.';
        }
    }
}

$admin = $conn->query("SELECT * FROM admins WHERE id=".(int)$_SESSION['admin_id'])->fetch_assoc();
$adminPageTitle = 'Settings';
require_once __DIR__ . '/includes/admin_header_output.php';
?>

<div class="admin-header"><h1>Admin Settings</h1></div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div class="admin-table-wrap" style="max-width:520px;padding:32px;">
    <h3 style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;margin-bottom:24px;">Change Login Credentials</h3>
    <form method="POST">
        <div class="form-group" style="margin-bottom:18px;">
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required style="border:1px solid var(--border);border-radius:4px;padding:10px 12px;width:100%;font-family:var(--font-body);font-size:14px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>Current Password <span style="color:var(--error);">*</span></label>
            <input type="password" name="current_password" required style="border:1px solid var(--border);border-radius:4px;padding:10px 12px;width:100%;font-family:var(--font-body);font-size:14px;">
        </div>
        <div style="height:1px;background:var(--border);margin:20px 0;"></div>
        <p style="font-size:13px;color:var(--gray);margin-bottom:16px;">Leave new password fields blank to keep your current password.</p>
        <div class="form-group" style="margin-bottom:18px;">
            <label>New Password</label>
            <input type="password" name="new_password" minlength="6" style="border:1px solid var(--border);border-radius:4px;padding:10px 12px;width:100%;font-family:var(--font-body);font-size:14px;" placeholder="Minimum 6 characters">
        </div>
        <div class="form-group" style="margin-bottom:28px;">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" style="border:1px solid var(--border);border-radius:4px;padding:10px 12px;width:100%;font-family:var(--font-body);font-size:14px;">
        </div>
        <button type="submit" class="btn btn-gold">Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
