<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/public_actor.php';
include '../config/permintaan_schema.php';
include '../config/school_hours.php';
include '../config/request_guard.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'minta';
$error = '';
ensure_permintaan_schema($conn);
$isRequestHours = school_request_is_open_now();

if (isset($_POST['kirim'])) {
    $guardRejectedLogged = false;
    if (!csrf_is_valid_request()) {
        $error = "Permintaan tidak valid. Silakan refresh halaman lalu coba lagi.";
        request_guard_log_rejected($conn, 'minta', 'csrf_invalid');
        $guardRejectedLogged = true;
    } elseif (!request_rate_limit_allow('public_minta', 8, 120, $waitSeconds)) {
        $error = "Terlalu banyak percobaan. Coba lagi dalam {$waitSeconds} detik.";
        request_guard_log_rejected($conn, 'minta', 'session_rate_limited', ['wait_seconds' => $waitSeconds]);
        $guardRejectedLogged = true;
    } elseif (!request_rate_limit_allow_ip($conn, 'public_minta_ip', 20, 120, $waitIp)) {
        $error = "Terlalu banyak request dari jaringan ini. Coba lagi dalam {$waitIp} detik.";
        request_guard_log_rejected($conn, 'minta', 'ip_rate_limited', ['wait_seconds' => $waitIp]);
        $guardRejectedLogged = true;
    } elseif (!school_request_is_open_now()) {
        $error = "Sarpras hanya menerima permintaan dan peminjaman di jam sekolah saja.";
    }

    $user_id = get_public_actor_id($conn);
    $kelas = request_normalize_text($_POST['kelas'] ?? '', 50);
    $kategori = request_normalize_text($_POST['kategori'] ?? '', 40);
    $barang = request_normalize_text($_POST['barang'] ?? '', 100);
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $jam_mapel = request_normalize_text($_POST['jam_mapel'] ?? '', 80);
    $allowedKategori = ['Alat Tulis', 'Alat Kebersihan'];

    if (empty($error) && $user_id <= 0) {
        $error = "Akun sistem publik tidak tersedia. Hubungi admin.";
    } elseif (empty($error) && $kelas === '') {
        $error = "Kelas wajib diisi.";
    } elseif (empty($error) && !preg_match('/^[\\p{L}\\p{N}\\s\\.\\-\\/]{2,50}$/u', $kelas)) {
        $error = "Format kelas tidak valid.";
    } elseif (empty($error) && $kategori === '') {
        $error = "Kategori wajib diisi.";
    } elseif (empty($error) && !in_array($kategori, $allowedKategori, true)) {
        $error = "Kategori tidak valid.";
    } elseif (empty($error) && $barang === '') {
        $error = "Nama barang wajib diisi.";
    } elseif (empty($error) && !preg_match('/^[\\p{L}\\p{N}\\s\\.\\-\\/]{2,100}$/u', $barang)) {
        $error = "Nama barang mengandung karakter yang tidak diizinkan.";
    } elseif (empty($error) && $jumlah <= 0) {
        $error = "Jumlah harus lebih dari 0.";
    } elseif (empty($error) && $jumlah > 200) {
        $error = "Jumlah maksimal 200 per permintaan.";
    } elseif (empty($error) && $jam_mapel === '') {
        $error = "Jam mapel wajib diisi.";
    } elseif (empty($error) && request_mb_len($jam_mapel) < 2) {
        $error = "Jam mapel tidak valid.";
    } elseif (empty($error)) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO permintaan_barang (user_id, kelas, kategori, barang, jumlah, jam_mapel, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        mysqli_stmt_bind_param($stmt, "isssis", $user_id, $kelas, $kategori, $barang, $jumlah, $jam_mapel);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            header("Location: minta.php?status=ok");
            exit;
        }
        $error = "Gagal menyimpan permintaan.";
    }

    if (!empty($error) && !$guardRejectedLogged) {
        request_guard_log_rejected($conn, 'minta', 'validation_or_policy_reject', [
            'message' => $error,
            'kategori' => $kategori,
            'jumlah' => $jumlah,
        ]);
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

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'ok'): ?>
                    <div class="card alert-success">
                        Minta barang berhasil disimpan dan menunggu persetujuan admin.
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <h2>Form Minta Barang</h2>
                        <div id="requestHoursNotice" class="card alert-warning" style="display:none;"></div>
                        <form method="post" id="mintaForm">
                            <?php echo csrf_input(); ?>
                            <div class="form-grid">
                                <div>
                                    <label for="kelas">Kelas</label>
                                    <input type="text" id="kelas" name="kelas" placeholder="Contoh: XI RPL 1" maxlength="50" required>
                                </div>
                                <div>
                                    <label for="kategori">Kategori</label>
                                    <select id="kategori" name="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Alat Tulis">Alat Tulis</option>
                                        <option value="Alat Kebersihan">Alat Kebersihan</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="barang">Barang</label>
                                    <input type="text" id="barang" name="barang" placeholder="Contoh: Kertas A4" maxlength="100" required>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div>
                                    <label for="jumlah">Jumlah</label>
                                    <input type="number" id="jumlah" name="jumlah" min="1" max="200" value="1" required>
                                </div>
                                <div>
                                    <label for="jam_mapel">Jam Mapel</label>
                                    <input type="text" id="jam_mapel" name="jam_mapel" placeholder="Contoh: Jam ke-3 Matematika" maxlength="80" required>
                                </div>
                            </div>

                            <div class="controls form-mobile-actions">
                                <button type="submit" name="kirim" class="btn btn-primary">Simpan Minta Barang</button>
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
            var isRequestHours = <?php echo $isRequestHours ? 'true' : 'false'; ?>;
            var requestLabel = <?php echo json_encode(school_request_hours_label()); ?>;
            var requestMessage = 'Sarpras hanya menerima permintaan dan peminjaman di jam sekolah saja (' + requestLabel + ').';
            var form = document.getElementById('mintaForm');
            var notice = document.getElementById('requestHoursNotice');

            if (form) {
                form.addEventListener('submit', function (event) {
                    if (isRequestHours) return;
                    event.preventDefault();
                    if (notice) {
                        notice.textContent = requestMessage;
                        notice.style.display = 'block';
                        notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }

            if (window.innerWidth > 640) return;
            var toast = document.getElementById('userToast');
            if (!toast) return;
            var source = document.querySelector('.alert-danger, .alert-success, .alert-warning');
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
