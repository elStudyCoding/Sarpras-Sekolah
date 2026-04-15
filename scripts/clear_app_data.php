<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden\n");
}

require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/../sql/clear_app_data.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "File SQL tidak ditemukan: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "Isi file SQL kosong atau gagal dibaca.\n");
    exit(1);
}

try {
    if (!mysqli_multi_query($conn, $sql)) {
        throw new RuntimeException(mysqli_error($conn));
    }

    do {
        $result = mysqli_store_result($conn);
        if ($result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));

    fwrite(STDOUT, "Data aplikasi berhasil dikosongkan via sql/clear_app_data.sql.\n");
    fwrite(STDOUT, "Akun users tidak dihapus.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Gagal mengosongkan data: " . $e->getMessage() . "\n");
    exit(1);
}
