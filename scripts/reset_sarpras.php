<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/barang_schema.php';
require_once __DIR__ . '/../config/peminjaman_schema.php';
require_once __DIR__ . '/../config/permintaan_schema.php';
require_once __DIR__ . '/../config/laporan_schema.php';

/**
 * Usage:
 * php scripts/reset_sarpras.php --admin-password="yourStrongPassword"
 */
$options = getopt('', ['admin-password:']);
$adminPassword = isset($options['admin-password']) ? trim((string)$options['admin-password']) : '';

if ($adminPassword === '') {
    fwrite(STDERR, "Error: --admin-password wajib diisi.\n");
    fwrite(STDERR, "Contoh: php scripts/reset_sarpras.php --admin-password=\"adminsarprassrikandi1\"\n");
    exit(1);
}

if (strlen($adminPassword) < 12) {
    fwrite(STDERR, "Error: password admin minimal 12 karakter.\n");
    exit(1);
}

ensure_peminjaman_schema($conn);
ensure_permintaan_schema($conn);
ensure_laporan_schema($conn);
ensure_barang_schema($conn);

function table_exists(mysqli $conn, string $tableName): bool
{
    $escaped = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$escaped}'");
    return $result !== false && mysqli_num_rows($result) > 0;
}

$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
$publicHash = password_hash('public_school_internal_only', PASSWORD_DEFAULT);

mysqli_begin_transaction($conn);

try {
    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=0');

    $tablesToTruncate = [
        'peminjaman',
        'permintaan_barang',
        'laporan',
        'audit_logs',
        'class_penalties',
        'wa_notification_logs',
        'barang',
        'users',
    ];

    foreach ($tablesToTruncate as $table) {
        if (table_exists($conn, $table)) {
            mysqli_query($conn, "TRUNCATE TABLE {$table}");
        }
    }

    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=1');

    $stmtUser = mysqli_prepare(
        $conn,
        'INSERT INTO users (id, nama, username, password, role) VALUES (?, ?, ?, ?, ?)'
    );

    $id = 1;
    $nama = 'Admin Sarpras';
    $username = 'admin';
    $role = 'admin';
    mysqli_stmt_bind_param($stmtUser, 'issss', $id, $nama, $username, $adminHash, $role);
    mysqli_stmt_execute($stmtUser);

    $id = 2;
    $nama = 'Layanan Sarpras Sekolah';
    $username = 'public_school';
    $role = 'user';
    mysqli_stmt_bind_param($stmtUser, 'issss', $id, $nama, $username, $publicHash, $role);
    mysqli_stmt_execute($stmtUser);
    mysqli_stmt_close($stmtUser);

    $stmtBarang = mysqli_prepare(
        $conn,
        'INSERT INTO barang (id, nama_barang, kategori, jumlah, kondisi) VALUES (?, ?, ?, ?, ?)'
    );

    $seedBarang = [
        [1, 'LCD', 'Elektronik', 8, 'Baik'],
        [2, 'Kabel HDMI', 'Elektronik', 15, 'Baik'],
        [3, 'Speaker', 'Elektronik', 6, 'Baik'],
        [4, 'Kertas A4', 'Alat Tulis', 50, 'Baik'],
        [5, 'Folio Bergaris', 'Alat Tulis', 40, 'Baik'],
        [6, 'Spidol', 'Alat Tulis', 20, 'Baik'],
        [7, 'Penghapus Papan', 'Alat Kebersihan', 15, 'Baik'],
        [8, 'Sapu', 'Alat Kebersihan', 10, 'Baik'],
    ];

    foreach ($seedBarang as $row) {
        [$barangId, $namaBarang, $kategori, $jumlah, $kondisi] = $row;
        mysqli_stmt_bind_param($stmtBarang, 'issis', $barangId, $namaBarang, $kategori, $jumlah, $kondisi);
        mysqli_stmt_execute($stmtBarang);
    }
    mysqli_stmt_close($stmtBarang);

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=1');
    fwrite(STDERR, "Reset gagal: " . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Reset berhasil.\n");
fwrite(STDOUT, "Admin login: username=admin, password sesuai --admin-password.\n");
fwrite(STDOUT, "Data awal barang: LCD, Kabel HDMI, Speaker.\n");
