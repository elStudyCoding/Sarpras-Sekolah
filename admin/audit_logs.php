<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/db_helper.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'audit';
$limit = 50;

$logs = mysqli_query($conn, "
    SELECT id, user_name, user_role, action, entity, entity_id, details, created_at
    FROM audit_logs
    ORDER BY id DESC
    LIMIT $limit
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Audit Log</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2>Aktivitas Terbaru</h2>
                                <p class="muted mb-0">Menampilkan <?php echo (int)$limit; ?> aktivitas terakhir.</p>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Aksi</th>
                                        <th>Entitas</th>
                                        <th>ID</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
                                        <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at']))); ?></td>
                                                <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                                <td><span class="badge"><?php echo htmlspecialchars($log['user_role']); ?></span></td>
                                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td><?php echo htmlspecialchars($log['entity']); ?></td>
                                                <td><?php echo $log['entity_id'] !== null ? (int)$log['entity_id'] : '-'; ?></td>
                                                <td class="audit-details">
                                                    <?php echo $log['details'] ? htmlspecialchars($log['details']) : '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="empty-state">Belum ada aktivitas yang terekam.</td>
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
