<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/public_actor.php';
include '../config/laporan_schema.php';
include '../config/school_hours.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'laporan';
$error = '';

ensure_laporan_schema($conn);
$isSchoolHours = school_hours_is_open_now();

if (isset($_POST['kirim'])) {
    if (!csrf_is_valid_request()) {
        $error = 'Permintaan tidak valid. Silakan refresh halaman lalu coba lagi.';
    } elseif (!school_hours_is_open_now()) {
        $error = 'sarpras hanya menerima permintaan dan laporan di jam sekolah saja';
    } else {
        $user_id  = get_public_actor_id($conn);
        $kelas    = trim($_POST['kelas'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $lokasi   = trim($_POST['lokasi'] ?? '');
        $deskripsi= trim($_POST['deskripsi'] ?? '');
        $no_telp  = trim($_POST['no_telp'] ?? '');
        $digits   = preg_replace('/\D+/', '', $no_telp);

        if ($user_id <= 0) {
            $error = 'Akun sistem publik tidak tersedia. Hubungi admin.';
        } elseif ($kelas === '' || $kategori === '' || $lokasi === '' || $deskripsi === '' || $no_telp === '') {
            $error = 'Semua field laporan wajib diisi.';
        } elseif (strlen($digits) < 10 || strlen($digits) > 16) {
            $error = 'Nomor WhatsApp tidak valid.';
        } else {
            $stmt = mysqli_prepare($conn, "
                INSERT INTO laporan (user_id, kelas, kategori, lokasi, deskripsi, no_telp, status, tanggal_lapor)
                VALUES (?, ?, ?, ?, ?, ?, 'Baru', CURDATE())
            ");
            mysqli_stmt_bind_param($stmt, "isssss", $user_id, $kelas, $kategori, $lokasi, $deskripsi, $no_telp);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: laporan.php?status=ok");
            exit;
        }
    }
}

$riwayat_laporan = mysqli_query($conn, "
    SELECT kategori, lokasi, status, tanggal_lapor
    FROM laporan
    ORDER BY tanggal_lapor DESC, id DESC
    LIMIT 10
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
<body class="laporan-page user-ui">
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Laporan Siswa</h1>
                </div>
            </header>
            <?php render_dashboard_topbar('Layanan Sarpras Sekolah', 'Publik', '../index.php?skipSplash=1', 'Menu Pilihan'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_user_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <?php if ($error !== ''): ?>
                        <div class="card alert-danger laporan-alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$isSchoolHours): ?>
                        <div class="card alert-warning laporan-alert">
                            Sarpras hanya melayani input pada jam sekolah (<?php echo htmlspecialchars(school_hours_label()); ?>).
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'ok'): ?>
                        <div class="card alert-success laporan-alert">
                            Laporan berhasil dikirim.
                        </div>
                    <?php endif; ?>

                    <div class="card laporan-hero">
                        <h2>Form Laporan Sarpras</h2>
                        <p class="muted mb-0">Laporkan kerusakan atau kebutuhan perbaikan agar cepat ditangani admin.</p>
                    </div>

                    <div class="laporan-grid">
                        <div class="card laporan-form-card">
                            <form method="post" class="laporan-form">
                                <?php echo csrf_input(); ?>
                                <div class="laporan-form-body">
                                    <div class="form-grid">
                                        <div>
                                            <label>Kelas</label>
                                            <input type="text" name="kelas" placeholder="Contoh: XI RPL 1" required>
                                        </div>

                                        <div>
                                            <label>Kategori</label>
                                            <select name="kategori" required>
                                                <option value="">Pilih kategori</option>
                                                <option value="Kerusakan">Kerusakan</option>
                                                <option value="Perbaikan">Perbaikan</option>
                                            </select>
                                        </div>

                                        <div class="laporan-col-full">
                                            <label>Lokasi/Barang</label>
                                            <input type="text" name="lokasi" placeholder="Contoh: Lab Komputer - Proyektor" required>
                                        </div>

                                        <div class="laporan-col-full">
                                            <label>No. WhatsApp</label>
                                            <input type="text" name="no_telp" placeholder="Contoh: 081234567890" required>
                                        </div>

                                        <div class="laporan-col-full">
                                            <label>Deskripsi</label>
                                            <textarea name="deskripsi" rows="5" placeholder="Jelaskan kondisi barang secara singkat dan jelas..." required></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="laporan-actions form-mobile-actions">
                                    <a href="dashboard.php" class="btn btn-outline">Kembali</a>
                                    <button type="submit" name="kirim" class="btn btn-primary" <?php echo !$isSchoolHours ? 'disabled' : ''; ?>>Kirim Laporan</button>
                                </div>
                            </form>
                        </div>

                        <div class="card laporan-guide">
                            <h3>Tips Laporan</h3>
                            <p class="muted">Isi data yang spesifik supaya admin bisa memproses lebih cepat.</p>
                            <ul>
                                <li>Tulis lokasi detail (ruang/kelas).</li>
                                <li>Sebutkan nama barang jika memungkinkan.</li>
                                <li>Deskripsikan masalah utama yang ditemukan.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Status Laporan Terbaru</h2>
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
                                <tbody>
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
    <div id="userToast" class="user-toast" aria-live="polite"></div>
    <script>
        (function () {
            if (window.innerWidth > 640) return;
            var toast = document.getElementById('userToast');
            if (!toast) return;
            var source = document.querySelector('.alert-danger, .alert-success');
            if (!source) return;
            toast.textContent = source.textContent.trim();
            toast.classList.add(source.classList.contains('alert-danger') ? 'error' : 'success');
            requestAnimationFrame(function () {
                toast.classList.add('show');
            });
            setTimeout(function () {
                toast.classList.remove('show');
            }, 2600);
        })();
    </script>
</body>
</html>
