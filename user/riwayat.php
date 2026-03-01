<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';
include '../config/permintaan_schema.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'riwayat';
ensure_peminjaman_schema($conn);
ensure_permintaan_schema($conn);
apply_overdue_penalties($conn);

$q = mysqli_query($conn, "
    SELECT p.*, b.nama_barang
    FROM peminjaman p
    JOIN barang b ON p.barang_id=b.id
    ORDER BY p.tanggal_pinjam DESC
");

$qPermintaan = mysqli_query($conn, "
    SELECT kelas, barang, jumlah, jam_mapel, tanggal_input
    FROM permintaan_barang
    ORDER BY id DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body class="user-ui">
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Riwayat Peminjaman</h1>
                </div>
            </header>
            <?php render_dashboard_topbar('Layanan Sarpras Sekolah', 'Publik', '../index.php?skipSplash=1', 'Menu Pilihan'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_user_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'ok'): ?>
                    <div class="card alert-success">
                        Pengajuan peminjaman berhasil disimpan.
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <h2>Riwayat Peminjaman</h2>
                        <div class="controls">
                            <button type="button" class="btn btn-outline btn-sm" id="btnRefreshRiwayat">Refresh Data</button>
                        </div>
                        <div class="table-wrap">
                            <table class="user-mobile-table">
                                <thead>
                                    <tr>
                                        <th>Nama Siswa</th>
                                        <th>Kelas</th>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Batas Kembali</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="riwayat-body">
                                    <?php while($r=mysqli_fetch_assoc($q)): ?>
                                    <tr>
                                        <td data-label="Nama Siswa"><?= htmlspecialchars($r['nama_siswa'] ?: '-'); ?></td>
                                        <td data-label="Kelas"><?= htmlspecialchars($r['kelas'] ?: '-'); ?></td>
                                        <td data-label="Barang"><?= htmlspecialchars($r['nama_barang']); ?></td>
                                        <td data-label="Jumlah"><?= (int)$r['jumlah_pinjam']; ?></td>
                                        <td data-label="Tanggal Pinjam"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])); ?></td>
                                        <td data-label="Batas Kembali"><?= !empty($r['due_at']) ? date('d/m/Y H:i', strtotime((string)$r['due_at'])) : '-'; ?></td>
                                        <td data-label="Status">
                                            <?php 
                                                if($r['status'] == 'P') {
                                                    echo '<span class="badge badge-warning">Menunggu Persetujuan</span>';
                                                } elseif($r['status'] == 'D') {
                                                    if (!empty($r['due_at']) && strtotime((string)$r['due_at']) < time()) {
                                                        echo '<span class="badge badge-danger">Terlambat</span>';
                                                    } else {
                                                        echo '<span class="badge badge-success">Sedang Dipinjam</span>';
                                                    }
                                                } elseif($r['status'] == 'K') {
                                                    echo '<span class="badge">Sudah Dikembalikan</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Riwayat Minta Barang</h2>
                        <div class="table-wrap">
                            <table class="user-mobile-table">
                                <thead>
                                    <tr>
                                        <th>Kelas</th>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Jam Mapel</th>
                                        <th>Waktu Input</th>
                                    </tr>
                                </thead>
                                <tbody id="permintaan-body">
                                    <?php if ($qPermintaan && mysqli_num_rows($qPermintaan) > 0): ?>
                                        <?php while($m = mysqli_fetch_assoc($qPermintaan)): ?>
                                        <tr>
                                            <td data-label="Kelas"><?= htmlspecialchars($m['kelas'] ?: '-'); ?></td>
                                            <td data-label="Barang"><?= htmlspecialchars($m['barang']); ?></td>
                                            <td data-label="Jumlah"><?= (int)$m['jumlah']; ?></td>
                                            <td data-label="Jam Mapel"><?= htmlspecialchars($m['jam_mapel']); ?></td>
                                            <td data-label="Waktu Input"><?= date('d/m/Y H:i', strtotime((string)$m['tanggal_input'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="empty-state">Belum ada riwayat minta barang.</td>
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
        async function refreshRiwayat() {
            try {
                const res = await fetch('riwayat_data.php', { cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                const tbody = document.getElementById('riwayat-body');
                if (tbody && data.html !== undefined) {
                    tbody.innerHTML = data.html;
                }
            } catch (e) {}
        }

        async function refreshPermintaan() {
            try {
                const res = await fetch('permintaan_data.php', { cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                const tbody = document.getElementById('permintaan-body');
                if (tbody && data.html !== undefined) {
                    tbody.innerHTML = data.html;
                }
            } catch (e) {}
        }

        const isMobile = window.innerWidth <= 640;
        const refreshBtn = document.getElementById('btnRefreshRiwayat');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                refreshRiwayat();
                refreshPermintaan();
            });
        }

        if (!isMobile) {
            setInterval(function () {
                if (!document.hidden) {
                    refreshRiwayat();
                    refreshPermintaan();
                }
            }, 8000);
        }
    </script>
</body>
</html>

