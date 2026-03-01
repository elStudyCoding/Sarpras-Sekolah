<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';

ensure_peminjaman_schema($conn);

$q = mysqli_query($conn, "
    SELECT p.*, b.nama_barang
    FROM peminjaman p
    JOIN barang b ON p.barang_id=b.id
    ORDER BY p.tanggal_pinjam DESC
");

$rows = '';
while ($r = mysqli_fetch_assoc($q)) {
    if ($r['status'] == 'P') {
        $status = '<span class="badge badge-warning">Menunggu Persetujuan</span>';
    } elseif ($r['status'] == 'D') {
        if (!empty($r['due_at']) && strtotime((string)$r['due_at']) < time()) {
            $status = '<span class="badge badge-danger">Terlambat</span>';
        } else {
            $status = '<span class="badge badge-success">Sedang Dipinjam</span>';
        }
    } else {
        $status = '<span class="badge">Sudah Dikembalikan</span>';
    }

$rows .= '<tr>'
        . '<td data-label="Nama Siswa">' . htmlspecialchars($r['nama_siswa'] ?: '-') . '</td>'
        . '<td data-label="Kelas">' . htmlspecialchars($r['kelas'] ?: '-') . '</td>'
        . '<td data-label="Barang">' . htmlspecialchars($r['nama_barang']) . '</td>'
        . '<td data-label="Jumlah">' . (int)$r['jumlah_pinjam'] . '</td>'
        . '<td data-label="Tanggal Pinjam">' . date('d/m/Y', strtotime($r['tanggal_pinjam'])) . '</td>'
        . '<td data-label="Batas Kembali">' . (!empty($r['due_at']) ? date('d/m/Y H:i', strtotime((string)$r['due_at'])) : '-') . '</td>'
        . '<td data-label="Status">' . $status . '</td>'
        . '</tr>';
}

if ($rows === '') {
    $rows = '<tr><td colspan="7" class="empty-state">Belum ada peminjaman.</td></tr>';
}

header('Content-Type: application/json');
echo json_encode(['html' => $rows]);
