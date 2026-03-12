<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/barang_schema.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'peminjaman';
ensure_peminjaman_schema($conn);
ensure_barang_schema($conn);
apply_overdue_penalties($conn);

$filterNama = trim((string)($_GET['nama'] ?? ''));
$filterKelas = trim((string)($_GET['kelas'] ?? ''));
$filterKategori = trim((string)($_GET['kategori'] ?? ''));

$kategoriList = mysqli_query($conn, "
    SELECT DISTINCT kategori
    FROM barang
    WHERE kategori IS NOT NULL AND kategori <> '' AND kategori <> 'Alat Tulis'
    ORDER BY kategori ASC
");

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

$kelas_penalty = mysqli_query($conn, "
    SELECT kelas_label, points, suspended_until
    FROM class_penalties
    WHERE points > 0 OR (suspended_until IS NOT NULL AND suspended_until > NOW())
    ORDER BY points DESC, suspended_until DESC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Data Peminjaman</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <?php if ($kelas_penalty && mysqli_num_rows($kelas_penalty) > 0): ?>
                    <div class="card alert-warning">
                        <h3>Status Disiplin Kelas</h3>
                        <ul>
                            <?php while($kp = mysqli_fetch_assoc($kelas_penalty)): ?>
                                <li>
                                    <strong><?= htmlspecialchars($kp['kelas_label']); ?></strong>
                                    - Poin: <?= (int)$kp['points']; ?>
                                    <?php if (!empty($kp['suspended_until']) && strtotime((string)$kp['suspended_until']) > time()): ?>
                                        (Suspend sampai <?= date('d/m/Y H:i', strtotime((string)$kp['suspended_until'])); ?>)
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <h2>Data Peminjaman</h2>
                        <form method="get" class="form-grid filter-bar">
                            <div>
                                <label>Nama</label>
                                <input type="text" name="nama" value="<?= htmlspecialchars($filterNama); ?>" placeholder="Cari nama siswa">
                            </div>
                            <div>
                                <label>Kelas</label>
                                <input type="text" name="kelas" value="<?= htmlspecialchars($filterKelas); ?>" placeholder="Cari kelas">
                            </div>
                            <div>
                                <label>Kategori</label>
                                <select name="kategori">
                                    <option value="">Semua Kategori</option>
                                    <?php if ($kategoriList && mysqli_num_rows($kategoriList) > 0): ?>
                                        <?php while ($cat = mysqli_fetch_assoc($kategoriList)): ?>
                                            <?php $val = (string)($cat['kategori'] ?? ''); ?>
                                            <?php if ($val !== ''): ?>
                                                <option value="<?= htmlspecialchars($val); ?>" <?= $filterKategori === $val ? 'selected' : ''; ?>><?= htmlspecialchars($val); ?></option>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="controls">
                                <button type="submit" class="btn btn-primary">Terapkan</button>
                                <a href="peminjaman.php" class="btn btn-outline">Reset</a>
                            </div>
                        </form>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nama Siswa</th>
                                        <th>Kelas</th>
                                        <th>Barang</th>
                                        <th>Kategori</th>
                                        <th>Jumlah</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="peminjaman-body">
                                    <?php if ($q && mysqli_num_rows($q) > 0): ?>
                                        <?php while($p=mysqli_fetch_assoc($q)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['nama_siswa'] ?: $p['nama_akun']); ?></td>
                                            <td><?= htmlspecialchars($p['kelas'] ?: '-'); ?></td>
                                            <td><?= htmlspecialchars($p['nama_barang']); ?></td>
                                            <td><?= htmlspecialchars($p['kategori'] ?? '-'); ?></td>
                                            <td><?= (int)$p['jumlah_pinjam']; ?></td>
                                            <td>
                                                <?php if (!empty($p['due_at'])): ?>
                                                    <?= date('d/m/Y H:i', strtotime((string)$p['due_at'])); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    if($p['status'] == 'P') {
                                                        echo '<span class="badge badge-warning">Pending</span>';
                                                    } elseif($p['status'] == 'D') {
                                                        if (!empty($p['due_at']) && strtotime((string)$p['due_at']) < time()) {
                                                            echo '<span class="badge badge-danger">Terlambat</span>';
                                                        } else {
                                                            echo '<span class="badge badge-success">Dipinjam</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="badge">Dikembalikan</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if($p['status']=='D'): ?>
                                                <form method="post" action="peminjaman_kembali.php" class="inline-action">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="id" value="<?= (int)$p['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning">Kembalikan</button>
                                                </form>
                                                <?php elseif($p['status']=='P'): ?>
                                                <form method="post" action="peminjaman_setujui.php" class="inline-action">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="id" value="<?= (int)$p['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">Setujui</button>
                                                </form>
                                                <?php else: ?>
                                                <span class="badge">Selesai</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="empty-state">Belum ada data peminjaman.</td>
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
        async function refreshPeminjaman() {
            try {
                const params = new URLSearchParams(window.location.search);
                const res = await fetch('peminjaman_data.php?' + params.toString(), { cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                const tbody = document.getElementById('peminjaman-body');
                if (tbody && data.html !== undefined) {
                    tbody.innerHTML = data.html;
                }
            } catch (e) {}
        }
        setInterval(refreshPeminjaman, 5000);
    </script>
</body>
</html>
