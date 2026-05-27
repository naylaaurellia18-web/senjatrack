<?php
require_once 'config.php'; // FIX: Wajib ada agar database session handler terdaftar
session_start();
session_unset();
session_destroy();
header("Location: /");
exit;
?>