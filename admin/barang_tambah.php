<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/db_helper.php';
include '../config/audit_log.php';
include '../config/barang_schema.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'barang';
$error = '';
ensure_barang_schema($conn);

if (isset($_POST['simpan'])) {
    if (!csrf_is_valid_request()) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {
        $nama = trim($_POST['nama_barang'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $jumlah = (int)($_POST['jumlah'] ?? 0);
        $kondisi = trim($_POST['kondisi'] ?? '');

        if ($nama === '' || $kategori === '' || $jumlah < 1) {
            $error = 'Nama barang, kategori, dan jumlah wajib diisi dengan benar.';
        } else {
            $insert = db_exec(
                $conn,
                "INSERT INTO barang (nama_barang, kategori, jumlah, kondisi) VALUES (?, ?, ?, ?)",
                "ssis",
                [$nama, $kategori, $jumlah, $kondisi]
            );

            if ($insert === false) {
                $error = 'Gagal menyimpan data barang.';
            } else {
                audit_log_write($conn, [
                    'user_id' => $_SESSION['id'] ?? 0,
                    'user_name' => $_SESSION['nama'] ?? 'admin',
                    'user_role' => $_SESSION['role'] ?? 'admin',
                    'action' => 'create',
                    'entity' => 'barang',
                    'entity_id' => $insert['insert_id'] ?? 0,
                        'details' => [
                            'nama_barang' => $nama,
                            'kategori' => $kategori,
                            'jumlah' => $jumlah,
                            'kondisi' => $kondisi,
                        ],
                    ]);

                header("Location: barang.php");
                exit;
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
    <title>Tambah Barang</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Tambah Barang</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <h2>Form Tambah Barang</h2>
                        <?php if ($error !== ''): ?>
                            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <?php echo csrf_input(); ?>
                            <div class="form-grid">
                                <div>
                                    <label>Nama Barang</label>
                                    <input type="text" name="nama_barang" required>
                                </div>
                                <div>
                                    <label>Kategori</label>
                                    <select name="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Elektronik">Elektronik</option>
                                        <option value="Alat Tulis">Alat Tulis</option>
                                        <option value="Alat Kebersihan">Alat Kebersihan</option>
                                    </select>
                                </div>

                                <div>
                                    <label>Jumlah</label>
                                    <input type="number" name="jumlah" min="1" required>
                                </div>

                                <div class="col-full">
                                    <label>Kondisi</label>
                                    <input type="text" name="kondisi" placeholder="Contoh: Baik">
                                </div>
                            </div>

                            <div class="controls" style="justify-content:flex-end">
                                <a href="barang.php" class="btn btn-outline">Batal</a>
                                <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
