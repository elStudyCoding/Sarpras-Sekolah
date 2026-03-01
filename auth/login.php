<?php
include '../config/database.php';
include '../config/security.php';
security_bootstrap();
session_name('sarpras_login');
session_start();
include '../config/csrf.php';

$error = null;

if (isset($_POST['login'])) {
    $attemptWindow = 300;
    $maxAttempts = 7;
    $now = time();
    $attemptData = $_SESSION['_login_attempt'] ?? ['count' => 0, 'first' => $now];
    if (($now - (int)$attemptData['first']) > $attemptWindow) {
        $attemptData = ['count' => 0, 'first' => $now];
    }
    if ((int)$attemptData['count'] >= $maxAttempts) {
        $wait = $attemptWindow - ($now - (int)$attemptData['first']);
        $error = "Terlalu banyak percobaan login. Coba lagi dalam {$wait} detik.";
    }

    if ($error !== null) {
        $_SESSION['_login_attempt'] = $attemptData;
    } elseif (!csrf_is_valid_request()) {
        $error = "Permintaan tidak valid. Silakan refresh halaman.";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        $stmt = mysqli_prepare($conn, "SELECT id, nama, username, password, role FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && $user['username'] === 'public_school') {
            $error = "Akun ini hanya untuk sistem internal.";
        } elseif ($user && password_verify($password, $user['password'])) {
            unset($_SESSION['_login_attempt']);
            session_write_close();

            if ($user['role'] == 'admin') {
                session_name('sarpras_admin');
            } else {
                session_name('sarpras_user');
            }
            session_start();
            session_regenerate_id(true);

            $_SESSION['id']   = (int)$user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/dashboard.php");
            }
            exit;
        } else {
            $attemptData['count'] = (int)$attemptData['count'] + 1;
            $_SESSION['_login_attempt'] = $attemptData;
            $error = "Username atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sarpras</title>
    <link rel="stylesheet" href="../assets/style.css?v=20260221">
</head>
<body class="auth-page">
    <main>
        <div class="auth-wrapper">
            <div class="auth-card card">
                <h2>Login Sistem Sarpras</h2>
                <p class="muted">Masuk untuk mengelola data barang &amp; peminjaman</p>

                <?php if ($error): ?>
                    <div class="flash error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post">
                    <?php echo csrf_input(); ?>
                    <div class="form-row">
                        <label>Username</label>
                        <input type="text" name="username" maxlength="50" autocomplete="username" required>
                    </div>

                    <div class="form-row">
                        <label>Password</label>
                        <input type="password" name="password" maxlength="128" autocomplete="current-password" required>
                    </div>

                    <div class="auth-actions">
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                        <a href="../index.php?skipSplash=1" class="btn btn-outline">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
