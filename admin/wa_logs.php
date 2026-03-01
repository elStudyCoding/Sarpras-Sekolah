<?php
include '../config/session_admin.php';
include '../config/database.php';
include '../config/wa_log.php';
include_once '../partials/dashboard_ui.php';

$activeMenu = 'wa_log';
$limit = 100;

wa_log_ensure_table($conn);

$logs = mysqli_query($conn, "
    SELECT id, context, target_phone, status, provider, provider_error, entity_type, entity_id, created_at
    FROM wa_notification_logs
    ORDER BY id DESC
    LIMIT {$limit}
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Notifikasi WA</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body>
    <main>
        <div class="container">
            <header class="site-header">
                <div class="site-title">
                    <h1>Log Notifikasi WA</h1>
                </div>
            </header>
            <?php render_dashboard_topbar($_SESSION['nama'], 'Administrator'); ?>

            <div class="layout">
                <?php render_dashboard_sidebar(get_admin_menu_items(), $activeMenu); ?>

                <section class="main-panel">
                    <div class="card">
                        <h2>Riwayat Pengiriman WA</h2>
                        <p class="muted">Menampilkan <?php echo (int)$limit; ?> log terbaru untuk monitoring sukses/gagal kirim.</p>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Konteks</th>
                                        <th>Tujuan</th>
                                        <th>Status</th>
                                        <th>Provider</th>
                                        <th>Entity</th>
                                        <th>Error</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($logs)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime((string)$row['created_at']))); ?></td>
                                                <td><?php echo htmlspecialchars($row['context']); ?></td>
                                                <td><?php echo htmlspecialchars($row['target_phone']); ?></td>
                                                <td>
                                                    <?php if ($row['status'] === 'sent'): ?>
                                                        <span class="badge badge-success">sent</span>
                                                    <?php elseif ($row['status'] === 'failed'): ?>
                                                        <span class="badge badge-danger">failed</span>
                                                    <?php else: ?>
                                                        <span class="badge"><?php echo htmlspecialchars($row['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['provider']); ?></td>
                                                <td>
                                                    <?php
                                                    $entity = trim((string)($row['entity_type'] ?? ''));
                                                    $entityId = (int)($row['entity_id'] ?? 0);
                                                    echo htmlspecialchars($entity !== '' ? ($entity . '#' . $entityId) : '-');
                                                    ?>
                                                </td>
                                                <td class="audit-details"><?php echo htmlspecialchars($row['provider_error'] ?: '-'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="empty-state">Belum ada log notifikasi WA.</td>
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
