<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/db_helper.php';
include '../config/barang_schema.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';

ensure_peminjaman_schema($conn);
ensure_barang_schema($conn);
apply_overdue_penalties($conn);

date_default_timezone_set('Asia/Jakarta');
$bulan = (int)($_GET['bulan'] ?? date('n'));
if ($bulan < 1 || $bulan > 12) {
    $bulan = (int)date('n');
}
$tahun = (int)date('Y');

$filterNama = trim((string)($_GET['nama'] ?? ''));
$filterKelas = trim((string)($_GET['kelas'] ?? ''));
$filterKategori = trim((string)($_GET['kategori'] ?? ''));

$sql = "
    SELECT p.*, u.nama AS nama_akun, b.nama_barang, b.kategori
    FROM peminjaman p
    JOIN users u ON p.user_id=u.id
    JOIN barang b ON p.barang_id=b.id
";

$where = ["b.kategori <> ?"];
$types = "s";
$params = ['Alat Tulis'];

if ($filterNama !== '') {
    $like = '%' . $filterNama . '%';
    $where[] = "(p.nama_siswa LIKE ? OR u.nama LIKE ?)";
    $types .= "ss";
    $params[] = $like;
    $params[] = $like;
}

if ($filterKelas !== '') {
    $like = '%' . $filterKelas . '%';
    $where[] = "p.kelas LIKE ?";
    $types .= "s";
    $params[] = $like;
}

if ($filterKategori !== '') {
    $where[] = "b.kategori = ?";
    $types .= "s";
    $params[] = $filterKategori;
}

$where[] = "(
    (p.tanggal_pinjam IS NOT NULL AND YEAR(p.tanggal_pinjam) = ? AND MONTH(p.tanggal_pinjam) = ?)
    OR
    (p.tanggal_kembali IS NOT NULL AND YEAR(p.tanggal_kembali) = ? AND MONTH(p.tanggal_kembali) = ?)
)";
$types .= "iiii";
$params[] = $tahun;
$params[] = $bulan;
$params[] = $tahun;
$params[] = $bulan;

$sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY p.id DESC";

$stmt = db_stmt_execute($conn, $sql, $types, $params);
$q = $stmt ? mysqli_stmt_get_result($stmt) : false;

$filename = "peminjaman_sarpras_{$bulan}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
if ($output === false) {
    http_response_code(500);
    exit('Gagal membuat file CSV.');
}

// UTF-8 BOM agar Excel membaca karakter dengan benar.
fwrite($output, "\xEF\xBB\xBF");

fputcsv(
    $output,
    ['Nama Siswa', 'Kelas', 'Nama Barang', 'Kategori', 'Jumlah', 'Tanggal Pinjam', 'Tanggal Kembali', 'Status'],
    ';'
);

while ($q && ($p = mysqli_fetch_assoc($q))) {
    $status = 'Dikembalikan';
    if ($p['status'] === 'P') {
        $status = 'Pending';
    } elseif ($p['status'] === 'D') {
        if (!empty($p['due_at']) && strtotime((string)$p['due_at']) < time()) {
            $status = 'Terlambat';
        } else {
            $status = 'Dipinjam';
        }
    }

    $tanggalPinjam = !empty($p['tanggal_pinjam'])
        ? date('d/m/Y', strtotime((string)$p['tanggal_pinjam']))
        : '-';
    $tanggalKembali = !empty($p['tanggal_kembali'])
        ? date('d/m/Y', strtotime((string)$p['tanggal_kembali']))
        : '-';

    fputcsv(
        $output,
        [
            (string)($p['nama_siswa'] ?: $p['nama_akun']),
            (string)($p['kelas'] ?: '-'),
            (string)($p['nama_barang'] ?? ''),
            (string)($p['kategori'] ?? ''),
            (string)($p['jumlah_pinjam'] ?? ''),
            $tanggalPinjam,
            $tanggalKembali,
            $status,
        ],
        ';'
    );
}

if ($stmt) {
    mysqli_stmt_close($stmt);
}

fclose($output);
exit;
