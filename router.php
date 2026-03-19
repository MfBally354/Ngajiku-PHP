<?php
// router.php — redirect sesuai role setelah login
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$role = $_SESSION['user']['role'] ?? 'santri';
redirect(SITE_URL . '/pages/' . $role . '/dashboard.php');
