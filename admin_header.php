<?php
// admin/includes/admin_header.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }
require_once __DIR__ . '/../../includes/db.php';

$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
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

        <a href="/" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Site
        </a>
        <a href="/admin/logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>
<div class="admin-content">
<!-- Mobile Admin Top Bar (visible only on mobile) -->
<div style="display:none;" id="mobileAdminBar" class="mobile-admin-bar">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--dark);margin:-36px -14px 20px;">
        <span style="font-family:'Cormorant Garamond',serif;color:var(--gold);font-size:18px;font-style:italic;">Admin Panel</span>
        <button onclick="document.getElementById('mobileAdminMenu').classList.toggle('open')" style="background:none;border:none;color:white;font-size:20px;cursor:pointer;">☰</button>
    </div>
    <div id="mobileAdminMenu" style="display:none;background:var(--dark);margin:-20px -14px 20px;padding:10px 0;">
        <a href="/admin/" style="display:block;padding:12px 20px;color:#ccc;text-decoration:none;font-size:14px;border-left:3px solid transparent;">📊 Dashboard</a>
        <a href="/admin/appointments.php" style="display:block;padding:12px 20px;color:#ccc;text-decoration:none;font-size:14px;border-left:3px solid transparent;">📅 Appointments</a>
        <a href="/admin/customers.php" style="display:block;padding:12px 20px;color:#ccc;text-decoration:none;font-size:14px;border-left:3px solid transparent;">👥 Customers</a>
        <a href="/admin/gowns.php" style="display:block;padding:12px 20px;color:#ccc;text-decoration:none;font-size:14px;border-left:3px solid transparent;">👗 Gown Catalog</a>
        <a href="/admin/faqs.php" style="display:block;padding:12px 20px;color:#ccc;text-decoration:none;font-size:14px;border-left:3px solid transparent;">❓ FAQs</a>
        <a href="/admin/messages.php" style="display:block;padding:12px 20px;color:#ccc;text-decoration:none;font-size:14px;border-left:3px solid transparent;">✉️ Messages</a>
        <a href="/admin/logout.php" style="display:block;padding:12px 20px;color:#e74c3c;text-decoration:none;font-size:14px;border-left:3px solid transparent;">🚪 Logout</a>
    </div>
</div>
<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
if(window.innerWidth <= 768) {
    document.getElementById('mobileAdminBar').style.display='block';
}
document.getElementById('mobileAdminMenu') && document.querySelectorAll('#mobileAdminMenu a').forEach(function(a){
    a.addEventListener('click',function(){ document.getElementById('mobileAdminMenu').style.display='none'; });
});
</script>
