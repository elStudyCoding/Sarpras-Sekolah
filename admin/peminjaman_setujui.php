<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/db_helper.php';
include '../config/audit_log.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';

ensure_peminjaman_schema($conn);
apply_overdue_penalties($conn);

if (!csrf_is_valid_request()) {
    header("Location: peminjaman.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: peminjaman.php");
    exit;
}

$update = db_exec(
    $conn,
    "UPDATE peminjaman
     SET status='D',
         due_at = COALESCE(due_at, TIMESTAMP(tanggal_pinjam, '15:20:00'))
     WHERE id = ?",
    "i",
    [$id]
);
if ($update !== false && ($update['affected_rows'] ?? 0) > 0) {
    audit_log_write($conn, [
        'user_id' => $_SESSION['id'] ?? 0,
        'user_name' => $_SESSION['nama'] ?? 'admin',
        'user_role' => $_SESSION['role'] ?? 'admin',
        'action' => 'approve',
        'entity' => 'peminjaman',
        'entity_id' => $id,
    ]);
}

header("Location: peminjaman.php");
exit;
?>
