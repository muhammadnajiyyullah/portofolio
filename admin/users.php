<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_admin();
$adminUser = admin_user();

$adminTitle  = 'Pengguna & Keamanan';
$adminDesc   = 'Kelola akun admin, reset password, dan pantau percobaan login.';
$adminActive = 'users';

$selfId = (int) ($adminUser['id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'create_user') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $name     = trim((string) ($_POST['name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['confirm_password'] ?? '');
        $errors   = [];

        if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
            $errors[] = 'Username 3–30 karakter, hanya huruf, angka, titik, garis bawah, atau tanda hubung.';
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors[] = 'Nama harus 2–120 karakter.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        }
        $errors = array_merge($errors, password_policy_errors($password, $username));

        if (!$errors) {
            $check = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
            $check->execute([$username]);
            if ((int) $check->fetchColumn() > 0) {
                $errors[] = 'Username sudah dipakai.';
            }
        }

        if ($errors) {
            flash(implode(' ', $errors), 'error');
        } else {
            $st = db()->prepare('INSERT INTO admins (username, password_hash, name, is_active) VALUES (?, ?, ?, 1)');
            $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name]);
            flash('Akun @' . $username . ' berhasil dibuat.');
        }
        redirect('users.php');
    }

    if ($action === 'reset_password' && $id > 0) {
        $target = db()->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
        $target->execute([$id]);
        $user = $target->fetch();

        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['confirm_password'] ?? '');

        if (!$user) {
            flash('Akun tidak ditemukan.', 'error');
        } elseif ($password !== $confirm) {
            flash('Konfirmasi password tidak cocok.', 'error');
        } else {
            $errors = password_policy_errors($password, (string) $user['username']);
            if ($errors) {
                flash(implode(' ', $errors), 'error');
            } else {
                db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);

                if ($id === $selfId) {
                    session_regenerate_id(true);
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    $_SESSION['admin_last_activity'] = time();
                }
                flash('Password @' . $user['username'] . ' berhasil diperbarui.');
            }
        }
        redirect('users.php');
    }

    if ($action === 'toggle_active' && $id > 0) {
        $target = db()->prepare('SELECT id, username, is_active FROM admins WHERE id = ? LIMIT 1');
        $target->execute([$id]);
        $user = $target->fetch();

        $activeCount = (int) db()->query('SELECT COUNT(*) FROM admins WHERE is_active = 1')->fetchColumn();

        if (!$user) {
            flash('Akun tidak ditemukan.', 'error');
        } elseif ($id === $selfId) {
            flash('Kamu tidak bisa menonaktifkan akun yang sedang digunakan.', 'error');
        } elseif ((int) $user['is_active'] === 1 && $activeCount <= 1) {
            flash('Minimal harus ada satu akun admin yang aktif.', 'error');
        } else {
            $next = (int) $user['is_active'] === 1 ? 0 : 1;
            db()->prepare('UPDATE admins SET is_active = ? WHERE id = ?')->execute([$next, $id]);
            flash('Akun @' . $user['username'] . ' ' . ($next ? 'diaktifkan.' : 'dinonaktifkan.'));
        }
        redirect('users.php');
    }

    if ($action === 'delete_user' && $id > 0) {
        $target = db()->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
        $target->execute([$id]);
        $user = $target->fetch();
        $total = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();

        if (!$user) {
            flash('Akun tidak ditemukan.', 'error');
        } elseif ($id === $selfId) {
            flash('Kamu tidak bisa menghapus akun yang sedang digunakan.', 'error');
        } elseif ($total <= 1) {
            flash('Akun admin terakhir tidak boleh dihapus.', 'error');
        } else {
            db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
            flash('Akun @' . $user['username'] . ' dihapus.');
        }
        redirect('users.php');
    }

    redirect('users.php');
}

$users = all_rows('admins', '1=1', [], 'created_at ASC, id ASC');
$attempts = all_rows('login_attempts', '1=1', [], 'attempted_at DESC, id DESC', 25);
$activeAdmins = count(array_filter($users, fn ($u) => (int) $u['is_active'] === 1));
$failed24h = (int) db()->query(
    'SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at > (NOW() - INTERVAL 1 DAY)'
)->fetchColumn();

require __DIR__ . '/../inc/admin-header.php';
?>

<div class="stat-grid">
    <article class="stat-card">
        <div class="stat-card__top"><span class="stat-card__icon"><?= icon('users', 18) ?></span></div>
        <strong><?= count($users) ?></strong>
        <span>Total akun admin</span>
    </article>
    <article class="stat-card stat-card--accent">
        <div class="stat-card__top"><span class="stat-card__icon"><?= icon('shield', 18) ?></span></div>
        <strong><?= $activeAdmins ?></strong>
        <span>Akun aktif</span>
    </article>
    <article class="stat-card">
        <div class="stat-card__top"><span class="stat-card__icon"><?= icon('lock', 18) ?></span></div>
        <strong><?= $failed24h ?></strong>
        <span>Login gagal (24 jam)</span>
    </article>
</div>

