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

/* ambil barang_id dari peminjaman */
$peminjaman = db_fetch_one(
    $conn,
    "SELECT barang_id, jumlah_pinjam, kelas, due_at, overdue_penalty_applied
     FROM peminjaman
     WHERE id = ? AND status='D'
     LIMIT 1",
    "i",
    [$id]
);

if ($peminjaman) {
    $barang_id = (int)$peminjaman['barang_id'];
    $jumlah_pinjam = (int)$peminjaman['jumlah_pinjam'];
    $kelas = trim((string)($peminjaman['kelas'] ?? ''));
    $dueAt = $peminjaman['due_at'] ?? null;
    $penaltyApplied = (int)($peminjaman['overdue_penalty_applied'] ?? 0);

    if ($penaltyApplied === 0 && !empty($dueAt) && strtotime((string)$dueAt) < time()) {
        if ($kelas !== '') {
            kelas_penalty_add_points($conn, $kelas, PENALTY_POINTS_OVERDUE);
        }
        db_exec(
            $conn,
            "UPDATE peminjaman SET overdue_penalty_applied = 1 WHERE id = ?",
            "i",
            [$id]
        );
    }

    /* update status peminjaman */
    db_exec(
        $conn,
        "UPDATE peminjaman SET status='K', tanggal_kembali=CURDATE() WHERE id = ?",
        "i",
        [$id]
    );

    /* kembalikan stok barang */
    if ($jumlah_pinjam > 0) {
        db_exec(
            $conn,
            "UPDATE barang SET jumlah = jumlah + ? WHERE id = ?",
            "ii",
            [$jumlah_pinjam, $barang_id]
        );
    }

    audit_log_write($conn, [
        'user_id' => $_SESSION['id'] ?? 0,
        'user_name' => $_SESSION['nama'] ?? 'admin',
        'user_role' => $_SESSION['role'] ?? 'admin',
        'action' => 'return',
        'entity' => 'peminjaman',
        'entity_id' => $id,
        'details' => [
            'barang_id' => $barang_id,
            'jumlah_pinjam' => $jumlah_pinjam,
            'penalty_applied' => ($penaltyApplied === 0 && !empty($dueAt) && strtotime((string)$dueAt) < time()) ? 1 : 0,
        ],
    ]);
}

header("Location: peminjaman.php");
exit;
