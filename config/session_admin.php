<?php
include_once __DIR__ . '/security.php';
security_bootstrap();

session_name('sarpras_admin');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
