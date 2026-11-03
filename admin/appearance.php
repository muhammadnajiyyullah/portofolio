<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_admin();

$adminTitle  = 'Tampilan';
$adminDesc   = 'Atur palet warna, tipografi, dan fitur interaktif website.';
$adminActive = 'appearance';

$presets = [
    'lavender' => [
        'label'  => 'Soft Lavender',
        'desc'   => 'Tenang, lembut, dan personal. Palet default dengan aksen ungu pastel.',
        'colors' => ['#C9B8FF', '#FAF8FF', '#2B1B5A', '#8B6CFF'],
        'mood'   => 'Calm & creative',
    ],
    'sage' => [
        'label'  => 'Quiet Sage',
        'desc'   => 'Segar dan membumi. Hijau sage dengan nuansa ruang kerja alami.',
        'colors' => ['#CDE7D4', '#F7FBF8', '#173A2C', '#4E9B72'],
        'mood'   => 'Fresh & grounded',
    ],
    'neon' => [
        'label'  => 'Neon Studio',
        'desc'   => 'Berani dan ekspresif. Aksen neon untuk portofolio yang menonjol.',
        'colors' => ['#C6F24E', '#F7F8FF', '#14122B', '#6D4AFF'],
        'mood'   => 'Bold & playful',
    ],
];

$fonts = [
    'jakarta' => [
        'label'  => 'Plus Jakarta Sans',
        'desc'   => 'Modern, ramah, dan sangat mudah dibaca.',
        'stack'  => 'Plus Jakarta Sans',
        'sample' => 'Membangun dengan rasa penasaran.',
    ],
    'sora' => [
        'label'  => 'Sora',
        'desc'   => 'Geometris dan tenang, terasa seperti produk teknologi.',
        'stack'  => 'Sora',
        'sample' => 'Desain yang baik terasa alami.',
    ],
    'grotesk' => [
        'label'  => 'Space Grotesk',
        'desc'   => 'Sedikit editorial dan teknis, cocok untuk dev journal.',
        'stack'  => 'Space Grotesk',
        'sample' => 'Kode, cerita, dan ruang untuk berpikir.',
    ],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();

    $theme = (string) ($_POST['theme_preset'] ?? 'lavender');
    $font  = (string) ($_POST['font_preset'] ?? 'jakarta');

    set_setting('theme_preset', isset($presets[$theme]) ? $theme : 'lavender');
    set_setting('font_preset', isset($fonts[$font]) ? $font : 'jakarta');
    set_setting('custom_cursor', isset($_POST['custom_cursor']) ? '1' : '0');
    set_setting('github_show', isset($_POST['github_show']) ? '1' : '0');

    flash('Pengaturan tampilan berhasil disimpan.');
    redirect('appearance.php');
}

$activeTheme = setting('theme_preset', 'lavender');
$activeFont  = setting('font_preset', 'jakarta');

require __DIR__ . '/../inc/admin-header.php';
?>

<form method="post" action="appearance.php" class="appearance-form">
    <?= csrf_field() ?>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h3>Palet warna</h3>
                <p>Warna diterapkan ke seluruh halaman publik, termasuk mode gelap.</p>
            </div>
            <a href="../index.php" target="_blank" rel="noopener" class="admin-card__link">Lihat website <?= icon('external', 14) ?></a>
        </div>

        <div class="preset-grid">
            <?php foreach ($presets as $key => $preset): ?>
            <label class="preset-card preset-card--<?= e($key) ?>">
                <input type="radio" name="theme_preset" value="<?= e($key) ?>" <?= $activeTheme === $key ? 'checked' : '' ?>>
                <span class="preset-card__check"><?= icon('check', 14) ?></span>
                <span class="preset-card__swatches" aria-hidden="true">
                    <?php foreach ($preset['colors'] as $color): ?>
                    <i style="background: <?= e($color) ?>"></i>
                    <?php endforeach; ?>
                </span>
                <span class="preset-card__body">
                    <strong><?= e($preset['label']) ?></strong>
                    <small><?= e($preset['desc']) ?></small>
                    <em><?= e($preset['mood']) ?></em>
                </span>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="appearance-preview " data-theme-preview>
            <div class="appearance-preview__bar">
                <span class="appearance-preview__dot"></span>
                <span class="appearance-preview__pill"></span>
                <span class="appearance-preview__pill appearance-preview__pill--short"></span>
            </div>
            <div class="appearance-preview__body">
                <div>
                    <p class="appearance-preview__eyebrow">HALO, SAYA</p>
                    <h3>Raka Pratama<span>.</span></h3>
                    <p>Informatics Graduate — Web &amp; Software Developer</p>
                    <span class="appearance-preview__btn">Lihat Proyek <?= icon('arrow-right', 13) ?></span>
                </div>
                <div class="appearance-preview__art">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h3>Tipografi</h3>
                <p>Font heading mengikuti preset. Body tetap Inter agar mudah dibaca.</p>
            </div>
        </div>

        <div class="font-grid">
            <?php foreach ($fonts as $key => $font): ?>
            <label class="font-card">
                <input type="radio" name="font_preset" value="<?= e($key) ?>" <?= $activeFont === $key ? 'checked' : '' ?>>
                <span class="font-card__check"><?= icon('check', 13) ?></span>
                <span class="font-card__sample" style="font-family: '<?= e($font['stack']) ?>', sans-serif">Aa</span>
                <span class="font-card__body">
                    <strong><?= e($font['label']) ?></strong>
                    <small><?= e($font['desc']) ?></small>
                    <em style="font-family: '<?= e($font['stack']) ?>', sans-serif"><?= e($font['sample']) ?></em>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h3>Fitur interaktif</h3>
                <p>Matikan jika terasa berlebihan atau mengganggu aksesibilitas.</p>
            </div>
        </div>

        <div class="toggle-list">
            <label class="switch-row">
                <span class="switch">
                    <input type="checkbox" name="custom_cursor" <?= bool_setting('custom_cursor', true) ? 'checked' : '' ?>>
                    <span class="switch__slider"></span>
                </span>
                <span>
                    <strong>Custom cursor</strong>
                    <small>Lingkaran lembut yang mengikuti kursor di perangkat dengan mouse.</small>
                </span>
            </label>

            <label class="switch-row">
                <span class="switch">
                    <input type="checkbox" name="github_show" <?= bool_setting('github_show', true) ? 'checked' : '' ?>>
                    <span class="switch__slider"></span>
                </span>
                <span>
                    <strong>Tampilkan GitHub section</strong>
                    <small>Statistik live dan grafik kontribusi diambil langsung dari GitHub.</small>
                </span>
            </label>
        </div>

        <div class="appearance-note">
            <?= icon('sparkles', 17) ?>
            <p><strong>Tip:</strong> mode terang/gelap diatur otomatis mengikuti preferensi pengunjung, dan bisa diganti lewat tombol di navbar.</p>
        </div>
    </section>

    <div class="form-sticky">
        <span><?= icon('shield', 15) ?> Perubahan tampil segera setelah disimpan.</span>
        <div class="form-sticky__actions">
            <a href="../index.php" target="_blank" rel="noopener" class="btn btn--ghost"><?= icon('eye', 15) ?> Preview</a>
            <button type="submit" class="btn btn--primary"><?= icon('check', 16) ?> Simpan tampilan</button>
        </div>
    </div>
</form>

<?php require __DIR__ . '/../inc/admin-footer.php'; ?>
