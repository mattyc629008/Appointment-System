<?php
session_start();
session_unset();
session_destroy();

header("Location: /admin/admin-login.php");
exit();
?>