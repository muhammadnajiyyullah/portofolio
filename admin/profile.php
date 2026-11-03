<?php
require_once __DIR__ . '/../config.php';
require_admin();

$adminUser = admin_user();

$adminTitle = 'Profil & Identitas';
$adminDesc = 'Semua data yang tampil di halaman publik, dikelola dari satu tempat.';
$adminActive = 'profile';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_profile') {
        $textSettings = [
            'site_name', 'owner_name', 'owner_initial',
            'headline_id', 'headline_en', 'bio_id', 'bio_en', 'about_id', 'about_en',
            'campus', 'major', 'ipk', 'grad_year', 'location',
            'fun_facts', 'roles', 'years_coding',
            'status_learning', 'status_building', 'status_listening',
            'email', 'whatsapp', 'github_user', 'github', 'linkedin', 'instagram', 'footer_note',
        ];
        foreach ($textSettings as $key) {
            set_setting($key, trim((string) ($_POST[$key] ?? '')));
        }
        set_setting('open_to_work', isset($_POST['open_to_work']) ? '1' : '0');

        try {
            if (!empty($_POST['remove_avatar'])) {
                delete_upload(setting('avatar'));
                set_setting('avatar', '');
            }
            if ($new = upload_file('avatar', 'image')) {
                delete_upload(setting('avatar'));
                set_setting('avatar', $new);
            }

            if (!empty($_POST['remove_about_photo'])) {
                delete_upload(setting('about_photo'));
                set_setting('about_photo', '');
            }
            if ($new = upload_file('about_photo', 'image')) {
                delete_upload(setting('about_photo'));
                set_setting('about_photo', $new);
            }

            if (!empty($_POST['remove_cv'])) {
                delete_upload(setting('cv_file'));
                set_setting('cv_file', '');
            }
            if ($new = upload_file('cv_file', 'pdf')) {
                delete_upload(setting('cv_file'));
                set_setting('cv_file', $new);
            }

            flash('Profil berhasil disimpan.');
        } catch (Throwable $e) {
            flash('Sebagian data tersimpan, tetapi upload gagal: ' . $e->getMessage(), 'error');
        }
        redirect('profile.php');
    }

    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $st = db()->prepare('SELECT * FROM admins WHERE id = ?');
        $st->execute([$adminUser['id'] ?? 0]);
        $admin = $st->fetch();

        if (!$admin || !password_verify($current, $admin['password_hash'])) {
            flash('Password saat ini tidak sesuai.', 'error');
        } else {
            $policyErrors = password_policy_errors($new, (string) ($admin['username'] ?? ''));
            if ($new !== $confirm) {
                flash('Konfirmasi password tidak cocok.', 'error');
            } elseif ($policyErrors) {
                flash(implode(' ', $policyErrors), 'error');
            } else {
                db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);

                session_regenerate_id(true);
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                $_SESSION['admin_last_activity'] = time();
                flash('Password berhasil diperbarui.');
            }
        }
        redirect('profile.php#security');
    }
}

$avatar = setting('avatar');
$aboutPhoto = setting('about_photo');
$cv = setting('cv_file');

$securitySt = db()->prepare('SELECT created_at, last_login_at, last_login_ip FROM admins WHERE id = ? LIMIT 1');
$securitySt->execute([(int) ($adminUser['id'] ?? 0)]);
$securityInfo = $securitySt->fetch() ?: [];

require __DIR__ . '/../inc/admin-header.php';
?>

