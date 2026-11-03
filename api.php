<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

verify_csrf();

$action = $_POST['action'] ?? '';

/* Honeypot: bot akan mengisinya, manusia tidak melihatnya. */
if (!empty($_POST['website'])) {
    json_response(['ok' => true]);
}

$now = time();

try {
    if ($action === 'contact') {
        $name    = trim((string) ($_POST['name'] ?? ''));
        $email   = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body    = trim((string) ($_POST['message'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            json_response(['ok' => false, 'error' => is_en() ? 'Please enter your name.' : 'Mohon isi nama kamu.'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
            json_response(['ok' => false, 'error' => is_en() ? 'Please enter a valid email.' : 'Mohon masukkan email yang valid.'], 422);
        }
        if (mb_strlen($body) < 10 || mb_strlen($body) > 4000) {
            json_response(['ok' => false, 'error' => is_en() ? 'Message must be 10–4000 characters.' : 'Pesan harus 10–4000 karakter.'], 422);
        }
        if ($subject !== '' && mb_strlen($subject) > 200) {
            $subject = mb_substr($subject, 0, 200);
        }

        if (($now - (int) ($_SESSION['last_contact'] ?? 0)) < 15) {
            json_response(['ok' => false, 'error' => is_en() ? 'Please wait a moment before sending again.' : 'Tunggu sebentar sebelum mengirim lagi.'], 429);
        }
        if (!rate_limit('contact:' . client_ip(), 3, 600)) {
            json_response(['ok' => false, 'error' => is_en() ? 'Too many messages. Please try again later.' : 'Terlalu banyak pesan. Coba lagi nanti.'], 429);
        }

        $st = db()->prepare('INSERT INTO messages (name, email, subject, body) VALUES (?, ?, ?, ?)');
        $st->execute([$name, $email, $subject, $body]);
        $_SESSION['last_contact'] = $now;

        json_response(['ok' => true, 'message' => t('form_success')]);
    }

    if ($action === 'guestbook') {
        $name    = trim((string) ($_POST['name'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            json_response(['ok' => false, 'error' => is_en() ? 'Please enter your name.' : 'Mohon isi nama kamu.'], 422);
        }
        if (mb_strlen($message) < 3 || mb_strlen($message) > 500) {
            json_response(['ok' => false, 'error' => is_en() ? 'Message must be 3–500 characters.' : 'Pesan harus 3–500 karakter.'], 422);
        }
        if (($now - (int) ($_SESSION['last_guestbook'] ?? 0)) < 20) {
            json_response(['ok' => false, 'error' => is_en() ? 'Please wait a moment.' : 'Tunggu sebentar ya.'], 429);
        }
        if (!rate_limit('guestbook:' . client_ip(), 3, 600)) {
            json_response(['ok' => false, 'error' => is_en() ? 'Too many entries. Please try again later.' : 'Terlalu banyak entri. Coba lagi nanti.'], 429);
        }

        $st = db()->prepare('INSERT INTO guestbook (name, message) VALUES (?, ?)');
        $st->execute([$name, $message]);
        $_SESSION['last_guestbook'] = $now;

        json_response([
            'ok'      => true,
            'message' => t('guestbook_success'),
            'entry'   => [
                'name'    => $name,
                'initial' => avatar_initial($name),
                'message' => $message,
                'date'    => format_date(date('Y-m-d')),
            ],
        ]);
    }

    if ($action === 'react') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $emoji     = (string) ($_POST['emoji'] ?? '');
        $allowed   = ['🔥', '👏', '💡'];

        if ($projectId <= 0 || !in_array($emoji, $allowed, true)) {
            json_response(['ok' => false, 'error' => 'Invalid reaction.'], 422);
        }
        if (!rate_limit('react:' . client_ip(), 60, 60)) {
            json_response(['ok' => false, 'error' => is_en() ? 'Too many reactions. Please slow down.' : 'Terlalu banyak reaction. Pelan-pelan ya.'], 429);
        }

        $exists = db()->prepare("SELECT id FROM projects WHERE id = ? AND status = 'published' LIMIT 1");
        $exists->execute([$projectId]);
        if (!$exists->fetchColumn()) {
            json_response(['ok' => false, 'error' => 'Project not found.'], 404);
        }

        $st = db()->prepare('INSERT INTO reactions (project_id, emoji, count) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE count = count + 1');
        $st->execute([$projectId, $emoji]);

        $countSt = db()->prepare('SELECT count FROM reactions WHERE project_id = ? AND emoji = ?');
        $countSt->execute([$projectId, $emoji]);

        json_response(['ok' => true, 'count' => (int) $countSt->fetchColumn()]);
    }

    json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => is_en() ? 'Something went wrong. Please try again.' : 'Terjadi kesalahan. Coba lagi ya.'], 500);
}
