<?php
// Messages page retired - redirecting to chat
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php'); exit;
}
header('Location: /admin/chat.php'); exit;