<form method="post" action="profile.php" enctype="multipart/form-data" class="profile-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_profile">

    <div class="profile-top">
        <div class="profile-identity-card">
            <div class="profile-avatar">
                <img src="../<?= e(image_url($avatar)) ?>" alt="<?= e(setting('owner_name')) ?>" id="avatarPreview">
                <label class="profile-avatar__edit" title="Ganti avatar">
                    <?= icon('image', 15) ?>
                    <input type="file" name="avatar" accept="image/*" data-preview="#avatarPreview">
                </label>
            </div>
            <div>
                <p class="profile-identity-card__eyebrow"><?= e(setting('headline_id')) ?></p>
                <h2><?= e(setting('owner_name')) ?></h2>
                <p><?= e(setting('location')) ?></p>
                <div class="profile-identity-card__meta">
                    <span class="status-pill"><span class="status-dot"></span><?= bool_setting('open_to_work', true) ? 'Open to work' : 'Tidak menerima peluang' ?></span>
                    <a href="../index.php" target="_blank" rel="noopener">Lihat profil <?= icon('external', 13) ?></a>
                </div>
            </div>
        </div>
        <div class="profile-top__actions">
            <button type="submit" class="btn btn--primary"><?= icon('check', 16) ?> Simpan semua perubahan</button>
            <p>Perubahan langsung tampil di website.</p>
        </div>
    </div>

    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Identitas utama</h3><p>Nama, headline, dan informasi dasar.</p></div>
            <span class="admin-card__badge"><?= icon('user', 14) ?> Public</span>
        </div>
        <div class="form-grid">
            <div class="field">
                <label for="site_name">Nama website</label>
                <input type="text" id="site_name" name="site_name" value="<?= e(setting('site_name')) ?>" maxlength="80">
            </div>
            <div class="field">
                <label for="owner_initial">Inisial logo</label>
                <input type="text" id="owner_initial" name="owner_initial" value="<?= e(setting('owner_initial')) ?>" maxlength="3">
                <small class="help">1–3 huruf, tampil di logo dan loader.</small>
            </div>
            <div class="field">
                <label for="owner_name">Nama lengkap</label>
                <input type="text" id="owner_name" name="owner_name" value="<?= e(setting('owner_name')) ?>" required maxlength="120">
            </div>
            <div class="field">
                <label for="location">Lokasi</label>
                <input type="text" id="location" name="location" value="<?= e(setting('location')) ?>" maxlength="120">
            </div>
            <div class="field field--full">
                <label for="headline_id">Headline (ID)</label>
                <input type="text" id="headline_id" name="headline_id" value="<?= e(setting('headline_id')) ?>" maxlength="180">
            </div>
            <div class="field field--full">
                <label for="headline_en">Headline (EN)</label>
                <input type="text" id="headline_en" name="headline_en" value="<?= e(setting('headline_en')) ?>" maxlength="180">
            </div>
            <div class="field">
                <label for="roles">Teks typewriter</label>
                <input type="text" id="roles" name="roles" value="<?= e(setting('roles')) ?>" maxlength="250">
                <small class="help">Pisahkan dengan koma.</small>
            </div>
            <div class="field">
                <label for="years_coding">Tahun ngoding</label>
                <input type="text" id="years_coding" name="years_coding" value="<?= e(setting('years_coding')) ?>" maxlength="10">
            </div>
            <div class="field">
                <label for="campus">Kampus</label>
                <input type="text" id="campus" name="campus" value="<?= e(setting('campus')) ?>" maxlength="150">
            </div>
            <div class="field">
                <label for="major">Jurusan</label>
                <input type="text" id="major" name="major" value="<?= e(setting('major')) ?>" maxlength="100">
            </div>
            <div class="field">
                <label for="ipk">IPK</label>
                <input type="text" id="ipk" name="ipk" value="<?= e(setting('ipk')) ?>" maxlength="10">
            </div>
            <div class="field">
                <label for="grad_year">Tahun lulus</label>
                <input type="text" id="grad_year" name="grad_year" value="<?= e(setting('grad_year')) ?>" maxlength="10">
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Cerita & bio</h3><p>Ditampilkan di hero, about, dan CV.</p></div>
        </div>
        <div class="form-grid">
            <div class="field field--full">
                <label for="bio_id">Bio singkat (ID)</label>
                <textarea id="bio_id" name="bio_id" rows="3" maxlength="600"><?= e(setting('bio_id')) ?></textarea>
                <small class="help">Tampil di hero dan ringkasan CV.</small>
            </div>
            <div class="field field--full">
                <label for="bio_en">Bio singkat (EN)</label>
                <textarea id="bio_en" name="bio_en" rows="3" maxlength="600"><?= e(setting('bio_en')) ?></textarea>
            </div>
            <div class="field field--full">
                <label for="about_id">Tentang saya (ID)</label>
                <textarea id="about_id" name="about_id" rows="9"><?= e(setting('about_id')) ?></textarea>
                <small class="help">Pisahkan paragraf dengan baris kosong.</small>
            </div>
            <div class="field field--full">
                <label for="about_en">Tentang saya (EN)</label>
                <textarea id="about_en" name="about_en" rows="9"><?= e(setting('about_en')) ?></textarea>
            </div>
            <div class="field field--full">
                <label for="fun_facts">Fun facts</label>
                <textarea id="fun_facts" name="fun_facts" rows="4"><?= e(setting('fun_facts')) ?></textarea>
                <small class="help">Satu fakta per baris.</small>
            </div>
            <div class="field field--full">
                <label for="footer_note">Catatan footer</label>
                <input type="text" id="footer_note" name="footer_note" value="<?= e(setting('footer_note')) ?>" maxlength="180">
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Status & ketersediaan</h3><p>Kartu “Currently” dan badge open to work.</p></div>
        </div>
        <div class="form-grid">
            <div class="field field--full">
                <label class="switch-row">
                    <span class="switch"><input type="checkbox" name="open_to_work" <?= bool_setting('open_to_work', true) ? 'checked' : '' ?>><span class="switch__slider"></span></span>
                    <span><strong>Tampilkan badge “Open to Work”</strong><small>Muncul di hero dan recruiter mode.</small></span>
                </label>
            </div>
            <div class="field">
                <label for="status_learning">Sedang belajar</label>
                <input type="text" id="status_learning" name="status_learning" value="<?= e(setting('status_learning')) ?>" maxlength="120">
            </div>
            <div class="field">
                <label for="status_building">Sedang membuat</label>
                <input type="text" id="status_building" name="status_building" value="<?= e(setting('status_building')) ?>" maxlength="120">
            </div>
            <div class="field">
                <label for="status_listening">Sedang mendengarkan</label>
                <input type="text" id="status_listening" name="status_listening" value="<?= e(setting('status_listening')) ?>" maxlength="120">
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Media & CV</h3><p>Foto profil, foto about, dan file CV PDF.</p></div>
        </div>
        <div class="media-grid">
            <div class="media-field">
                <div class="media-field__preview media-field__preview--square">
                    <img src="../<?= e(image_url($avatar)) ?>" alt="Avatar" id="avatarPreview2">
                </div>
                <div>
                    <strong>Avatar / foto profil</strong>
                    <p>Rasio 1:1, minimal 600×600px. JPG, PNG, WEBP.</p>
                    <input type="file" name="avatar" accept="image/*" data-preview="#avatarPreview2,#avatarPreview">
                    <?php if ($avatar): ?>
                    <label class="checkline"><input type="checkbox" name="remove_avatar" value="1"> Hapus avatar saat ini</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="media-field">
                <div class="media-field__preview">
                    <img src="../<?= e(image_url($aboutPhoto)) ?>" alt="Foto about" id="aboutPreview">
                </div>
                <div>
                    <strong>Foto bagian About</strong>
                    <p>Rasio portrait 4:5, minimal 800×1000px.</p>
                    <input type="file" name="about_photo" accept="image/*" data-preview="#aboutPreview">
                    <?php if ($aboutPhoto): ?>
                    <label class="checkline"><input type="checkbox" name="remove_about_photo" value="1"> Hapus foto ini</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="media-field media-field--file">
                <span class="media-field__file-icon"><?= icon('file', 24) ?></span>
                <div>
                    <strong>File CV (PDF)</strong>
                    <p><?= $cv ? 'File aktif: ' . e(basename($cv)) : 'Belum ada PDF. Tombol Download CV akan mengarah ke Resume Builder otomatis.' ?></p>
                    <input type="file" name="cv_file" accept="application/pdf">
                    <?php if ($cv): ?>
                    <label class="checkline"><input type="checkbox" name="remove_cv" value="1"> Hapus file CV</label>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Kontak & tautan</h3><p>Ditampilkan di navbar, contact, footer, dan CV.</p></div>
        </div>
        <div class="form-grid">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e(setting('email')) ?>" maxlength="180">
            </div>
            <div class="field">
                <label for="whatsapp">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" value="<?= e(setting('whatsapp')) ?>" maxlength="40">
                <small class="help">Format bebas, otomatis diubah menjadi tautan wa.me.</small>
            </div>
            <div class="field">
                <label for="github_user">Username GitHub</label>
                <input type="text" id="github_user" name="github_user" value="<?= e(setting('github_user')) ?>" maxlength="80">
                <small class="help">Dipakai untuk GitHub live stats.</small>
            </div>
            <div class="field">
                <label for="github">URL GitHub</label>
                <input type="url" id="github" name="github" value="<?= e(setting('github')) ?>" maxlength="255">
            </div>
            <div class="field">
                <label for="linkedin">URL LinkedIn</label>
                <input type="url" id="linkedin" name="linkedin" value="<?= e(setting('linkedin')) ?>" maxlength="255">
            </div>
            <div class="field">
                <label for="instagram">URL Instagram</label>
                <input type="url" id="instagram" name="instagram" value="<?= e(setting('instagram')) ?>" maxlength="255">
            </div>
        </div>
    </section>

    <div class="form-sticky">
        <span><?= icon('shield', 15) ?> Data disimpan langsung ke database.</span>
        <button type="submit" class="btn btn--primary"><?= icon('check', 16) ?> Simpan semua perubahan</button>
    </div>
