<?php

if (!function_exists('ui_escape')) {
    function ui_escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ui_menu_short_label')) {
    function ui_menu_short_label($label)
    {
        $label = trim((string)$label);
        if ($label === '') {
            return 'MN';
        }

        $parts = preg_split('/\s+/', $label);
        if (count($parts) >= 2) {
            $first = strtoupper(substr((string)$parts[0], 0, 1));
            $second = strtoupper(substr((string)$parts[1], 0, 1));
            return $first . $second;
        }

        return strtoupper(substr($label, 0, 2));
    }
}

if (!function_exists('get_admin_menu_items')) {
    function get_admin_menu_items()
    {
        return [
            'dashboard' => ['href' => 'dashboard.php', 'label' => 'Overview'],
            'barang' => ['href' => 'barang.php', 'label' => 'Kelola Barang'],
            'peminjaman' => ['href' => 'peminjaman.php', 'label' => 'Data Peminjaman'],
            'permintaan' => ['href' => 'permintaan.php', 'label' => 'Minta Barang'],
            'disiplin' => ['href' => 'disiplin_kelas.php', 'label' => 'Disiplin Kelas'],
            'laporan' => ['href' => 'laporan.php', 'label' => 'Laporan'],
            'wa_log' => ['href' => 'wa_logs.php', 'label' => 'Log Notifikasi WA'],
            'audit' => ['href' => 'audit_logs.php', 'label' => 'Audit Log'],
        ];
    }
}

if (!function_exists('get_user_menu_items')) {
    function get_user_menu_items()
    {
        return [
            'dashboard' => ['href' => 'dashboard.php', 'label' => 'Dashboard'],
            'pinjam' => ['href' => 'pinjam.php', 'label' => 'Pinjam Barang'],
            'minta' => ['href' => 'minta.php', 'label' => 'Minta Barang'],
            'riwayat' => ['href' => 'riwayat.php', 'label' => 'Riwayat'],
            'laporan' => ['href' => 'laporan.php', 'label' => 'Laporan'],
        ];
    }
}

if (!function_exists('render_dashboard_topbar')) {
    function render_dashboard_topbar($userName, $roleLabel, $actionHref = '../auth/logout.php', $actionLabel = 'Logout')
    {
        ?>
        <div class="topbar card">
            <div class="topbar-meta">
                <p class="topbar-label">Masuk sebagai</p>
                <p class="topbar-user"><?php echo ui_escape($userName); ?></p>
            </div>
            <div class="topbar-actions">
                <span class="badge topbar-role"><?php echo ui_escape($roleLabel); ?></span>
                <?php if (!empty($actionHref) && !empty($actionLabel)): ?>
                    <a href="<?php echo ui_escape($actionHref); ?>" class="btn btn-outline btn-sm"><?php echo ui_escape($actionLabel); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('render_dashboard_sidebar')) {
    function render_dashboard_sidebar(array $menuItems, $activeKey = '')
    {
        ?>
        <aside class="sidebar card">
            <nav>
                <ul>
                    <?php foreach ($menuItems as $key => $item): ?>
                        <?php $isActive = ((string)$key === (string)$activeKey); ?>
                        <li>
                            <a href="<?php echo ui_escape($item['href']); ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                                <span class="nav-label" data-short="<?php echo ui_escape(ui_menu_short_label($item['label'])); ?>">
                                    <?php echo ui_escape($item['label']); ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>
        <?php
    }
}
