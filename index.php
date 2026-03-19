<?php
// index.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
redirect(isLoggedIn() ? SITE_URL . '/router.php' : SITE_URL . '/login.php');
