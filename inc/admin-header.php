<?php
if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../config.php';
}
require_admin();

$adminTitle  = $adminTitle ?? 'Dashboard';
$adminDesc   = $adminDesc ?? '';
$adminActive = $adminActive ?? 'dashboard';
$adminUser   = admin_user();
$unreadCount = (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
$flashes     = take_flashes();
?><!doctype html>
<html lang="id" data-mode="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> — <?= e(setting('site_name', 'Dev Journal')) ?> Admin</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin.css', '../')) ?>">
    <script nonce="<?= e(csp_nonce()) ?>">
    (function () {
        try {
            var mode = localStorage.getItem('dj-admin-mode') || 'light';
            document.documentElement.dataset.mode = mode;
        } catch (e) {}
    })();
    </script>
</head>
<body class="admin-body">
<a class="skip-link" href="#adminContent">Lewati ke konten</a>

<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar__brand">
            <a href="index.php" class="admin-brand">
                <span class="admin-brand__mark"><?= e(setting('owner_initial', 'RP')) ?></span>
                <span><strong><?= e(setting('site_name', 'Dev Journal')) ?></strong><small>admin workspace</small></span>
            </a>
            <button type="button" class="admin-sidebar__close" id="sidebarClose" aria-label="Tutup menu"><?= icon('close', 18) ?></button>
        </div>

        <nav class="admin-nav" aria-label="Navigasi admin">
            <p class="admin-nav__label">Workspace</p>
            <a href="index.php" class="<?= $adminActive === 'dashboard' ? 'is-active' : '' ?>"><?= icon('grid', 17) ?><span>Dashboard</span></a>
            <a href="profile.php" class="<?= $adminActive === 'profile' ? 'is-active' : '' ?>"><?= icon('user', 17) ?><span>Profil</span></a>
            <a href="appearance.php" class="<?= $adminActive === 'appearance' ? 'is-active' : '' ?>"><?= icon('palette', 17) ?><span>Tampilan</span></a>
            <a href="users.php" class="<?= $adminActive === 'users' ? 'is-active' : '' ?>"><?= icon('shield', 17) ?><span>Pengguna</span></a>

            <p class="admin-nav__label">Konten</p>
            <a href="collection.php?type=projects" class="<?= $adminActive === 'projects' ? 'is-active' : '' ?>"><?= icon('folder', 17) ?><span>Proyek</span><em><?= (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn() ?></em></a>
            <a href="collection.php?type=skills" class="<?= $adminActive === 'skills' ? 'is-active' : '' ?>"><?= icon('zap', 17) ?><span>Skills</span></a>
            <a href="collection.php?type=journey" class="<?= $adminActive === 'journey' ? 'is-active' : '' ?>"><?= icon('briefcase', 17) ?><span>Journey</span></a>
            <a href="collection.php?type=certificates" class="<?= $adminActive === 'certificates' ? 'is-active' : '' ?>"><?= icon('award', 17) ?><span>Sertifikat</span></a>
            <a href="collection.php?type=posts" class="<?= $adminActive === 'posts' ? 'is-active' : '' ?>"><?= icon('file', 17) ?><span>Blog</span></a>
            <a href="collection.php?type=testimonials" class="<?= $adminActive === 'testimonials' ? 'is-active' : '' ?>"><?= icon('message', 17) ?><span>Testimoni</span></a>

            <p class="admin-nav__label">Interaksi</p>
            <a href="messages.php" class="<?= $adminActive === 'messages' ? 'is-active' : '' ?>">
                <?= icon('mail', 17) ?><span>Inbox</span>
                <?php if ($unreadCount > 0): ?><em class="is-alert"><?= $unreadCount ?></em><?php endif; ?>
            </a>
            <a href="messages.php?tab=guestbook" class="<?= $adminActive === 'guestbook' ? 'is-active' : '' ?>"><?= icon('book', 17) ?><span>Guestbook</span></a>
        </nav>

        <div class="admin-sidebar__foot">
            <div class="admin-user">
                <span class="admin-user__avatar"><?= e(avatar_initial($adminUser['name'] ?? 'Admin')) ?></span>
                <div><strong><?= e($adminUser['name'] ?? 'Admin') ?></strong><small>@<?= e($adminUser['username'] ?? 'admin') ?></small></div>
            </div>
            <div class="admin-sidebar__actions">
                <a href="../index.php" target="_blank" rel="noopener" title="Lihat website"><?= icon('external', 16) ?></a>
                <form method="post" action="logout.php" data-confirm="Keluar dari dashboard?">
                    <?= csrf_field() ?>
                    <button type="submit" title="Keluar" aria-label="Keluar dari dashboard"><?= icon('logout', 16) ?></button>
                </form>
            </div>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-btn" id="sidebarOpen" aria-label="Buka menu"><?= icon('menu', 19) ?></button>
            <div class="admin-topbar__title">
                <h1><?= e($adminTitle) ?></h1>
                <?php if ($adminDesc): ?><p><?= e($adminDesc) ?></p><?php endif; ?>
            </div>
            <div class="admin-topbar__actions">
                <a href="../index.php" target="_blank" rel="noopener" class="admin-toplink"><?= icon('external', 15) ?> <span>Lihat site</span></a>
                <button type="button" class="admin-icon-btn" id="adminTheme" aria-label="Ganti mode"><?= icon('sun', 17) ?><span class="admin-icon-btn__moon"><?= icon('moon', 17) ?></span></button>
            </div>
        </header>

        <div class="admin-content" id="adminContent">
            <?php foreach ($flashes as $flash): ?>
            <div class="admin-flash admin-flash--<?= e($flash['type']) ?>">
                <?= icon($flash['type'] === 'error' ? 'close' : 'check', 16) ?>
                <span><?= e($flash['message']) ?></span>
                <button type="button" data-dismiss-flash aria-label="Tutup"><?= icon('close', 14) ?></button>
            </div>
            <?php endforeach; ?>
