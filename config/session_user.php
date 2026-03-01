<?php
include_once __DIR__ . '/security.php';
security_bootstrap();

session_name('sarpras_user');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}
