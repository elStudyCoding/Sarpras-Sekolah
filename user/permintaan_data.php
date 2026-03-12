<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/permintaan_schema.php';

ensure_permintaan_schema($conn);

$data = mysqli_query($conn, "
    SELECT kelas, kategori, barang, jumlah, jam_mapel, status, tanggal_input
    FROM permintaan_barang
    ORDER BY id DESC
    LIMIT 20
");

$rows = '';
while ($m = mysqli_fetch_assoc($data)) {
    $rows .= '<tr>'
        . '<td data-label="Kelas">' . htmlspecialchars($m['kelas'] ?: '-') . '</td>'
        . '<td data-label="Kategori">' . htmlspecialchars($m['kategori'] ?? '-') . '</td>'
        . '<td data-label="Barang">' . htmlspecialchars($m['barang']) . '</td>'
        . '<td data-label="Jumlah">' . (int)$m['jumlah'] . '</td>'
        . '<td data-label="Jam Mapel">' . htmlspecialchars($m['jam_mapel']) . '</td>'
        . '<td data-label="Status">' . htmlspecialchars($m['status'] ?? 'Pending') . '</td>'
        . '<td data-label="Waktu Input">' . date('d/m/Y H:i', strtotime((string)$m['tanggal_input'])) . '</td>'
        . '</tr>';
}

if ($rows === '') {
    $rows = '<tr><td colspan="7" class="empty-state">Belum ada riwayat minta barang.</td></tr>';
}

header('Content-Type: application/json');
echo json_encode(['html' => $rows]);
