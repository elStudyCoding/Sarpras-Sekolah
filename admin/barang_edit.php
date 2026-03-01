<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/db_helper.php';
include '../config/audit_log.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'barang';
$id = (int)($_GET['id'] ?? 0);
$error = '';

$b = db_fetch_one($conn, "SELECT * FROM barang WHERE id = ? LIMIT 1", "i", [$id]);

if (!$b) {
    header("Location: barang.php");
    exit;
}

if (isset($_POST['update'])) {
    if (!csrf_is_valid_request()) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {
        $tambah = (int)($_POST['jumlah'] ?? 0);
        $nama = trim($_POST['nama_barang'] ?? '');
        $kondisi = trim($_POST['kondisi'] ?? '');

        if ($nama === '' || $tambah < 0) {
            $error = 'Nama barang wajib diisi dan tambah jumlah tidak boleh negatif.';
        } else {
            $update = db_exec(
                $conn,
                "UPDATE barang SET nama_barang = ?, jumlah = jumlah + ?, kondisi = ? WHERE id = ?",
                "sisi",
                [$nama, $tambah, $kondisi, $id]
            );
            if ($update === false) {
                $error = 'Gagal memperbarui data barang.';
            } else {
                audit_log_write($conn, [
                    'user_id' => $_SESSION['id'] ?? 0,
                    'user_name' => $_SESSION['nama'] ?? 'admin',
                    'user_role' => $_SESSION['role'] ?? 'admin',
                    'action' => 'update',
                    'entity' => 'barang',
                    'entity_id' => $id,
                    'details' => [
                        'nama_barang' => $nama,
                        'tambah_jumlah' => $tambah,
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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Edit Barang</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <h2>Edit Barang</h2>
                        <?php if ($error !== ''): ?>
                            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <?php echo csrf_input(); ?>
                            <div class="form-grid">
                                <div>
                                    <label>Nama Barang</label>
                                    <input type="text" name="nama_barang" value="<?= htmlspecialchars($b['nama_barang']); ?>" required>
                                </div>

                                <div>
                                    <label>Tambah Jumlah</label>
                                    <input type="number" name="jumlah" value="0" required>
                                </div>

                                <div style="grid-column:1/-1">
                                    <label>Kondisi</label>
                                    <input type="text" name="kondisi" value="<?= htmlspecialchars($b['kondisi']); ?>">
                                </div>
                            </div>

                            <div style="display:flex;gap:12px;justify-content:flex-end">
                                <a href="barang.php" class="btn btn-outline">Batal</a>
                                <button type="submit" name="update" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
