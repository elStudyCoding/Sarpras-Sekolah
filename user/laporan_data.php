<?php
include '../config/session_public.php';
include '../config/database.php';

$data = mysqli_query($conn, "
    SELECT kategori, lokasi, status, tanggal_lapor
    FROM laporan
    ORDER BY tanggal_lapor DESC, id DESC
");

$rows = '';
while ($l = mysqli_fetch_assoc($data)) {
    if ($l['status'] === 'Ditangani') {
        $statusBadge = '<span class="badge badge-success">Ditangani</span>';
    } elseif ($l['status'] === 'Selesai') {
        $statusBadge = '<span class="badge">Selesai Ditangani</span>';
    } elseif ($l['status'] === 'Baru') {
        $statusBadge = '<span class="badge badge-warning">Belum Ditangani</span>';
    } else {
        $statusBadge = '<span class="badge">' . htmlspecialchars($l['status']) . '</span>';
    }

    $rows .= '<tr>'
        . '<td data-label="Kategori">' . htmlspecialchars($l['kategori']) . '</td>'
        . '<td data-label="Lokasi">' . htmlspecialchars($l['lokasi']) . '</td>'
        . '<td data-label="Status">' . $statusBadge . '</td>'
        . '<td data-label="Tanggal">' . date('d/m/Y', strtotime($l['tanggal_lapor'])) . '</td>'
        . '</tr>';
}

if ($rows === '') {
    $rows = '<tr><td colspan="4" class="empty-state">Belum ada laporan.</td></tr>';
}

header('Content-Type: application/json');
echo json_encode(['html' => $rows]);
