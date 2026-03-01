<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/db_helper.php';
include '../config/audit_log.php';
include '../config/wa_notifier.php';
include '../config/wa_log.php';
include '../config/laporan_schema.php';

ensure_laporan_schema($conn);

if (!csrf_is_valid_request()) {
    header("Location: laporan.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed = ['Baru', 'Ditangani', 'Selesai'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    header("Location: laporan.php");
    exit;
}

$laporan = db_fetch_one(
    $conn,
    "SELECT kelas, kategori, lokasi, no_telp FROM laporan WHERE id = ? LIMIT 1",
    "i",
    [$id]
);

$update = db_exec(
    $conn,
    "UPDATE laporan SET status = ? WHERE id = ?",
    "si",
    [$status, $id]
);

if ($update !== false && ($update['affected_rows'] ?? 0) > 0) {
    $waResult = 'not_sent';
    $waError = '';
    if ($laporan && in_array($status, ['Ditangani', 'Selesai'], true)) {
        $kelas = trim((string)($laporan['kelas'] ?? '-'));
        $kategori = trim((string)($laporan['kategori'] ?? '-'));
        $lokasi = trim((string)($laporan['lokasi'] ?? '-'));
        $now = date('d/m/Y H:i');

        if ($status === 'Ditangani') {
            $message = "[E-Sarpras Sekolah]\n"
                . "Status Laporan: SEDANG DITANGANI\n"
                . "Kelas: {$kelas}\n"
                . "Kategori: {$kategori}\n"
                . "Lokasi: {$lokasi}\n"
                . "Update: {$now}\n"
                . "Tim sarpras sedang memproses laporan Anda.";
        } else {
            $message = "[E-Sarpras Sekolah]\n"
                . "Status Laporan: SELESAI DITANGANI\n"
                . "Kelas: {$kelas}\n"
                . "Kategori: {$kategori}\n"
                . "Lokasi: {$lokasi}\n"
                . "Update: {$now}\n"
                . "Terima kasih, laporan Anda sudah selesai diproses.";
        }
        $sent = wa_send_notification($laporan['no_telp'] ?? '', $message, $waError);
        $waResult = $sent ? 'sent' : 'failed';

        wa_log_write($conn, [
            'context' => 'laporan_status_update',
            'target_phone' => (string)($laporan['no_telp'] ?? ''),
            'message' => $message,
            'status' => $waResult,
            'provider' => wa_provider_name(),
            'provider_error' => $waError,
            'entity_type' => 'laporan',
            'entity_id' => $id,
        ]);
    }

    audit_log_write($conn, [
        'user_id' => $_SESSION['id'] ?? 0,
        'user_name' => $_SESSION['nama'] ?? 'admin',
        'user_role' => $_SESSION['role'] ?? 'admin',
        'action' => 'update_status',
        'entity' => 'laporan',
        'entity_id' => $id,
        'details' => [
            'status' => $status,
            'wa_result' => $waResult,
            'wa_error' => $waError,
        ],
    ]);
}

header("Location: laporan.php");
exit;
