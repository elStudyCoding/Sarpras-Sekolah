<?php
include '../config/session_public.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/public_actor.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';
include '../config/school_hours.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'pinjam';
ensure_peminjaman_schema($conn);
$isRequestHours = school_request_is_open_now();

$barang = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang");
$barang_tidak_tersedia = mysqli_query($conn, "SELECT * FROM barang WHERE jumlah <= 0 ORDER BY nama_barang");

// Simpan barang tersedia ke array untuk bisa diloop berkali-kali
$barang_tersedia = [];
$barang_query = mysqli_query($conn, "SELECT * FROM barang WHERE jumlah > 0 ORDER BY nama_barang");
while($row = mysqli_fetch_assoc($barang_query)) {
    $barang_tersedia[] = $row;
}

if (isset($_POST['pinjam'])) {
    if (!csrf_is_valid_request()) {
        $error = "Permintaan tidak valid. Silakan refresh halaman lalu coba lagi.";
    } elseif (!school_request_is_open_now()) {
        $error = "Sarpras hanya menerima permintaan dan peminjaman di jam sekolah saja.";
    }

    $user_id = get_public_actor_id($conn);
    $nama_siswa = trim($_POST['nama_siswa'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $jumlah_pinjam = (int)$_POST['jumlah_pinjam'];
    
    // Validasi jumlah pinjam
    if (empty($error) && $user_id <= 0) {
        $error = "Akun sistem publik tidak tersedia. Hubungi admin.";
    } elseif (empty($error) && $kelas === '') {
        $error = "Kelas wajib diisi.";
    } elseif (empty($error) && $no_telp === '') {
        $error = "Nomor WhatsApp wajib diisi untuk pengingat pengembalian.";
    } elseif (empty($error) && $jumlah_pinjam <= 0) {
        $error = "Jumlah pinjam harus lebih dari 0!";
    } elseif (empty($error)) {
        $kelasState = kelas_penalty_is_blocked($conn, $kelas);
        if (!empty($kelasState['blocked'])) {
            $untilText = date('d/m/Y H:i', strtotime((string)$kelasState['suspended_until']));
            $error = "Kelas ini sedang diblokir sementara sampai {$untilText} karena keterlambatan pengembalian.";
        }
    }

    if (empty($error)) {
        $kelasState = kelas_penalty_is_blocked($conn, $kelas);
        if (!empty($kelasState['points'])) {
            $warning = "Poin penalti kelas saat ini: " . (int)$kelasState['points'] . ".";
        }
    }

    if (empty($error)) {
        $noTelpDigits = preg_replace('/[^0-9]/', '', $no_telp);
        if ($noTelpDigits === '' || strlen($noTelpDigits) < 10) {
            $error = "Nomor WhatsApp minimal 10 digit.";
        } else {
            $no_telp = $noTelpDigits;
        }
    }

    if (empty($error)) {
        $kelas = preg_replace('/\s+/', ' ', $kelas);
    }

    if (empty($error)) {
        // Cek stok tersedia
        $cekStmt = mysqli_prepare($conn, "SELECT jumlah FROM barang WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($cekStmt, "i", $barang_id);
        mysqli_stmt_execute($cekStmt);
        $cekResult = mysqli_stmt_get_result($cekStmt);
        $row_stok = mysqli_fetch_assoc($cekResult);
        mysqli_stmt_close($cekStmt);
        
        if (!$row_stok) {
            $error = "Barang tidak ditemukan.";
        } elseif ((int)$row_stok['jumlah'] < $jumlah_pinjam) {
            $error = "Stok tidak mencukupi! Tersedia: " . $row_stok['jumlah'];
        } else {
            $insertStmt = mysqli_prepare($conn, "
                INSERT INTO peminjaman (user_id, nama_siswa, kelas, no_telp, barang_id, tanggal_pinjam, due_at, jumlah_pinjam, status)
                VALUES (?, ?, ?, ?, ?, CURDATE(), TIMESTAMP(CURDATE(), '15:20:00'), ?, 'P')
            ");
            mysqli_stmt_bind_param($insertStmt, "isssii", $user_id, $nama_siswa, $kelas, $no_telp, $barang_id, $jumlah_pinjam);
            $insertOk = mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);

            if ($insertOk) {
                $updateStmt = mysqli_prepare($conn, "UPDATE barang SET jumlah = jumlah - ? WHERE id = ?");
                mysqli_stmt_bind_param($updateStmt, "ii", $jumlah_pinjam, $barang_id);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
                header("Location: riwayat.php?status=ok");
                exit;
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Barang</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body class="user-ui">
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Pinjam Barang</h1>
                </div>
            </header>
            <?php render_dashboard_topbar('Layanan Sarpras Sekolah', 'Publik', '../index.php?skipSplash=1', 'Menu Pilihan'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_user_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <?php if (!$isRequestHours): ?>
                    <div class="card alert-warning">
                        Sarpras hanya menerima permintaan dan peminjaman di jam sekolah saja (<?php echo htmlspecialchars(school_request_hours_label()); ?>).
                    </div>
                    <?php endif; ?>

                    <?php if (mysqli_num_rows($barang_tidak_tersedia) > 0): ?>
                    <div class="card alert-warning">
                        <h3>Perhatian: Barang Sedang Digunakan</h3>
                        <p>Maaf, barang-barang berikut sedang digunakan oleh kelas yang membutuhkan alat ini:</p>
                        <ul>
                            <?php while($b = mysqli_fetch_assoc($barang_tidak_tersedia)): ?>
                            <li><strong><?= htmlspecialchars($b['nama_barang']); ?></strong></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                    <div class="card alert-danger">
                        <strong>Error!</strong> <?= htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($warning)): ?>
                    <div class="card alert-warning">
                        <?= htmlspecialchars($warning); ?>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <h2>Pinjam Barang</h2>
                        <form method="post">
                            <?php echo csrf_input(); ?>
                            <div class="form-grid">
                                <div>
                                    <label for="nama_siswa">Nama Siswa</label>
                                    <input type="text" id="nama_siswa" name="nama_siswa" placeholder="Contoh: Budi Santoso" required>
                                </div>
                                <div>
                                    <label for="kelas">Kelas</label>
                                    <input type="text" id="kelas" name="kelas" placeholder="Contoh: XI RPL 1" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="no_telp">No. WhatsApp Peminjam:</label>
                                <input type="text" id="no_telp" name="no_telp" class="form-control" placeholder="Contoh: 081234567890" required>
                            </div>
                            <div class="form-group">
                                <label for="barang_id">Pilih Barang:</label>
                                <select id="barang_id" name="barang_id" class="form-control" required>
                                    <option value="">Pilih Barang</option>
                                    <?php foreach($barang_tersedia as $b): ?>
                                    <option value="<?= (int)$b['id']; ?>"><?= htmlspecialchars($b['nama_barang']); ?> (Tersedia: <?= (int)$b['jumlah']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="jumlah_pinjam">Jumlah Pinjam:</label>
                                <input type="number" id="jumlah_pinjam" name="jumlah_pinjam" class="form-control" value="1" min="1" required>
                            </div>

                            <div class="controls form-mobile-actions">
                                <button name="pinjam" class="btn btn-primary" <?php echo !$isRequestHours ? 'disabled' : ''; ?>>Pinjam Barang</button>
                                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
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
