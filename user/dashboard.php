<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';
include '../config/permintaan_schema.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'dashboard';
ensure_peminjaman_schema($conn);
ensure_permintaan_schema($conn);
apply_overdue_penalties($conn);

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS laporan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        kelas VARCHAR(50) NOT NULL,
        kategori VARCHAR(20) NOT NULL,
        lokasi VARCHAR(100) NOT NULL,
        deskripsi TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Baru',
        tanggal_lapor DATE NOT NULL
    )
");

$riwayat_peminjaman = mysqli_query($conn, "
    SELECT p.*, b.nama_barang
    FROM peminjaman p
    JOIN barang b ON p.barang_id=b.id
    ORDER BY p.tanggal_pinjam DESC, p.id DESC
");

$riwayat_laporan = mysqli_query($conn, "
    SELECT kategori, lokasi, status, tanggal_lapor
    FROM laporan
    ORDER BY tanggal_lapor DESC, id DESC
");

$ringkasan = [
    'dipinjam' => 0,
    'terlambat' => 0,
    'minta_hari_ini' => 0,
];
$qDipinjam = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'D'");
if ($qDipinjam) {
    $row = mysqli_fetch_assoc($qDipinjam);
    $ringkasan['dipinjam'] = (int)($row['total'] ?? 0);
}
$qTerlambat = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'D' AND due_at IS NOT NULL AND due_at < NOW()");
if ($qTerlambat) {
    $row = mysqli_fetch_assoc($qTerlambat);
    $ringkasan['terlambat'] = (int)($row['total'] ?? 0);
}
$qMinta = mysqli_query($conn, "SELECT COUNT(*) AS total FROM permintaan_barang WHERE DATE(tanggal_input) = CURDATE()");
if ($qMinta) {
    $row = mysqli_fetch_assoc($qMinta);
    $ringkasan['minta_hari_ini'] = (int)($row['total'] ?? 0);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sarpras Sekolah</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
    <style>
        .quick-grid-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 14px;
        }
        .quick-card {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            min-height: 96px;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #9fc9e5;
            background: linear-gradient(145deg, #fafdff 0%, #eaf4fd 100%);
            box-shadow: 0 8px 18px rgba(12, 53, 84, 0.1);
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .quick-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(12, 53, 84, 0.16);
            border-color: #70afd8;
            text-decoration: none;
        }
        .quick-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: .74rem;
            font-weight: 800;
            color: #0e4a6c;
            background: linear-gradient(135deg, #d4edff 0%, #b5dcf7 100%);
            border: 1px solid #97c8ea;
            flex: 0 0 36px;
        }
        .quick-text {
            display: grid;
            gap: 4px;
        }
        .quick-text strong {
            color: #0f3b5d;
            font-size: .97rem;
            line-height: 1.3;
        }
        .quick-text span {
            color: #4c6d86;
            font-size: .84rem;
            line-height: 1.45;
        }
        .status-strip {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #c7dced;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .status-strip .badge {
            padding: 8px 12px;
            font-size: .88rem;
            border-radius: 12px;
        }
        @media (max-width: 900px) {
            .quick-grid-cards {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .status-strip {
                margin-top: 14px;
                padding-top: 12px;
            }
        }
        @media (max-width: 640px) {
            .quick-grid-cards {
                gap: 10px;
            }
            .quick-card {
                min-height: 80px;
                padding: 12px;
                gap: 10px;
            }
            .quick-badge {
                width: 32px;
                height: 32px;
                flex-basis: 32px;
                font-size: .68rem;
            }
            .quick-text strong {
                font-size: .9rem;
            }
            .quick-text span {
                font-size: .79rem;
            }
            .status-strip .badge {
                font-size: .8rem;
                padding: 7px 10px;
            }
        }
    </style>
</head>
<body class="user-ui">
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Dashboard Sarpras Sekolah</h1>
                </div>
            </header>
            <?php render_dashboard_topbar('Layanan Sarpras Sekolah', 'Publik', '../index.php?skipSplash=1', 'Menu Pilihan'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_user_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <h2>Selamat datang</h2>
                        <p class="muted">Dashboard menampilkan ringkasan peminjaman dan laporan untuk seluruh sekolah.</p>
                        <div class="controls">
                            <button type="button" class="btn btn-outline btn-sm" id="btnRefreshUser">Refresh Data</button>
                        </div>
                    </div>

                    <div class="card quick-actions">
                        <h2>Akses Cepat</h2>
                        <p class="muted">Pilih menu sesuai kebutuhan supaya proses lebih cepat.</p>
                        <div class="quick-grid-cards">
                            <a href="pinjam.php" class="quick-card">
                                <span class="quick-badge">PB</span>
                                <span class="quick-text">
                                    <strong>Pinjam Barang</strong>
                                    <span>Ajukan peminjaman alat kelas</span>
                                </span>
                            </a>
                            <a href="minta.php" class="quick-card">
                                <span class="quick-badge">MB</span>
                                <span class="quick-text">
                                    <strong>Minta Barang</strong>
                                    <span>Input barang habis pakai</span>
                                </span>
                            </a>
                            <a href="laporan.php" class="quick-card">
                                <span class="quick-badge">LP</span>
                                <span class="quick-text">
                                    <strong>Buat Laporan</strong>
                                    <span>Laporkan kerusakan sarpras</span>
                                </span>
                            </a>
                        </div>
                        <div class="status-strip">
                            <span class="badge badge-success">Sedang Dipinjam: <?php echo $ringkasan['dipinjam']; ?></span>
                            <span class="badge badge-danger">Terlambat: <?php echo $ringkasan['terlambat']; ?></span>
                            <span class="badge badge-info">Minta Barang Hari Ini: <?php echo $ringkasan['minta_hari_ini']; ?></span>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Riwayat Peminjaman Barang</h2>
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
                                    <?php if ($riwayat_peminjaman && mysqli_num_rows($riwayat_peminjaman) > 0): ?>
                                        <?php while($r = mysqli_fetch_assoc($riwayat_peminjaman)): ?>
                                        <tr>
                                            <td data-label="Nama Siswa"><?php echo htmlspecialchars($r['nama_siswa'] ?: '-'); ?></td>
                                            <td data-label="Kelas"><?php echo htmlspecialchars($r['kelas'] ?: '-'); ?></td>
                                            <td data-label="Barang"><?php echo htmlspecialchars($r['nama_barang']); ?></td>
                                            <td data-label="Jumlah"><?php echo (int)$r['jumlah_pinjam']; ?></td>
                                            <td data-label="Tanggal Pinjam"><?php echo date('d/m/Y', strtotime($r['tanggal_pinjam'])); ?></td>
                                            <td data-label="Batas Kembali"><?php echo !empty($r['due_at']) ? date('d/m/Y H:i', strtotime((string)$r['due_at'])) : '-'; ?></td>
                                            <td data-label="Status">
                                                <?php
                                                    if ($r['status'] == 'P') {
                                                        echo '<span class="badge badge-warning">Menunggu Persetujuan</span>';
                                                    } elseif ($r['status'] == 'D') {
                                                        if (!empty($r['due_at']) && strtotime((string)$r['due_at']) < time()) {
                                                            echo '<span class="badge badge-danger">Terlambat</span>';
                                                        } else {
                                                            echo '<span class="badge badge-success">Sedang Dipinjam</span>';
                                                        }
                                                    } elseif ($r['status'] == 'K') {
                                                        echo '<span class="badge">Sudah Dikembalikan</span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="empty-state">Belum ada riwayat peminjaman.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Riwayat Laporan</h2>
                        <div class="table-wrap">
                            <table class="user-mobile-table">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th>Lokasi</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody id="laporan-body">
                                    <?php if ($riwayat_laporan && mysqli_num_rows($riwayat_laporan) > 0): ?>
                                        <?php while($l = mysqli_fetch_assoc($riwayat_laporan)): ?>
                                        <tr>
                                            <td data-label="Kategori"><?php echo htmlspecialchars($l['kategori']); ?></td>
                                            <td data-label="Lokasi"><?php echo htmlspecialchars($l['lokasi']); ?></td>
                                            <td data-label="Status">
                                                <?php
                                                    if ($l['status'] === 'Ditangani') {
                                                        echo '<span class="badge badge-success">Ditangani</span>';
                                                    } elseif ($l['status'] === 'Selesai') {
                                                        echo '<span class="badge">Selesai Ditangani</span>';
                                                    } elseif ($l['status'] === 'Baru') {
                                                        echo '<span class="badge badge-warning">Belum Ditangani</span>';
                                                    } else {
                                                        echo '<span class="badge">' . htmlspecialchars($l['status']) . '</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td data-label="Tanggal"><?php echo date('d/m/Y', strtotime($l['tanggal_lapor'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">Belum ada laporan.</td>
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

        async function refreshLaporanUser() {
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

        const isMobile = window.innerWidth <= 640;
        const refreshBtn = document.getElementById('btnRefreshUser');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                refreshRiwayat();
                refreshLaporanUser();
            });
        }

        if (!isMobile) {
            const refreshInterval = 8000;
            setInterval(function () {
                if (!document.hidden) refreshRiwayat();
            }, refreshInterval);
            setInterval(function () {
                if (!document.hidden) refreshLaporanUser();
            }, refreshInterval);
        }
    </script>
</body>
</html>
