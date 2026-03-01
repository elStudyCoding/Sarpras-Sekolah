<?php
include '../config/security.php';
security_bootstrap();

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

foreach (['sarpras_admin', 'sarpras_user', 'sarpras_public', 'sarpras_login'] as $name) {
    session_name($name);
    session_start();
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

header("Location: login.php");
exit;
?>
