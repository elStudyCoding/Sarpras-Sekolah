<?php
include '../config/session_admin.php';
include '../config/database.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'dashboard';

$top_barang = mysqli_query($conn, "
    SELECT b.nama_barang, SUM(p.jumlah_pinjam) AS total_pinjam
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    GROUP BY p.barang_id
    ORDER BY total_pinjam DESC
    LIMIT 5
");

$laporan_terbaru = mysqli_query($conn, "
    SELECT l.*, COALESCE(u.nama, 'Umum') AS nama
    FROM laporan l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.tanggal_lapor DESC, l.id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Dashboard Admin</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <h2>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?></h2>
                        <p class="muted">Gunakan menu di sebelah kiri untuk mengelola data.</p>
                    </div>

                    <div class="card">
                        <h2>Barang Paling Sering Dipinjam</h2>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Total Dipinjam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($top_barang) > 0): ?>
                                        <?php while($t = mysqli_fetch_assoc($top_barang)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($t['nama_barang']); ?></td>
                                                <td><?= (int)$t['total_pinjam']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="empty-state">Belum ada data peminjaman.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Overview Laporan Siswa (Terbaru)</h2>
                        <p class="muted">Hanya menampilkan 5 laporan terbaru. Laporan lebih lama hanya ada di database.</p>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Kategori</th>
                                        <th>Lokasi</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($laporan_terbaru) > 0): ?>
                                        <?php while($l = mysqli_fetch_assoc($laporan_terbaru)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($l['nama']); ?></td>
                                                <td><?= htmlspecialchars($l['kelas']); ?></td>
                                                <td><?= htmlspecialchars($l['kategori']); ?></td>
                                                <td><?= htmlspecialchars($l['lokasi']); ?></td>
                                                <td><?= htmlspecialchars($l['status']); ?></td>
                                                <td><?= date('d/m/Y', strtotime($l['tanggal_lapor'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="empty-state">Belum ada laporan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
