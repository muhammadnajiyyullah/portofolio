<?php
declare(strict_types=1);

/* ------------------------------------------------------------------
 |  Dev Journal — Personal Portfolio
 |  Konfigurasi utama. Ubah kredensial database bila perlu.
 |----------------------------------------------------------------- */

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'portofolio';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME     = 'Dev Journal';
const APP_TIMEZONE = 'Asia/Jakarta';
const APP_DEBUG    = false; // Jangan aktifkan di server publik.
const UPLOAD_PATH  = __DIR__ . '/uploads';
const MAX_UPLOAD   = 5 * 1024 * 1024; // 5 MB
const SESSION_IDLE = 7200; // 2 jam tanpa aktivitas → logout otomatis.

date_default_timezone_set(APP_TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    ini_set('session.gc_maxlifetime', (string) SESSION_IDLE);
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');
    session_name('devjournal');
    session_start();
}

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/lang.php';

send_security_headers();
