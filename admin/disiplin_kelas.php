<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/csrf.php';
include '../config/peminjaman_schema.php';
include '../config/peminjaman_policy.php';
include '../config/db_helper.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'disiplin';
ensure_peminjaman_schema($conn);
apply_overdue_penalties($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid_request()) {
        $error = 'Permintaan tidak valid.';
    } else {
        $kelasKey = trim((string)($_POST['kelas_key'] ?? ''));
        $action = trim((string)($_POST['action_type'] ?? ''));

        if ($kelasKey !== '' && $action === 'reset_points') {
            db_exec(
                $conn,
                "UPDATE class_penalties
                 SET points = 0, suspended_until = NULL
                 WHERE kelas_key = ?",
                "s",
                [$kelasKey]
            );
            header("Location: disiplin_kelas.php?status=reset");
            exit;
        }

        if ($kelasKey !== '' && $action === 'extend_suspend') {
            $days = (int)($_POST['suspend_days'] ?? 7);
            if ($days < 1) {
                $days = 1;
            }
            if ($days > 30) {
                $days = 30;
            }
            db_exec(
                $conn,
                "UPDATE class_penalties
                 SET suspended_until = DATE_ADD(GREATEST(COALESCE(suspended_until, NOW()), NOW()), INTERVAL " . $days . " DAY)
                 WHERE kelas_key = ?",
                "s",
                [$kelasKey]
            );
            header("Location: disiplin_kelas.php?status=suspend");
            exit;
        }
    }
}

$q = mysqli_query($conn, "
    SELECT kelas_key, kelas_label, points, suspended_until, last_violation_at, updated_at
    FROM class_penalties
    ORDER BY points DESC, suspended_until DESC, updated_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disiplin Kelas</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Disiplin Kelas</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <?php if (!empty($error)): ?>
                    <div class="card alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'reset'): ?>
                    <div class="card alert-success">Poin kelas berhasil direset.</div>
                    <?php endif; ?>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'suspend'): ?>
                    <div class="card alert-success">Masa suspend kelas berhasil diperpanjang.</div>
                    <?php endif; ?>

                    <div class="card">
                        <h2>Ringkasan Penalti & Suspend</h2>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Kelas</th>
                                        <th>Poin</th>
                                        <th>Status Suspend</th>
                                        <th>Pelanggaran Terakhir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($q && mysqli_num_rows($q) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($q)): ?>
                                            <?php
                                                $isSuspended = !empty($row['suspended_until']) && strtotime((string)$row['suspended_until']) > time();
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['kelas_label']); ?></td>
                                                <td><?php echo (int)$row['points']; ?></td>
                                                <td>
                                                    <?php if ($isSuspended): ?>
                                                        <span class="badge badge-danger">Suspend s/d <?php echo date('d/m/Y H:i', strtotime((string)$row['suspended_until'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo !empty($row['last_violation_at']) ? date('d/m/Y H:i', strtotime((string)$row['last_violation_at'])) : '-'; ?>
                                                </td>
                                                <td>
                                                    <form method="post" class="inline-action" style="margin-right:8px;">
                                                        <?php echo csrf_input(); ?>
                                                        <input type="hidden" name="kelas_key" value="<?php echo htmlspecialchars($row['kelas_key']); ?>">
                                                        <input type="hidden" name="action_type" value="reset_points">
                                                        <button type="submit" class="btn btn-sm btn-secondary">Reset Poin</button>
                                                    </form>

                                                    <form method="post" class="inline-action">
                                                        <?php echo csrf_input(); ?>
                                                        <input type="hidden" name="kelas_key" value="<?php echo htmlspecialchars($row['kelas_key']); ?>">
                                                        <input type="hidden" name="action_type" value="extend_suspend">
                                                        <input type="number" name="suspend_days" value="7" min="1" max="30" style="width:90px; margin-right:8px;">
                                                        <button type="submit" class="btn btn-sm btn-warning">Tambah Suspend</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="empty-state">Belum ada data disiplin kelas.</td>
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
</body>
</html>