<section class="admin-card admin-card--flush">
    <div class="admin-card__head">
        <div>
            <h3>Akun administrator</h3>
            <p>Nonaktifkan akun yang tidak dipakai. Akun default sebaiknya dinonaktifkan setelah akun baru dibuat.</p>
        </div>
        <span class="admin-card__badge"><?= icon('shield', 14) ?> <?= $activeAdmins ?> aktif</span>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Akun</th>
                    <th>Status</th>
                    <th>Login terakhir</th>
                    <th>Dibuat</th>
                    <th class="th-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="td-title">
                        <div class="admin-user" style="padding:0">
                            <span class="admin-user__avatar"><?= e(avatar_initial((string) $user['name'])) ?></span>
                            <div>
                                <strong><?= e((string) $user['name']) ?><?= (int) $user['id'] === $selfId ? ' (kamu)' : '' ?></strong>
                                <small>@<?= e((string) $user['username']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ((int) $user['is_active'] === 1): ?>
                            <span class="badge badge--live">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge--draft">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['last_login_at']): ?>
                            <?= e(format_date((string) $user['last_login_at'])) ?><br>
                            <small><?= e((string) $user['last_login_ip']) ?></small>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= e(format_date((string) $user['created_at'])) ?></td>
                    <td class="td-actions">
                        <form method="post" action="users.php" class="inline-form" data-confirm="<?= (int) $user['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?> akun @<?= e((string) $user['username']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                            <button type="submit" class="row-action" title="<?= (int) $user['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>" <?= (int) $user['id'] === $selfId ? 'disabled' : '' ?>>
                                <?= icon((int) $user['is_active'] === 1 ? 'eye' : 'check', 15) ?>
                            </button>
                        </form>
                        <form method="post" action="users.php" class="inline-form" data-confirm="Hapus akun @<?= e((string) $user['username']) ?> secara permanen?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                            <button type="submit" class="row-action row-action--danger" title="Hapus" <?= (int) $user['id'] === $selfId ? 'disabled' : '' ?>><?= icon('trash', 15) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="form-grid" style="align-items:start">
    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Tambah akun admin</h3><p>Buat akun baru dengan password yang kuat.</p></div>
        </div>
        <form method="post" action="users.php" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">
            <div class="field">
                <label for="new_username">Username</label>
                <input type="text" id="new_username" name="username" pattern="[A-Za-z0-9._-]{3,30}" minlength="3" maxlength="30" autocomplete="off" required>
                <small class="help">3–30 karakter, tanpa spasi.</small>
            </div>
            <div class="field">
                <label for="new_name">Nama tampilan</label>
                <input type="text" id="new_name" name="name" minlength="2" maxlength="120" autocomplete="off" required>
            </div>
            <div class="field">
                <label for="new_user_password">Password</label>
                <input type="password" id="new_user_password" name="password" minlength="10" autocomplete="new-password" required>
                <small class="help">Minimal 10 karakter, 3 jenis karakter.</small>
            </div>
            <div class="field">
                <label for="new_user_confirm">Konfirmasi password</label>
                <input type="password" id="new_user_confirm" name="confirm_password" minlength="10" autocomplete="new-password" required>
            </div>
            <div class="field field--full">
                <button type="submit" class="btn btn--primary"><?= icon('plus', 15) ?> Buat akun</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Reset password</h3><p>Ganti password akun tanpa perlu password lama.</p></div>
        </div>
        <form method="post" action="users.php" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset_password">
            <div class="field field--full">
                <label for="reset_user">Pilih akun</label>
                <select id="reset_user" name="id" required>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user['id'] ?>">@<?= e((string) $user['username']) ?> — <?= e((string) $user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="reset_password">Password baru</label>
                <input type="password" id="reset_password" name="password" minlength="10" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="reset_confirm">Konfirmasi</label>
                <input type="password" id="reset_confirm" name="confirm_password" minlength="10" autocomplete="new-password" required>
            </div>
            <div class="field field--full">
                <button type="submit" class="btn btn--dark"><?= icon('lock', 15) ?> Simpan password baru</button>
            </div>
        </form>
    </section>
</div>

<section class="admin-card admin-card--flush">
    <div class="admin-card__head">
        <div>
            <h3>Riwayat percobaan login</h3>
            <p>25 percobaan terakhir. Lockout otomatis: 5 gagal per akun atau 10 gagal per IP dalam 15 menit.</p>
        </div>
    </div>
    <?php if (!$attempts): ?>
    <div class="empty-state empty-state--admin">
        <?= icon('shield', 28) ?>
        <h2>Belum ada percobaan login.</h2>
        <p>Riwayat akan muncul setelah ada yang mencoba masuk ke dashboard.</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>Waktu</th><th>Username</th><th>IP</th><th>Hasil</th></tr>
            </thead>
            <tbody>
                <?php foreach ($attempts as $attempt): ?>
                <tr>
                    <td><?= e(format_date((string) $attempt['attempted_at'])) ?></td>
                    <td><?= $attempt['username'] !== '' ? '@' . e((string) $attempt['username']) : '—' ?></td>
                    <td><code><?= e((string) $attempt['ip']) ?></code></td>
                    <td>
                        <?php if ((int) $attempt['success'] === 1): ?>
                            <span class="badge badge--live">Berhasil</span>
                        <?php else: ?>
                            <span class="badge badge--draft">Gagal</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../inc/admin-footer.php'; ?>
