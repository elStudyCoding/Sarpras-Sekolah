<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/public_actor.php';
include '../config/permintaan_schema.php';
include '../config/school_hours.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'minta';
ensure_permintaan_schema($conn);
$isRequestHours = school_request_is_open_now();

if (isset($_POST['kirim'])) {
    if (!csrf_is_valid_request()) {
        $error = "Permintaan tidak valid. Silakan refresh halaman lalu coba lagi.";
    } elseif (!school_request_is_open_now()) {
        $error = "Sarpras hanya menerima permintaan dan peminjaman di jam sekolah saja.";
    }

    $user_id = get_public_actor_id($conn);
    $kelas = trim($_POST['kelas'] ?? '');
    $barang = trim($_POST['barang'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $jam_mapel = trim($_POST['jam_mapel'] ?? '');

    if (empty($error) && $user_id <= 0) {
        $error = "Akun sistem publik tidak tersedia. Hubungi admin.";
    } elseif (empty($error) && $kelas === '') {
        $error = "Kelas wajib diisi.";
    } elseif (empty($error) && $barang === '') {
        $error = "Nama barang wajib diisi.";
    } elseif (empty($error) && $jumlah <= 0) {
        $error = "Jumlah harus lebih dari 0.";
    } elseif (empty($error) && $jam_mapel === '') {
        $error = "Jam mapel wajib diisi.";
    } elseif (empty($error)) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO permintaan_barang (user_id, kelas, barang, jumlah, jam_mapel)
            VALUES (?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "issis", $user_id, $kelas, $barang, $jumlah, $jam_mapel);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            header("Location: minta.php?status=ok");
            exit;
        }
        $error = "Gagal menyimpan permintaan.";
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minta Barang</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body class="user-ui">
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Minta Barang Habis Pakai</h1>
                </div>
            </header>
            <?php render_dashboard_topbar('Layanan Sarpras Sekolah', 'Publik', '../index.php?skipSplash=1', 'Menu Pilihan'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_user_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <?php if (!empty($error)): ?>
                    <div class="card alert-danger">
                        <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!$isRequestHours): ?>
                    <div class="card alert-warning">
                        Sarpras hanya menerima permintaan dan peminjaman di jam sekolah saja (<?php echo htmlspecialchars(school_request_hours_label()); ?>).
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'ok'): ?>
                    <div class="card alert-success">
                        Minta barang berhasil disimpan.
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <h2>Form Minta Barang</h2>
                        <form method="post">
                            <?php echo csrf_input(); ?>
                            <div class="form-grid">
                                <div>
                                    <label for="kelas">Kelas</label>
                                    <input type="text" id="kelas" name="kelas" placeholder="Contoh: XI RPL 1" required>
                                </div>
                                <div>
                                    <label for="barang">Barang</label>
                                    <input type="text" id="barang" name="barang" placeholder="Contoh: Kertas A4" required>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div>
                                    <label for="jumlah">Jumlah</label>
                                    <input type="number" id="jumlah" name="jumlah" min="1" value="1" required>
                                </div>
                                <div>
                                    <label for="jam_mapel">Jam Mapel</label>
                                    <input type="text" id="jam_mapel" name="jam_mapel" placeholder="Contoh: Jam ke-3 Matematika" required>
                                </div>
                            </div>

                            <div class="controls form-mobile-actions">
                                <button type="submit" name="kirim" class="btn btn-primary" <?php echo !$isRequestHours ? 'disabled' : ''; ?>>Simpan Minta Barang</button>
                                <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
                            </div>
                        </form>
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
