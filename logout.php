<?php
session_start();
session_destroy();
header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/ngajiku') . '/login.php');
exit;
