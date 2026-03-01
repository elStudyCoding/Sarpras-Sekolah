<?php

// Cara pakai:
// php C:\laragon\www\E-Sarpras\cron\test_wa.php 08xxxxxxxxxx "Pesan test"

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

include __DIR__ . '/../config/wa_notifier.php';
include __DIR__ . '/../config/database.php';
include __DIR__ . '/../config/wa_log.php';

$phone = trim((string)($argv[1] ?? ''));
$message = trim((string)($argv[2] ?? ''));

if ($phone === '') {
    echo "Gagal: nomor WA wajib diisi.\n";
    exit(1);
}

if ($message === '') {
    $message = "[E-Sarpras Sekolah]\nTest notifikasi berhasil.\nIntegrasi WhatsApp aktif dan siap digunakan.";
}

$err = '';
$ok = wa_send_notification($phone, $message, $err);

if ($ok) {
    wa_log_write($conn, [
        'context' => 'manual_test',
        'target_phone' => $phone,
        'message' => $message,
        'status' => 'sent',
        'provider' => wa_provider_name(),
        'provider_error' => '',
        'entity_type' => 'test',
        'entity_id' => 0,
    ]);
    echo "SUKSES: notifikasi terkirim ke " . wa_normalize_phone($phone) . "\n";
    exit(0);
}

wa_log_write($conn, [
    'context' => 'manual_test',
    'target_phone' => $phone,
    'message' => $message,
    'status' => 'failed',
    'provider' => wa_provider_name(),
    'provider_error' => $err,
    'entity_type' => 'test',
    'entity_id' => 0,
]);
echo "GAGAL: {$err}\n";
exit(1);
