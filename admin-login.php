<?php
// admin/login.php
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: /admin/'); exit; }
require_once __DIR__ . '/../includes/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: /admin/');
        exit;
    } else {
        $error = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – Allen and Rej Bridal</title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body style="background:var(--dark);">
<div class="auth-wrap" style="background:transparent;">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);font-style:italic;">Allen and Rej</div>
            <div style="font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gray);margin-top:4px;">Admin Panel</div>
        </div>
        <h2>Admin Login</h2>
        <p>Restricted access — authorized personnel only</p>
        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group" style="margin-bottom:20px;">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="username">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <div style="text-align:right;margin-bottom:20px;">
                <a href="/admin/admin-forgot-password.php" style="font-size:13px;color:var(--gold);text-decoration:none;">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;">Login to Admin</button>
        </form>
        <div style="text-align:center;margin-top:20px;font-size:13px;">
            <a href="/" style="color:var(--gray);">← Back to Website</a>
        </div>
    </div>
</div>
</body>
</html>
