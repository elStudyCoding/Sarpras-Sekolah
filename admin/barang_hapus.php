<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/db_helper.php';
include '../config/audit_log.php';

if (!csrf_is_valid_request()) {
    header("Location: barang.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $delete = db_exec($conn, "DELETE FROM barang WHERE id = ?", "i", [$id]);
    if ($delete !== false && ($delete['affected_rows'] ?? 0) > 0) {
        audit_log_write($conn, [
            'user_id' => $_SESSION['id'] ?? 0,
            'user_name' => $_SESSION['nama'] ?? 'admin',
            'user_role' => $_SESSION['role'] ?? 'admin',
            'action' => 'delete',
            'entity' => 'barang',
            'entity_id' => $id,
        ]);
    }
}
header("Location: barang.php");
exit;
