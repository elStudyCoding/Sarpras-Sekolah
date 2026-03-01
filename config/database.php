<?php
// Optional local override:
// Create config/database.local.php and define DB_HOST/DB_USER/DB_PASS/DB_NAME.
if (is_file(__DIR__ . '/database.local.php')) {
    include_once __DIR__ . '/database.local.php';
}

$host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
$user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root');
$pass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');
$db   = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'sarpras');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    http_response_code(500);
    exit('Koneksi database gagal.');
}

mysqli_set_charset($conn, 'utf8mb4');
