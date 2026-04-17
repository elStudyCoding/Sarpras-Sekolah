<?php
// Optional local override:
// Create config/database.local.php and define DB_HOST/DB_USER/DB_PASS/DB_NAME.
if (is_file(__DIR__ . '/database.local.php')) {
    include_once __DIR__ . '/database.local.php';
}

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'sarpras');

$host = getenv('DB_HOST') ?: DB_HOST;
$user = getenv('DB_USER') ?: DB_USER;
$pass = getenv('DB_PASS') ?: DB_PASS;
$db   = getenv('DB_NAME') ?: DB_NAME;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($host, $user, $pass, $db);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal.');
}

mysqli_set_charset($conn, 'utf8mb4');
