<?php
// includes/auth_check.php
// Sertakan di setiap halaman yang butuh login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Pastikan $user selalu tersedia di semua halaman yang include file ini
$user = currentUser();
