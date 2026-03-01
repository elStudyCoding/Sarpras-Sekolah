<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/laporan_schema.php';

ensure_laporan_schema($conn);

$laporan = mysqli_query($conn, "
    SELECT l.*
    FROM laporan l
    ORDER BY l.tanggal_lapor DESC, l.id DESC
");

$rows = '';
while ($l = mysqli_fetch_assoc($laporan)) {
    $csrfField = '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    $idField = '<input type="hidden" name="id" value="' . (int)$l['id'] . '">';
    if ($l['status'] === 'Baru') {
        $statusBadge = '<span class="badge badge-warning">Belum Ditangani</span>';
        $aksi = '<form method="post" action="laporan_update.php" class="inline-action">'
            . $csrfField . $idField
            . '<input type="hidden" name="status" value="Ditangani">'
            . '<button type="submit" class="btn btn-sm btn-success">Tandai Ditangani</button>'
            . '</form>';
    } elseif ($l['status'] === 'Ditangani') {
        $statusBadge = '<span class="badge badge-success">Ditangani</span>';
        $aksi = '<form method="post" action="laporan_update.php" class="inline-action">'
            . $csrfField . $idField
            . '<input type="hidden" name="status" value="Selesai">'
            . '<button type="submit" class="btn btn-sm btn-primary">Tandai Selesai</button>'
            . '</form>';
    } elseif ($l['status'] === 'Selesai') {
        $statusBadge = '<span class="badge">Selesai Ditangani</span>';
        $aksi = '<form method="post" action="laporan_update.php" class="inline-action">'
            . $csrfField . $idField
            . '<input type="hidden" name="status" value="Ditangani">'
            . '<button type="submit" class="btn btn-sm btn-secondary">Batalkan Selesai</button>'
            . '</form>';
    } else {
        $statusBadge = '<span class="badge">' . htmlspecialchars($l['status']) . '</span>';
        $aksi = '<span class="badge">-</span>';
    }

    $rows .= '<tr>'
        . '<td>' . htmlspecialchars($l['kelas']) . '</td>'
        . '<td>' . htmlspecialchars($l['kategori']) . '</td>'
        . '<td>' . htmlspecialchars($l['lokasi']) . '</td>'
        . '<td>' . htmlspecialchars($l['deskripsi']) . '</td>'
        . '<td>' . htmlspecialchars($l['no_telp'] ?: '-') . '</td>'
        . '<td>' . $statusBadge . '</td>'
        . '<td>' . date('d/m/Y', strtotime($l['tanggal_lapor'])) . '</td>'
        . '<td>' . $aksi . '</td>'
        . '</tr>';
}

if ($rows === '') {
    $rows = '<tr><td colspan="8" class="empty-state">Belum ada laporan.</td></tr>';
}

header('Content-Type: application/json');
echo json_encode(['html' => $rows]);
