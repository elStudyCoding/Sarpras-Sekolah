<?php

// Jalankan via Task Scheduler/cron, idealnya tiap hari jam 15:00.
// Contoh manual:
// php C:\laragon\www\E-Sarpras\cron\send_return_reminders.php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

include __DIR__ . '/../config/database.php';
include __DIR__ . '/../config/db_helper.php';
include __DIR__ . '/../config/peminjaman_schema.php';
include __DIR__ . '/../config/peminjaman_policy.php';
include __DIR__ . '/../config/wa_notifier.php';
include __DIR__ . '/../config/wa_log.php';

ensure_peminjaman_schema($conn);
apply_overdue_penalties($conn);

$windowMinutes = 40;

$rows = mysqli_query($conn, "
    SELECT p.id, p.nama_siswa, p.kelas, p.no_telp, p.due_at, b.nama_barang
    FROM peminjaman p
    JOIN barang b ON b.id = p.barang_id
    WHERE p.status = 'D'
      AND p.no_telp IS NOT NULL
      AND p.no_telp <> ''
      AND p.reminder_sent_at IS NULL
      AND p.due_at IS NOT NULL
      AND p.due_at > NOW()
      AND TIMESTAMPDIFF(MINUTE, NOW(), p.due_at) BETWEEN 0 AND {$windowMinutes}
    ORDER BY p.due_at ASC
");

$checked = 0;
$sent = 0;
$failed = 0;

if ($rows) {
    while ($r = mysqli_fetch_assoc($rows)) {
        $checked++;
        $nama = trim((string)($r['nama_siswa'] ?? ''));
        if ($nama === '') {
            $nama = 'Peminjam';
        }

        $message = "Pengingat Sarpras: {$nama} ({$r['kelas']}) mohon kembalikan {$r['nama_barang']} sebelum "
            . date('H:i', strtotime((string)$r['due_at']))
            . ". Terima kasih.";

        $message = "[E-Sarpras Sekolah]\n"
            . "Pengingat Pengembalian Barang\n"
            . "Nama: {$nama}\n"
            . "Kelas: " . ($r['kelas'] ?: '-') . "\n"
            . "Barang: " . ($r['nama_barang'] ?: '-') . "\n"
            . "Batas Kembali: " . date('d/m/Y H:i', strtotime((string)$r['due_at'])) . "\n"
            . "Mohon dikembalikan sebelum jam pulang (15:20).";

        $waError = '';
        $ok = wa_send_notification($r['no_telp'], $message, $waError);

        if ($ok) {
            db_exec(
                $conn,
                "UPDATE peminjaman SET reminder_sent_at = NOW() WHERE id = ?",
                "i",
                [(int)$r['id']]
            );
            $sent++;
        } else {
            $failed++;
        }

        wa_log_write($conn, [
            'context' => 'pengingat_pengembalian',
            'target_phone' => (string)($r['no_telp'] ?? ''),
            'message' => $message,
            'status' => $ok ? 'sent' : 'failed',
            'provider' => wa_provider_name(),
            'provider_error' => $waError,
            'entity_type' => 'peminjaman',
            'entity_id' => (int)$r['id'],
        ]);
    }
}

header('Content-Type: text/plain; charset=UTF-8');
echo "checked={$checked}; sent={$sent}; failed={$failed}" . PHP_EOL;
