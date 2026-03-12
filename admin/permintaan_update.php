<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/db_helper.php';
include '../config/audit_log.php';
include '../config/permintaan_schema.php';

ensure_permintaan_schema($conn);

if (!csrf_is_valid_request()) {
    header("Location: permintaan.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
$allowed = ['approve', 'reject'];

if ($id <= 0 || !in_array($action, $allowed, true)) {
    header("Location: permintaan.php");
    exit;
}

$newStatus = ($action === 'approve') ? 'Disetujui' : 'Ditolak';

$update = db_exec(
    $conn,
    "UPDATE permintaan_barang
     SET status = ?, approved_by = ?, approved_at = NOW()
     WHERE id = ? AND status = 'Pending'",
    "sii",
    [$newStatus, (int)($_SESSION['id'] ?? 0), $id]
);

if ($update !== false && ($update['affected_rows'] ?? 0) > 0) {
    audit_log_write($conn, [
        'user_id' => $_SESSION['id'] ?? 0,
        'user_name' => $_SESSION['nama'] ?? 'admin',
        'user_role' => $_SESSION['role'] ?? 'admin',
        'action' => $action === 'approve' ? 'approve' : 'reject',
        'entity' => 'permintaan',
        'entity_id' => $id,
        'details' => [
            'status' => $newStatus,
        ],
    ]);
}

header("Location: permintaan.php");
exit;
