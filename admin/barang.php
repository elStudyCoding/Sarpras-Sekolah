<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/barang_schema.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'barang';
ensure_barang_schema($conn);
$filterKategori = trim((string)($_GET['kategori'] ?? ''));
$kategoriList = mysqli_query($conn, "
    SELECT DISTINCT kategori
    FROM barang
    WHERE kategori IS NOT NULL AND kategori <> ''
    ORDER BY kategori ASC
");

$sql = "SELECT * FROM barang";
if ($filterKategori !== '') {
    $sql .= " WHERE kategori = ?";
}
$sql .= " ORDER BY nama_barang ASC";


$data = null;
if ($filterKategori !== '') {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $filterKategori);
    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $data = mysqli_query($conn, $sql);
}
?>
<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Data Barang</title>
        <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Data Barang</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <h2>Data Barang</h2>
                            <div class="controls">
                                <a href="barang_tambah.php" class="btn btn-success">Tambah Barang</a>
                            </div>
                        </div>
                        <form method="get" class="form-grid filter-bar">
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
                                <a href="barang.php" class="btn btn-outline">Reset</a>
                            </div>
                        </form>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="row-no">No</th>
                                        <th>Nama</th>
                                        <th>Kategori</th>
                                        <th>Jumlah</th>
                                        <th>Kondisi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($b=mysqli_fetch_assoc($data)): ?>
                                        <tr>
                                            <td class="row-no"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($b['nama_barang']); ?></td>
                                            <td><?= htmlspecialchars($b['kategori'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($b['jumlah']); ?></td>
                                            <td><?= htmlspecialchars($b['kondisi']); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a class="edit" href="barang_edit.php?id=<?= $b['id']; ?>">Edit</a>
                                                    <form method="post" action="barang_hapus.php" class="inline-action">
                                                        <?php echo csrf_input(); ?>
                                                        <input type="hidden" name="id" value="<?= (int)$b['id']; ?>">
                                                        <button type="submit" class="delete">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top:16px">
                            <a href="dashboard.php" class="btn btn-outline">← Kembali ke Dashboard</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
