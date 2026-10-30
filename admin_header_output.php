<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $adminPageTitle ?? 'Admin' ?> – Alenn and Rej Admin</title>
<link rel="stylesheet" href="/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="admin-wrap">
<aside class="admin-sidebar">
    <div class="admin-logo">
        <h2>Alenn and Rej</h2>
        <small>Admin Panel</small>
    </div>
    <nav class="admin-menu">
        <a href="/admin/" class="<?= $currentAdminPage === 'index' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="/admin/appointments.php" class="<?= $currentAdminPage === 'appointments' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> Appointments
        </a>
        <a href="/admin/customers.php" class="<?= $currentAdminPage === 'customers' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        <a href="/admin/gowns.php" class="<?= $currentAdminPage === 'gowns' ? 'active' : '' ?>">
            <i class="fas fa-tshirt"></i> Gown Catalog
        </a>
        </a>
        <a href="/admin/faqs.php" class="<?= $currentAdminPage === 'faqs' ? 'active' : '' ?>">
            <i class="fas fa-question-circle"></i> FAQs
        </a>

        <a href="/admin/admin_msg.php" class="<?= $currentAdminPage === 'chat' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i> Live Chat
            <?php
            $chatUnread = $conn->query("SELECT COUNT(*) as c FROM chat_messages WHERE sender='customer' AND is_read=0");
            $cu = $chatUnread ? $chatUnread->fetch_assoc()['c'] : 0;
            if ($cu > 0) echo "<span style='margin-left:auto;background:#E74C3C;color:white;border-radius:10px;font-size:10px;padding:1px 7px;'>$cu</span>";
            ?>
        </a>
        <a href="/admin/settings.php" class="<?= $currentAdminPage === 'settings' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="/" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Site
        </a>
        <a href="/admin/logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>
<!-- Overlay for mobile sidebar -->
<div class="admin-overlay" id="adminOverlay"></div>
<div class="admin-content">
<!-- Single mobile toggle — top of content area, CSS hides it on desktop -->
<div class="admin-mobile-bar" id="adminMobileBar">
    <button class="admin-mobile-toggle" id="adminMenuToggle" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>
    <span style="font-family:'Cormorant Garamond',serif;color:var(--gold);font-size:18px;font-style:italic;">Admin Panel</span>
</div>
<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
(function(){
    var toggle = document.getElementById('adminMenuToggle');
    var sidebar = document.querySelector('.admin-sidebar');
    var overlay = document.getElementById('adminOverlay');

    function openSidebar(){
        sidebar.classList.add('admin-sidebar-open');
        overlay.classList.add('admin-overlay-show');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar(){
        sidebar.classList.remove('admin-sidebar-open');
        overlay.classList.remove('admin-overlay-show');
        document.body.style.overflow = '';
    }
    if (toggle) toggle.addEventListener('click', openSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Ping admin last_seen every 55s
    (function pingAdmin(){
        fetch('../svc.php?mode=pulse&t=' + Date.now(), {
            method: 'POST',
            credentials: 'same-origin',
            referrerPolicy: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mode=pulse'
        }).catch(function(){});
        setTimeout(pingAdmin, 30000);
    })();
})();
</script>