<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/barang_schema.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';

ensure_peminjaman_schema($conn);
ensure_barang_schema($conn);
apply_overdue_penalties($conn);

$filterNama = trim((string)($_GET['nama'] ?? ''));
$filterKelas = trim((string)($_GET['kelas'] ?? ''));
$filterKategori = trim((string)($_GET['kategori'] ?? ''));

$sql = "
    SELECT p.*, u.nama AS nama_akun, b.nama_barang, b.kategori
    FROM peminjaman p
    JOIN users u ON p.user_id=u.id
    JOIN barang b ON p.barang_id=b.id
";

$where = ["b.kategori <> 'Alat Tulis'"];
if ($filterNama !== '') {
    $safe = mysqli_real_escape_string($conn, $filterNama);
    $like = '%' . $safe . '%';
    $where[] = "(p.nama_siswa LIKE '{$like}' OR u.nama LIKE '{$like}')";
}

if ($filterKelas !== '') {
    $safe = mysqli_real_escape_string($conn, $filterKelas);
    $like = '%' . $safe . '%';
    $where[] = "p.kelas LIKE '{$like}'";
}

if ($filterKategori !== '') {
    $safe = mysqli_real_escape_string($conn, $filterKategori);
    $where[] = "b.kategori = '{$safe}'";
}

$sql .= " WHERE " . implode(" AND ", $where);

$sql .= " ORDER BY p.id DESC";

$q = mysqli_query($conn, $sql);

$rows = '';
while ($p = mysqli_fetch_assoc($q)) {
    $csrfField = '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    $idField = '<input type="hidden" name="id" value="' . (int)$p['id'] . '">';

    if ($p['status'] == 'P') {
        $status = '<span class="badge badge-warning">Pending</span>';
        $aksi = '<form method="post" action="peminjaman_setujui.php" class="inline-action">'
            . $csrfField . $idField
            . '<button type="submit" class="btn btn-sm btn-primary">Setujui</button>'
            . '</form>';
    } elseif ($p['status'] == 'D') {
        if (!empty($p['due_at']) && strtotime((string)$p['due_at']) < time()) {
            $status = '<span class="badge badge-danger">Terlambat</span>';
        } else {
            $status = '<span class="badge badge-success">Dipinjam</span>';
        }
        $aksi = '<form method="post" action="peminjaman_kembali.php" class="inline-action">'
            . $csrfField . $idField
            . '<button type="submit" class="btn btn-sm btn-warning">Kembalikan</button>'
            . '</form>';
    } else {
        $status = '<span class="badge">Dikembalikan</span>';
        $aksi = '<span class="badge">Selesai</span>';
    }

    $rows .= '<tr>'
        . '<td>' . htmlspecialchars($p['nama_siswa'] ?: $p['nama_akun']) . '</td>'
        . '<td>' . htmlspecialchars($p['kelas'] ?: '-') . '</td>'
        . '<td>' . htmlspecialchars($p['nama_barang']) . '</td>'
        . '<td>' . htmlspecialchars($p['kategori'] ?? '-') . '</td>'
        . '<td>' . (int)$p['jumlah_pinjam'] . '</td>'
        . '<td>' . (!empty($p['due_at']) ? date('d/m/Y H:i', strtotime((string)$p['due_at'])) : '-') . '</td>'
        . '<td>' . $status . '</td>'
        . '<td>' . $aksi . '</td>'
        . '</tr>';
}

if ($rows === '') {
    $rows = '<tr><td colspan="8" class="empty-state">Belum ada data peminjaman.</td></tr>';
}

header('Content-Type: application/json');
echo json_encode(['html' => $rows]);
