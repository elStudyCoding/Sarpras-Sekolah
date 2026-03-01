<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/permintaan_schema.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'permintaan';
ensure_permintaan_schema($conn);

$data = mysqli_query($conn, "
    SELECT p.*, u.nama AS nama_akun
    FROM permintaan_barang p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Minta Barang</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Data Minta Barang</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <h2>Daftar Minta Barang</h2>
                        <p class="muted">Alur minta barang ini hanya pencatatan input barang, jumlah, dan jam mapel.</p>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Input Oleh</th>
                                        <th>Kelas</th>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Jam Mapel</th>
                                        <th>Waktu Input</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($data && mysqli_num_rows($data) > 0): ?>
                                        <?php while($r = mysqli_fetch_assoc($data)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($r['nama_akun']); ?></td>
                                                <td><?php echo htmlspecialchars($r['kelas'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($r['barang']); ?></td>
                                                <td><?php echo (int)$r['jumlah']; ?></td>
                                                <td><?php echo htmlspecialchars($r['jam_mapel']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime((string)$r['tanggal_input'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="empty-state">Belum ada data minta barang.</td>
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
