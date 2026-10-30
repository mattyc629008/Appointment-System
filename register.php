<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isCustomerLoggedIn()) { header('Location: /dashboard.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (!$first_name || !$last_name || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers (first_name,last_name,email,phone,password) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $hashed);
            if ($stmt->execute()) {
                $_SESSION['customer_id'] = $conn->insert_id;
                $_SESSION['customer_name'] = $first_name;
                $cid = $_SESSION['customer_id'];
                $msg = "Welcome to Allen and Rej Bridal, $first_name! Book your first appointment to find your dream gown.";
                $ns = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?,?)");
                $ns->bind_param("is", $cid, $msg);
                $ns->execute();
                header('Location: /dashboard.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);font-style:italic;">Allen and Rej</div>
        </div>
        <h2>Create Your Account</h2>
        <p>Track appointments and get personalized notifications</p>
        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label>Email Address *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label>Password *</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group" style="margin-bottom:28px;">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;">Create Account</button>
        </form>
        <div class="auth-switch" style="margin-top:20px;">
            Already have an account? <a href="/login.php">Log in</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
