<?php
include __DIR__ . '/config/security.php';
security_bootstrap();

$styleVersion = @filemtime(__DIR__ . '/assets/style.css');
if ($styleVersion === false) {
    $styleVersion = time();
}

$skipSplash = isset($_GET['skipSplash']) && $_GET['skipSplash'] === '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Sarpras Sekolah</title>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo $styleVersion; ?>">
</head>
<body>
<?php if (!$skipSplash): ?>
<div id="splashScreen" class="splash-screen" role="status" aria-live="polite" aria-label="Memuat aplikasi">
    <div class="splash-orb orb-a" aria-hidden="true"></div>
    <div class="splash-orb orb-b" aria-hidden="true"></div>
    <div class="splash-content">
        <div class="splash-mark" aria-hidden="true">
            <span></span>
        </div>
        <p class="splash-kicker">E-Sarpras</p>
        <h2>Sistem Sarpras Sekolah</h2>
        <p class="splash-subtitle">Menyiapkan dashboard inventaris dan peminjaman...</p>
        <div class="splash-loader" aria-hidden="true">
            <span></span>
        </div>
        <p class="splash-note">Secure Inventory Platform</p>
    </div>
</div>
<?php endif; ?>
<main>
    <div class="container">
        <header class="site-header">
            <div class="site-title">
                <h1>Sistem Sarpras Sekolah</h1>
            </div>
        </header>

        <section class="card text-center">
            <h2>Selamat datang</h2>
            <p class="muted">Aplikasi pendataan sarana &amp; prasarana sekolah untuk mengelola barang, peminjaman, dan pengembalian.</p>
            <div class="controls" style="justify-content:center;margin-top:8px">
                <a class="btn btn-primary" href="user/dashboard.php">Dashboard Siswa</a>
                <a class="btn btn-outline" href="auth/login.php">Login Admin</a>
            </div>
        </section>

    </div>
</main>
<?php if (!$skipSplash): ?>
<script>
    (function () {
        var splash = document.getElementById('splashScreen');
        if (!splash) return;

        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var hideDelay = reducedMotion ? 250 : 2400;
        var removeDelay = reducedMotion ? 500 : 3000;

        window.addEventListener('load', function () {
            setTimeout(function () {
                splash.classList.add('is-hidden');
            }, hideDelay);

            setTimeout(function () {
                if (splash && splash.parentNode) {
                    splash.parentNode.removeChild(splash);
                }
            }, removeDelay);
        });
    })();
</script>
<?php endif; ?>
</body>
</html>