</form>

<section class="admin-card" id="security">
    <div class="admin-card__head">
        <div><h3>Keamanan akun</h3><p>Ganti password dashboard secara berkala.</p></div>
        <span class="admin-card__badge"><?= icon('lock', 14) ?> @<?= e($adminUser['username']) ?></span>
    </div>
    <form method="post" action="profile.php" class="form-grid form-grid--password">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="field">
            <label for="current_password">Password saat ini</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="field">
            <label for="new_password">Password baru</label>
            <input type="password" id="new_password" name="new_password" minlength="10" autocomplete="new-password" required>
            <small class="help">Minimal 10 karakter, kombinasi huruf besar/kecil, angka, atau simbol.</small>
        </div>
        <div class="field">
            <label for="confirm_password">Konfirmasi password baru</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="10" autocomplete="new-password" required>
        </div>
        <div class="field field--full">
            <button type="submit" class="btn btn--dark"><?= icon('lock', 15) ?> Perbarui password</button>
        </div>
    </form>
    <div class="form-grid" style="margin-top:18px">
        <div class="field">
            <label>Login terakhir</label>
            <p class="help"><?= $securityInfo['last_login_at'] ? e(format_date((string) $securityInfo['last_login_at'])) . ' · ' . e((string) $securityInfo['last_login_ip']) : 'Belum tercatat.' ?></p>
        </div>
        <div class="field">
            <label>Akun dibuat</label>
            <p class="help"><?= e(format_date((string) ($securityInfo['created_at'] ?? ''))) ?></p>
        </div>
        <div class="field field--full">
            <p class="help">Butuh akun admin lain atau ingin menonaktifkan akun default? Kelola dari <a href="users.php">halaman Pengguna</a>.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../inc/admin-footer.php'; ?>
