<?php
require_once __DIR__ . '/../config.php';

if (admin_user()) {
    redirect('index.php');
}

$error = '';
$username = '';
$flashes = take_flashes();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip       = client_ip();

    $lockSeconds = login_lock_seconds($username, $ip);
    if ($lockSeconds > 0) {
        $error = rate_limit_message($lockSeconds);
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $st = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $admin = $st->fetch();

        // Dummy hash agar waktu respons untuk username yang tidak ada tetap mirip.
        $hash = $admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $valid = password_verify($password, (string) $hash);

        if (!$admin || !(int) $admin['is_active'] || !$valid) {
            record_login_attempt($username, $ip, false);
            $lockSeconds = login_lock_seconds($username, $ip);

            // Perlambat brute force tanpa mengganggu pengguna normal.
            usleep(random_int(120000, 260000));
            $error = $lockSeconds > 0
                ? rate_limit_message($lockSeconds)
                : 'Username atau password salah.';
        } else {
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
            }

            record_login_attempt($username, $ip, true);
            session_regenerate_id(true);
            $_SESSION = [
                'admin' => [
                    'id'       => (int) $admin['id'],
                    'username' => (string) $admin['username'],
                    'name'     => (string) $admin['name'],
                ],
                'admin_last_activity' => time(),
                'admin_fp'            => admin_fingerprint(),
                'csrf'                => bin2hex(random_bytes(32)),
            ];

            db()->prepare('UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?')
                ->execute([$ip, $admin['id']]);

            flash('Selamat datang kembali, ' . $admin['name'] . '.');
            redirect('index.php');
        }
    }
}
?><!doctype html>
<html lang="id" data-mode="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — <?= e(setting('site_name', 'Dev Journal')) ?> Admin</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin.css', '../')) ?>">
</head>
<body class="login-body">
<div class="login-shell">
    <aside class="login-aside">
        <div class="login-aside__top">
            <a href="../index.php" class="admin-brand admin-brand--light">
                <span class="admin-brand__mark"><?= e(setting('owner_initial', 'RP')) ?></span>
                <span><strong><?= e(setting('site_name', 'Dev Journal')) ?></strong><small>admin workspace</small></span>
            </a>
            <a href="../index.php" class="login-back">← Kembali ke website</a>
        </div>

        <div class="login-aside__content">
            <p class="login-aside__eyebrow"><span></span>PRIVATE AREA</p>
            <h1>Ruang kerja<br>di balik layar<span>.</span></h1>
            <p>Kelola profil, proyek, catatan, dan semua cerita yang tampil di portofolio — tanpa menyentuh satu baris kode.</p>

            <div class="login-terminal">
                <div class="login-terminal__bar"><i></i><i></i><i></i><span>dev-journal / admin</span></div>
                <div class="login-terminal__body">
                    <p><span>$</span> whoami</p>
                    <p class="login-terminal__out">administrator</p>
                    <p><span>$</span> status</p>
                    <p class="login-terminal__out"><i></i> ready to create</p>
                </div>
            </div>
        </div>

        <p class="login-aside__foot"><?= date('Y') ?> · <?= e(setting('site_name', 'Dev Journal')) ?> · dibuat dengan hati-hati</p>
    </aside>

    <main class="login-main">
        <div class="login-card">
            <p class="login-card__eyebrow"><?= icon('lock', 14) ?> Secure sign in</p>
            <h2>Masuk ke dashboard</h2>
            <p class="login-card__sub">Gunakan akun administrator untuk melanjutkan.</p>

            <?php foreach ($flashes as $item): ?>
            <div class="login-flash login-flash--<?= e((string) ($item['type'] ?? 'success')) ?>">
                <?= icon((($item['type'] ?? 'success') === 'error') ? 'close' : 'check', 15) ?>
                <span><?= e((string) ($item['message'] ?? '')) ?></span>
            </div>
            <?php endforeach; ?>

            <?php if ($error): ?>
            <div class="login-error"><?= icon('close', 15) ?><span><?= e($error) ?></span></div>
            <?php endif; ?>

            <form method="post" action="login.php" class="login-form">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Username</label>
                    <div class="input-icon">
                        <?= icon('user', 17) ?>
                        <input type="text" id="username" name="username" value="<?= e($username) ?>" placeholder="admin" autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        <?= icon('lock', 17) ?>
                        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Tampilkan password"><?= icon('eye', 17) ?></button>
                    </div>
                </div>
                <button type="submit" class="btn btn--primary btn--block btn--lg">Masuk <span>→</span></button>
            </form>

            <div class="login-hint">
                <strong>Pertama kali?</strong>
                <p>Akun awal ada di <code>README.md</code>. Ganti password segera setelah masuk melalui halaman Profil.</p>
            </div>

            <a href="../index.php" class="login-home-link">← Kembali ke portofolio</a>
        </div>
    </main>
</div>
<script src="<?= e(asset_url('assets/js/admin.js', '../')) ?>" defer></script>
</body>
</html>
