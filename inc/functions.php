<?php
declare(strict_types=1);

/* ==================================================================
 |  Database
 |================================================================== */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $server = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            $opts
        );
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            $opts
        );
        migrate($pdo);
    } catch (PDOException $e) {
        error_log('[DevJournal DB] ' . $e->getMessage());
        http_response_code(500);
        $msg = APP_DEBUG
            ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            : 'Detail teknis disembunyikan. Periksa log server untuk informasi lebih lanjut.';
        echo '<!doctype html><meta charset="utf-8"><title>Database error</title>'
            . '<div style="font-family:system-ui;max-width:640px;margin:12vh auto;padding:32px;border:1px solid #e5e1f2;border-radius:20px">'
            . '<h1 style="font-size:24px">Koneksi database gagal</h1>'
            . '<p style="color:#6b6285;line-height:1.7">Pastikan MySQL di Laragon sudah berjalan, lalu sesuaikan kredensial di <code>config.php</code>.</p>'
            . '<pre style="background:#f6f3ff;padding:16px;border-radius:12px;white-space:pre-wrap;font-size:12px">' . $msg . '</pre></div>';
        exit;
    }

    return $pdo;
}

function migrate(PDO $pdo): void
{
    $exists = $pdo->query("SHOW TABLES LIKE 'settings'")->fetchColumn();
    if (!$exists) {
        create_schema($pdo);
        require_once __DIR__ . '/seed.php';
        seed_database($pdo);
    }

    upgrade_schema($pdo);
}

