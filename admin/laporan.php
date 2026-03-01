<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/laporan_schema.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'laporan';

ensure_laporan_schema($conn);

$laporan = mysqli_query($conn, "
    SELECT l.*
    FROM laporan l
    ORDER BY l.tanggal_lapor DESC, l.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Siswa</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Laporan Siswa</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <h2>Daftar Laporan</h2>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Kelas</th>
                                        <th>Kategori</th>
                                        <th>Lokasi</th>
                                        <th>Deskripsi</th>
                                        <th>No. WA</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="laporan-body">
                                    <?php if (mysqli_num_rows($laporan) > 0): ?>
                                        <?php while($l = mysqli_fetch_assoc($laporan)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($l['kelas']); ?></td>
                                                <td><?= htmlspecialchars($l['kategori']); ?></td>
                                                <td><?= htmlspecialchars($l['lokasi']); ?></td>
                                                <td><?= htmlspecialchars($l['deskripsi']); ?></td>
                                                <td><?= htmlspecialchars($l['no_telp'] ?: '-'); ?></td>
                                                <td>
                                                    <?php
                                                        $status = $l['status'];
                                                        if ($status === 'Ditangani') {
                                                            echo '<span class="badge badge-success">Ditangani</span>';
                                                        } elseif ($status === 'Selesai') {
                                                            echo '<span class="badge">Selesai Ditangani</span>';
                                                        } elseif ($status === 'Baru') {
                                                            echo '<span class="badge badge-warning">Belum Ditangani</span>';
                                                        } else {
                                                            echo '<span class="badge">' . htmlspecialchars($status) . '</span>';
                                                        }
                                                    ?>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($l['tanggal_lapor'])); ?></td>
                                                <td>
                                                    <?php if ($l['status'] === 'Baru'): ?>
                                                        <form method="post" action="laporan_update.php" class="inline-action">
                                                            <?php echo csrf_input(); ?>
                                                            <input type="hidden" name="id" value="<?= (int)$l['id']; ?>">
                                                            <input type="hidden" name="status" value="Ditangani">
                                                            <button type="submit" class="btn btn-sm btn-success">Tandai Ditangani</button>
                                                        </form>
                                                    <?php elseif ($l['status'] === 'Ditangani'): ?>
                                                        <form method="post" action="laporan_update.php" class="inline-action">
                                                            <?php echo csrf_input(); ?>
                                                            <input type="hidden" name="id" value="<?= (int)$l['id']; ?>">
                                                            <input type="hidden" name="status" value="Selesai">
                                                            <button type="submit" class="btn btn-sm btn-primary">Tandai Selesai</button>
                                                        </form>
                                                    <?php elseif ($l['status'] === 'Selesai'): ?>
                                                        <form method="post" action="laporan_update.php" class="inline-action">
                                                            <?php echo csrf_input(); ?>
                                                            <input type="hidden" name="id" value="<?= (int)$l['id']; ?>">
                                                            <input type="hidden" name="status" value="Ditangani">
                                                            <button type="submit" class="btn btn-sm btn-secondary">Batalkan Selesai</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="badge">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="empty-state">Belum ada laporan.</td>
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
    <script>
        async function refreshLaporan() {
            try {
                const res = await fetch('laporan_data.php', { cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                const tbody = document.getElementById('laporan-body');
                if (tbody && data.html !== undefined) {
                    tbody.innerHTML = data.html;
                }
            } catch (e) {}
        }
        setInterval(refreshLaporan, 5000);
    </script>
</body>
</html>
