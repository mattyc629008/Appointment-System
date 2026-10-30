<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isCustomerLoggedIn()) { header('Location: /dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_name'] = $user['first_name'];
        // SECURITY: Validate redirect is a relative internal path only.
        // Prevents open-redirect phishing (e.g. ?redirect=https://evil.com).
        $raw_redirect = $_GET['redirect'] ?? '/dashboard.php';
        // Only allow paths starting with / and not containing // or protocol
        $redirect = (preg_match('#^/[a-zA-Z0-9/_\-.?=&%]*$#', $raw_redirect))
            ? $raw_redirect
            : '/dashboard.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);font-style:italic;">Allen and Rej</div>
        </div>
        <h2>Welcome Back</h2>
        <p>Login to view your appointments and notifications</p>
        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group" style="margin-bottom:20px;">
                <label>Email Address</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div style="text-align:right;margin-bottom:20px;">
                <a href="/forgot-password.php" style="font-size:13px;color:var(--gold);text-decoration:none;">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;">Log In</button>
        </form>
        <div class="auth-switch" style="margin-top:20px;">
            Don't have an account? <a href="/register.php">Create one</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
