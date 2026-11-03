<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    clear_admin_session();
    flash('Kamu sudah keluar dari dashboard.');
    redirect('login.php');
}

redirect('index.php');