function create_schema(PDO $pdo): void
{
    $schema = [
        "CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(80) NOT NULL PRIMARY KEY,
            value TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(120) NOT NULL DEFAULT 'Administrator',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS projects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(160) NOT NULL UNIQUE,
            title VARCHAR(200) NOT NULL,
            category VARCHAR(40) NOT NULL DEFAULT 'Web',
            summary_id TEXT NULL,
            summary_en TEXT NULL,
            description LONGTEXT NULL,
            problem TEXT NULL,
            solution TEXT NULL,
            role TEXT NULL,
            result TEXT NULL,
            tech_stack VARCHAR(500) NULL,
            thumbnail VARCHAR(255) NULL,
            gallery TEXT NULL,
            live_url VARCHAR(255) NULL,
            github_url VARCHAR(255) NULL,
            featured TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            sort_order INT NOT NULL DEFAULT 0,
            views INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS skills (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(60) NOT NULL DEFAULT 'Frontend',
            icon VARCHAR(40) NULL,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS journey (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            year VARCHAR(40) NOT NULL,
            title VARCHAR(180) NOT NULL,
            organization VARCHAR(180) NULL,
            description TEXT NULL,
            type VARCHAR(40) NOT NULL DEFAULT 'Pendidikan',
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS certificates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            issuer VARCHAR(180) NULL,
            year VARCHAR(20) NULL,
            credential_url VARCHAR(255) NULL,
            image VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(180) NOT NULL UNIQUE,
            title VARCHAR(200) NOT NULL,
            excerpt TEXT NULL,
            content LONGTEXT NULL,
            cover VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            published_at DATE NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS testimonials (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            role VARCHAR(180) NULL,
            quote TEXT NULL,
            avatar VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            subject VARCHAR(200) NULL,
            body TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS guestbook (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS reactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            emoji VARCHAR(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
            count INT UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_reaction (project_id, emoji)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS visits (
            day DATE NOT NULL PRIMARY KEY,
            views INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            username VARCHAR(80) NOT NULL DEFAULT '',
            success TINYINT(1) NOT NULL DEFAULT 0,
            attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_attempt_ip (ip, attempted_at),
            INDEX idx_attempt_user (username, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS rate_limits (
            bucket VARCHAR(191) NOT NULL PRIMARY KEY,
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            reset_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($schema as $sql) {
        $pdo->exec($sql);
    }
}

function upgrade_schema(PDO $pdo): void
{
    // Pastikan tabel baru ikut dibuat pada instalasi lama.
    create_schema($pdo);

    $addColumn = static function (PDO $pdo, string $table, string $column, string $definition): void {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $st->execute([$table, $column]);
        if (!(int) $st->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    };

    $addColumn($pdo, 'admins', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER password_hash');
    $addColumn($pdo, 'admins', 'last_login_at', 'DATETIME NULL AFTER is_active');
    $addColumn($pdo, 'admins', 'last_login_ip', 'VARCHAR(45) NULL AFTER last_login_at');

    // utf8mb4_unicode_ci treats every emoji as equal, which made the
    // (project_id, emoji) unique key collapse all reactions into one row.
    $collation = $pdo->query(
        "SELECT COLLATION_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reactions' AND COLUMN_NAME = 'emoji'"
    )->fetchColumn();

    if ($collation && strcasecmp((string) $collation, 'utf8mb4_bin') !== 0) {
        $pdo->exec('ALTER TABLE reactions MODIFY emoji VARCHAR(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
    }
}

/* ==================================================================
 |  Settings
 |================================================================== */

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT name, value FROM settings')->fetchAll() as $row) {
            $cache[$row['name']] = (string) $row['value'];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function set_setting(string $key, string $value): void
{
    $st = db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $st->execute([$key, $value]);
}

function bool_setting(string $key, bool $default = false): bool
{
    $v = setting($key, $default ? '1' : '0');
    return $v === '1';
}

/* ==================================================================
 |  Output & language helpers
 |================================================================== */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * URL aset dengan cache-busting berdasarkan waktu modifikasi file.
 * Contoh: asset_url('assets/css/style.css') => assets/css/style.css?v=...
 */
function asset_url(string $path, string $prefix = ''): string
{
    $path = ltrim($path, '/');
    $file = __DIR__ . '/../' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '';
    return $prefix . $path . ($version !== '' ? '?v=' . $version : '');
}

function current_lang(): string
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'], true)) {
        $_COOKIE['lang'] = $_GET['lang'];
    }
    $lang = $_COOKIE['lang'] ?? 'id';
    return in_array($lang, ['id', 'en'], true) ? $lang : 'id';
}

function is_en(): bool
{
    return current_lang() === 'en';
}

/**
 * Ambil field yang sesuai bahasa aktif (mis. summary_en) dengan fallback ke ID.
 */
function tr(array $row, string $field): string
{
    if (is_en()) {
        $en = $row[$field . '_en'] ?? '';
        if ($en !== null && trim((string) $en) !== '') {
            return (string) $en;
        }
    }
    return (string) ($row[$field] ?? '');
}

function month_id(int $month): string
{
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $months[$month] ?? '';
}

function format_date(?string $date, bool $withYear = true): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '';
    }
    if (is_en()) {
        return date($withYear ? 'M j, Y' : 'M j', $ts);
    }
    return date('j', $ts) . ' ' . month_id((int) date('n', $ts)) . ($withYear ? ' ' . date('Y', $ts) : '');
}

function reading_time(string $html): int
{
    $words = str_word_count(strip_tags($html));
    return max(1, (int) ceil($words / 200));
}

function excerpt_text(string $text, int $limit = 140): string
{
    $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    if (mb_strlen($clean) <= $limit) {
        return $clean;
    }
    return rtrim(mb_substr($clean, 0, $limit), ' ,.;:') . '…';
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $allowed = ['projects', 'posts'];
    if (!in_array($table, $allowed, true)) {
        return $slug;
    }
    $base = $slug;
    $i = 2;
    while (true) {
        $st = db()->prepare("SELECT id FROM {$table} WHERE slug = ? AND id <> ? LIMIT 1");
        $st->execute([$slug, $ignoreId]);
        if (!$st->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function tech_array(?string $stack): array
{
    if (!$stack) {
        return [];
    }
    $parts = preg_split('/\s*,\s*/', $stack) ?: [];
    return array_values(array_filter(array_map('trim', $parts)));
}

function image_url(?string $path, string $fallback = 'assets/img/placeholder.svg'): string
{
    $path = trim((string) $path);
    return $path !== '' ? $path : $fallback;
}

function admin_media(?string $path, string $fallback = 'assets/img/placeholder.svg'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        $path = $fallback;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return '../' . ltrim($path, '/');
}

function wa_link(?string $number): string
{
    $digits = preg_replace('/\D+/', '', (string) $number);
    if ($digits !== '' && str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits;
}

/* ==================================================================
 |  Security helpers
 |================================================================== */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('X-XSS-Protection: 0');

    $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; "
        . "img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "script-src 'self' 'nonce-{$nonce}'; "
        . "connect-src 'self' https://api.github.com https://github-contributions-api.jogruber.de;";
    header('Content-Security-Policy: ' . $csp);

    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * JSON yang aman ditempatkan di dalam tag <script>.
 */
function json_for_script(mixed $data): string
{
    return (string) json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}

function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Throttle sederhana berbasis database (tahan terhadap cookie clearing).
 */
function rate_limit(string $bucket, int $maxHits, int $windowSeconds): bool
{
    $pdo = db();
    $key = hash('sha256', $bucket);
    $maxHits = max(1, $maxHits);
    $windowSeconds = max(1, $windowSeconds);

    $st = $pdo->prepare(
        'INSERT INTO rate_limits (bucket, hits, reset_at)
         VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ' . $windowSeconds . ' SECOND))
         ON DUPLICATE KEY UPDATE
            hits = IF(reset_at <= NOW(), 1, hits + 1),
            reset_at = IF(reset_at <= NOW(), VALUES(reset_at), reset_at)'
    );
    $st->execute([$key]);

    $get = $pdo->prepare('SELECT hits FROM rate_limits WHERE bucket = ?');
    $get->execute([$key]);
    $hits = (int) $get->fetchColumn();

    if (random_int(1, 50) === 1) {
        $pdo->exec('DELETE FROM rate_limits WHERE reset_at < (NOW() - INTERVAL 1 DAY)');
    }

    return $hits <= $maxHits;
}

/**
 * @return list<string> Daftar pelanggaran kebijakan password.
 */
function password_policy_errors(string $password, string $username = ''): array
{
    $errors = [];
    $length = mb_strlen($password);

    if ($length < 10) {
        $errors[] = 'Password minimal 10 karakter.';
    }
    if ($length > 128) {
        $errors[] = 'Password maksimal 128 karakter.';
    }

    $classes = 0;
    foreach (['/[a-z]/', '/[A-Z]/', '/\d/', '/[^A-Za-z0-9]/'] as $pattern) {
        if (preg_match($pattern, $password)) {
            $classes++;
        }
    }
    if ($classes < 3) {
        $errors[] = 'Gunakan minimal 3 jenis karakter: huruf kecil, huruf besar, angka, atau simbol.';
    }

    $common = [
        'password', 'password123', 'admin123', 'administrator', 'qwerty123',
        '12345678', '123456789', '1234567890', 'letmein123', 'welcome123',
        'iloveyou', 'portofolio', 'devjournal',
    ];
    $lower = mb_strtolower($password);
    if (in_array($lower, $common, true)) {
        $errors[] = 'Password terlalu umum. Pilih kombinasi yang unik.';
    }
    if ($username !== '' && mb_stripos($password, $username) !== false) {
        $errors[] = 'Password tidak boleh mengandung username.';
    }
    if (preg_match('/(.)\1{3,}/', $password)) {
        $errors[] = 'Hindari karakter yang berulang terus-menerus.';
    }

    return $errors;
}

function record_login_attempt(string $username, string $ip, bool $success): void
{
    $pdo = db();
    $username = mb_strtolower(trim($username));

    $st = $pdo->prepare('INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, ?)');
    $st->execute([$ip, $username, $success ? 1 : 0]);

    if ($success) {
        $cleanup = $pdo->prepare(
            'DELETE FROM login_attempts
             WHERE success = 0 AND (ip = ? OR username = ?)
               AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $cleanup->execute([$ip, $username]);
    }

    if (random_int(1, 20) === 1) {
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 30 DAY)');
    }
}

/**
 * Sisa waktu lockout dalam detik, 0 bila tidak terkunci.
 */
function login_lock_seconds(string $username, string $ip): int
{
    $username = mb_strtolower(trim($username));
    $pdo = db();

    $checks = [
        ['ip = ?', $ip, 10],
        ['username = ?', $username, 5],
    ];

    $remaining = 0;
    foreach ($checks as [$where, $value, $limit]) {
        $st = $pdo->prepare(
            'SELECT COUNT(*) AS hits,
                    TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(attempted_at), INTERVAL 15 MINUTE)) AS remaining
             FROM login_attempts
             WHERE success = 0 AND ' . $where . '
               AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $st->execute([$value]);
        $row = $st->fetch();
        if ($row && (int) $row['hits'] >= $limit) {
            $remaining = max($remaining, (int) $row['remaining']);
        }
    }

    return max(0, $remaining);
}

function admin_fingerprint(): string
{
    return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
}

function clear_admin_session(bool $regenerate = true): void
{
    unset(
        $_SESSION['admin'],
        $_SESSION['admin_last_activity'],
        $_SESSION['admin_fp'],
        $_SESSION['csrf'],
        $_SESSION['login_attempts'],
        $_SESSION['login_locked_until']
    );
    if ($regenerate && session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function rate_limit_message(int $seconds): string
{
    $seconds = max(1, $seconds);
    $minutes = (int) ceil($seconds / 60);
    return 'Terlalu banyak percobaan. Coba lagi dalam ' . $minutes . ' menit.';
}

/* ==================================================================
 |  Session, flash, auth, csrf
 |================================================================== */

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$sent || !hash_equals(csrf_token(), (string) $sent)) {
        http_response_code(403);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Sesi kedaluwarsa. Muat ulang halaman.']);
        } else {
            echo 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        }
        exit;
    }
}

function admin_user(): ?array
{
    if (empty($_SESSION['admin'])) {
        return null;
    }

    $now = time();
    $last = (int) ($_SESSION['admin_last_activity'] ?? 0);

    if ($last > 0 && ($now - $last) > SESSION_IDLE) {
        clear_admin_session();
        flash('Sesi berakhir karena tidak ada aktivitas. Silakan masuk kembali.', 'error');
        return null;
    }

    $fingerprint = (string) ($_SESSION['admin_fp'] ?? '');
    if ($fingerprint !== '' && !hash_equals($fingerprint, admin_fingerprint())) {
        clear_admin_session();
        flash('Sesi tidak valid. Silakan masuk kembali.', 'error');
        return null;
    }

    if ($last === 0 || ($now - $last) > 60) {
        $_SESSION['admin_last_activity'] = $now;
    }

    return $_SESSION['admin'];
}

function require_admin(): void
{
    $user = admin_user();
    if (!$user) {
        redirect('login.php');
    }

    static $validated = false;
    if (!$validated) {
        $st = db()->prepare('SELECT id, username, name, is_active FROM admins WHERE id = ? LIMIT 1');
        $st->execute([(int) ($user['id'] ?? 0)]);
        $row = $st->fetch();

        if (!$row || !(int) $row['is_active']) {
            clear_admin_session();
            flash('Akun admin ini tidak aktif. Hubungi administrator lain.', 'error');
            redirect('login.php');
        }

        $_SESSION['admin'] = [
            'id'       => (int) $row['id'],
            'username' => (string) $row['username'],
            'name'     => (string) $row['name'],
        ];
        $validated = true;
    }
}

/* ==================================================================
 |  Uploads
 |================================================================== */

function upload_file(string $field, string $kind = 'image'): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal (kode ' . $file['error'] . ').');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('File upload tidak valid.');
    }
    if ($file['size'] > MAX_UPLOAD) {
        throw new RuntimeException('Ukuran file maksimal 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($file['tmp_name']);

    $imageTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $pdfTypes = ['application/pdf' => 'pdf'];

    if ($kind === 'pdf') {
        if (!isset($pdfTypes[$mime])) {
            throw new RuntimeException('File harus berupa PDF.');
        }
        $ext = 'pdf';
    } else {
        if (!isset($imageTypes[$mime])) {
            throw new RuntimeException('File harus berupa JPG, PNG, WEBP, atau GIF.');
        }
        $ext = $imageTypes[$mime];
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new RuntimeException('File gambar tidak valid.');
        }
        if (($info[0] ?? 0) > 10000 || ($info[1] ?? 0) > 10000 || (($info[0] ?? 0) * ($info[1] ?? 0)) > 40000000) {
            throw new RuntimeException('Dimensi gambar terlalu besar (maksimal 10000px dan 40 megapiksel).');
        }
    }

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0775, true);
    }

    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = UPLOAD_PATH . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Gagal menyimpan file.');
    }

    return 'uploads/' . $name;
}

function delete_upload(?string $path): void
{
    $path = (string) $path;
    if (str_starts_with($path, 'uploads/')) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

function upload_files(string $field, string $kind = 'image'): array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'] ?? null)) {
        return [];
    }

    $paths = [];
    $count = count($_FILES[$field]['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($_FILES[$field]['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $_FILES['__multi_tmp'] = [
            'name'     => $_FILES[$field]['name'][$i],
            'type'     => $_FILES[$field]['type'][$i],
            'tmp_name' => $_FILES[$field]['tmp_name'][$i],
            'error'    => $_FILES[$field]['error'][$i],
            'size'     => $_FILES[$field]['size'][$i],
        ];
        $path = upload_file('__multi_tmp', $kind);
        if ($path !== null) {
            $paths[] = $path;
        }
    }
    unset($_FILES['__multi_tmp']);

    return $paths;
}

/* ==================================================================
 |  Visitor tracking
 |================================================================== */

function track_visit(): void
{
    if (!empty($_GET['preview']) || !empty($_GET['no_track'])) {
        return;
    }
    try {
        $st = db()->prepare('INSERT INTO visits (day, views) VALUES (CURDATE(), 1) ON DUPLICATE KEY UPDATE views = views + 1');
        $st->execute();
    } catch (Throwable $e) {
        // Statistik tidak boleh mengganggu halaman.
    }
}

function total_visits(): int
{
    try {
        return (int) db()->query('SELECT COALESCE(SUM(views),0) FROM visits')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/* ==================================================================
 |  Query shortcuts
 |================================================================== */

function all_rows(string $table, string $where = '1=1', array $params = [], string $order = 'sort_order ASC, id DESC', ?int $limit = null): array
{
    $allowed = ['projects', 'skills', 'journey', 'certificates', 'posts', 'testimonials', 'messages', 'guestbook'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }
    $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$order}";
    if ($limit !== null && $limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function one_row(string $table, string $where, array $params): ?array
{
    $allowed = ['projects', 'skills', 'journey', 'certificates', 'posts', 'testimonials', 'messages'];
    if (!in_array($table, $allowed, true)) {
        return null;
    }
    $st = db()->prepare("SELECT * FROM {$table} WHERE {$where} LIMIT 1");
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

function next_project(int $currentId): ?array
{
    $st = db()->prepare("SELECT * FROM projects WHERE status = 'published' AND id > ? ORDER BY sort_order ASC, id ASC LIMIT 1");
    $st->execute([$currentId]);
    $row = $st->fetch();
    if ($row) {
        return $row;
    }
    $st = db()->prepare("SELECT * FROM projects WHERE status = 'published' ORDER BY sort_order ASC, id ASC LIMIT 1");
    $st->execute();
    return $st->fetch() ?: null;
}

/* ==================================================================
 |  Icons (stroke style, based on Lucide - ISC)
 |================================================================== */

function icon(string $name, int $size = 20, string $class = ''): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = [
            'sun'          => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
            'moon'         => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
            'menu'         => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'close'        => '<path d="M18 6 6 18M6 6l12 12"/>',
            'arrow-right'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'arrow-left'   => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
            'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
            'arrow-up'     => '<path d="M12 19V5M6 11l6-6 6 6"/>',
            'download'     => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
            'mail'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
            'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.09 4.18 2 2 0 0 1 4.08 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
            'map-pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
            'search'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'terminal'     => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
            'sparkles'     => '<path d="m12 3-1.9 5.8L4 10.7l6.1 1.9L12 18.4l1.9-5.8L20 10.7l-6.1-1.9L12 3z"/><path d="M5 3v4M3 5h4M19 17v4M17 19h4"/>',
            'code'         => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
            'layout'       => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
            'server'       => '<rect x="2" y="3" width="20" height="7" rx="2"/><rect x="2" y="14" width="20" height="7" rx="2"/><path d="M6 6.5h.01M6 17.5h.01"/>',
            'database'     => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5M3 12a9 3 0 0 0 18 0"/>',
            'tools'        => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-2.4 2.6-2.6z"/>',
            'users'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'briefcase'    => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
            'graduation'   => '<path d="M22 10 12 5 2 10l10 5 10-5zM6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>',
            'trophy'       => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4zM7 6H4a2 2 0 0 0 2 4h1M17 6h3a2 2 0 0 1-2 4h-1"/>',
            'calendar'     => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'external'     => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
            'eye'          => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off'      => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22"/>',
            'message'      => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
            'send'         => '<path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="M22 2 11 13"/>',
            'check'        => '<path d="M20 6 9 17l-5-5"/>',
            'plus'         => '<path d="M12 5v14M5 12h14"/>',
            'trash'        => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
            'edit'         => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
            'logout'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'grid'         => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            'folder'       => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
            'file'         => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8M16 17H8M10 9H8"/>',
            'settings'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'image'        => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
            'star'         => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
            'heart'        => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
            'coffee'       => '<path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8zM6 1v3M10 1v3M14 1v3"/>',
            'music'        => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
            'book'         => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
            'rocket'       => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09zM12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2zM9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
            'command'      => '<path d="M18 3a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3H6a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3V6a3 3 0 0 0-3-3 3 3 0 0 0-3 3 3 3 0 0 0 3 3h12a3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'lock'         => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'user'         => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'globe'        => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
            'copy'         => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'printer'      => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
            'play'         => '<polygon points="5 3 19 12 5 21 5 3"/>',
            'filter'       => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
            'drag'         => '<circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/>',
            'clock'        => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'linkedin'     => '<rect x="2" y="2" width="20" height="20" rx="4"/><path d="M7 10v7M7 7v.01M11 17v-4a2 2 0 0 1 4 0v4M11 10v7"/>',
            'instagram'    => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
            'github'       => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>',
            'whatsapp'     => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
            'figma'        => '<path d="M5 5.5A3.5 3.5 0 0 1 8.5 2H12v7H8.5A3.5 3.5 0 0 1 5 5.5zM12 2h3.5a3.5 3.5 0 1 1 0 7H12V2zM12 12.5a3.5 3.5 0 1 1 3.5 3.5H12v-3.5zM5 19.5A3.5 3.5 0 0 1 8.5 16H12v3.5a3.5 3.5 0 1 1-7 0zM5 12.5A3.5 3.5 0 0 1 8.5 9H12v7H8.5A3.5 3.5 0 0 1 5 12.5z"/>',
            'palette'      => '<circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.85-.44-1.15-.29-.32-.47-.73-.47-1.16a1.7 1.7 0 0 1 1.7-1.7H16c3.31 0 6-2.69 6-6 0-4.97-4.5-9-10-9z"/>',
            'award'        => '<circle cx="12" cy="8" r="6"/><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11"/>',
            'zap'          => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
            'layers'       => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
            'monitor'      => '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
            'smartphone'   => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
            'trending'     => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
            'shield'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        ];
    }

    $path = $icons[$name] ?? $icons['sparkles'];
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<svg' . $classAttr . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/* ==================================================================
 |  Misc UI helpers
 |================================================================== */

function nav_is_active(string $key): bool
{
    return ($GLOBALS['active_nav'] ?? '') === $key;
}

function status_badge(string $status): string
{
    $label = $status === 'published' ? (is_en() ? 'Published' : 'Publish') : 'Draft';
    return '<span class="badge badge--' . ($status === 'published' ? 'live' : 'draft') . '">' . $label . '</span>';
}

function avatar_initial(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initial = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $initial .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        if (mb_strlen($initial) >= 2) {
            break;
        }
    }
    return $initial ?: '?';
}

function clean_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    if (!class_exists('DOMDocument')) {
        // Fallback bila ekstensi DOM tidak tersedia.
        $html = preg_replace('#<(script|iframe|object|embed|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';
        return trim(preg_replace('#javascript\s*:#i', '', $html) ?? '');
    }

    $allowed = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'u' => [], 's' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'pre' => [], 'code' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'hr' => [],
        'a' => ['href'],
    ];

    $doc = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadHTML(
        '<?xml encoding="UTF-8"><div id="dj-clean-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) {
        return '';
    }

    $root = null;
    foreach ($doc->childNodes as $child) {
        if ($child instanceof DOMElement && $child->getAttribute('id') === 'dj-clean-root') {
            $root = $child;
            break;
        }
    }
    if (!$root) {
        return '';
    }

    sanitize_dom_node($root, $allowed);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= (string) $doc->saveHTML($child);
    }
    return trim($out);
}

/**
 * Sanitasi rekursif dengan allowlist tag dan atribut.
 *
 * @param array<string, list<string>> $allowed
 */
function sanitize_dom_node(DOMNode $node, array $allowed): void
{
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMComment) {
            $node->removeChild($child);
            continue;
        }
        if (!$child instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($child->tagName);
        if (!isset($allowed[$tag])) {
            $dangerous = in_array($tag, [
                'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
                'button', 'textarea', 'select', 'option', 'svg', 'math', 'link',
                'meta', 'base', 'template',
            ], true);

            if ($dangerous) {
                $node->removeChild($child);
            } else {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
            }
            continue;
        }

        foreach (iterator_to_array($child->attributes) as $attr) {
            if (!in_array(strtolower($attr->name), $allowed[$tag], true)) {
                $child->removeAttribute($attr->name);
            }
        }

        if ($tag === 'a') {
            $href = trim($child->getAttribute('href'));
            if ($href === '' || !preg_match('#^(https?://|mailto:|/|\#|\?)#i', $href)) {
                $child->removeAttribute('href');
            } else {
                $child->setAttribute('rel', 'noopener noreferrer');
                if (preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('target', '_blank');
                }
            }
        }

        sanitize_dom_node($child, $allowed);
    }
}
