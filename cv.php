<?php
require_once __DIR__ . '/config.php';

$ownerName = setting('owner_name');
$headline  = tr(['h' => setting('headline_id'), 'h_en' => setting('headline_en')], 'h');
$bio       = tr(['b' => setting('bio_id'), 'b_en' => setting('bio_en')], 'b');
$email     = setting('email');

$projects = all_rows('projects', "status = 'published'", [], 'featured DESC, sort_order ASC', 4);
$skills = all_rows('skills', '1=1', [], 'sort_order ASC');
$journey = all_rows('journey', '1=1', [], 'sort_order ASC');
$certificates = all_rows('certificates', '1=1', [], 'sort_order ASC');

$skillGroups = [];
foreach ($skills as $skill) {
    if ($skill['category'] === 'Soft Skills') {
        continue;
    }
    $skillGroups[$skill['category']][] = $skill['name'];
}
$soft = [];
foreach ($skills as $skill) {
    if ($skill['category'] === 'Soft Skills') {
        $soft[] = $skill['name'];
    }
}

$work = array_values(array_filter($journey, fn ($j) => in_array($j['type'], ['Karier', 'Magang', 'Organisasi', 'Proyek'], true)));
$education = array_values(array_filter($journey, fn ($j) => $j['type'] === 'Pendidikan'));
$awards = array_values(array_filter($journey, fn ($j) => $j['type'] === 'Lomba'));
?><!doctype html>
<html lang="<?= is_en() ? 'en' : 'id' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($ownerName) ?> — CV</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1b1430;
            --muted: #625a78;
            --line: #e7e1f2;
            --accent: #6c4de6;
            --soft: #f6f3ff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eeeaf7;
            color: var(--ink);
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 12px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        .toolbar {
            position: sticky; top: 0; z-index: 5;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            max-width: 900px; margin: 0 auto; padding: 18px 24px;
        }
        .toolbar a, .toolbar button {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: inherit; font-size: 12px; font-weight: 600;
            padding: 10px 16px; border-radius: 999px; cursor: pointer;
            border: 1px solid #d9d0ef; background: #fff; color: var(--ink); text-decoration: none;
        }
        .toolbar button { background: var(--ink); color: #fff; border-color: var(--ink); }
        .paper {
            width: 900px; min-height: 1200px; margin: 0 auto 48px;
            background: #fff; padding: 54px 58px;
            box-shadow: 0 30px 80px -30px rgba(43, 27, 90, .35);
        }
        .head { display: flex; justify-content: space-between; gap: 32px; align-items: flex-start; padding-bottom: 26px; border-bottom: 1px solid var(--line); }
        .head h1 { margin: 0 0 6px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 38px; line-height: 1.05; letter-spacing: -.03em; }
        .head .role { margin: 0; color: var(--accent); font-weight: 600; font-size: 13px; }
        .head .summary { margin: 14px 0 0; color: var(--muted); max-width: 520px; }
        .head img { width: 104px; height: 104px; border-radius: 26px; object-fit: cover; }
        .contact { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 14px; font-size: 11px; color: var(--muted); }
        .contact span { display: inline-flex; align-items: center; gap: 6px; }
        .contact b { color: var(--ink); font-weight: 500; }
        .grid { display: grid; grid-template-columns: 1.7fr 1fr; gap: 34px; margin-top: 30px; }
        .section { margin-bottom: 26px; }
        .section h2 {
            margin: 0 0 12px; font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--accent);
        }
        .item { margin-bottom: 16px; }
        .item:last-child { margin-bottom: 0; }
        .item h3 { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; font-weight: 700; }
        .item .meta { display: flex; justify-content: space-between; gap: 12px; font-size: 10.5px; color: var(--muted); margin-bottom: 3px; }
        .item p { margin: 4px 0 0; color: var(--muted); font-size: 11.5px; }
        .tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
        .tags span { font-size: 9.5px; padding: 3px 8px; border-radius: 999px; background: var(--soft); color: var(--accent); }
        .side-block { padding: 16px 18px; background: var(--soft); border-radius: 14px; margin-bottom: 16px; }
        .side-block h3 { margin: 0 0 8px; font-size: 10px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .side-block p { margin: 0 0 10px; font-size: 11px; color: var(--ink); }
        .side-block p:last-child { margin-bottom: 0; }
        .side-block strong { display: block; font-size: 11px; }
        .side-block small { color: var(--muted); font-size: 10px; }
        .skill-group { margin-bottom: 10px; }
        .skill-group strong { display: block; font-size: 10px; color: var(--muted); margin-bottom: 3px; }
        .skill-group span { font-size: 11px; color: var(--ink); }
        .badges { display: flex; flex-wrap: wrap; gap: 4px; }
        .badges span { font-size: 9.5px; padding: 3px 7px; border: 1px solid var(--line); border-radius: 999px; }
        .cert { font-size: 11px; margin-bottom: 8px; }
        .cert strong { display: block; font-weight: 600; }
        .cert span { color: var(--muted); font-size: 10px; }
        .footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; color: var(--muted); font-size: 9.5px; }
        .print-hint { max-width: 900px; margin: 0 auto 14px; padding: 0 24px; color: #6b6285; font-size: 11px; }
        @media (max-width: 960px) {
            .paper { width: auto; margin: 0 14px 32px; padding: 34px 26px; min-height: 0; }
            .grid { grid-template-columns: 1fr; gap: 8px; }
            .head { flex-direction: column-reverse; }
            .head img { width: 76px; height: 76px; }
            .toolbar { padding: 14px; }
        }
        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #fff; font-size: 10.5px; }
            .toolbar, .print-hint { display: none !important; }
            .paper { width: auto; margin: 0; padding: 0; box-shadow: none; min-height: 0; }
            .head { padding-bottom: 18px; }
            .head h1 { font-size: 30px; }
            .grid { margin-top: 20px; gap: 24px; }
            .section { margin-bottom: 16px; break-inside: avoid; }
            .item { break-inside: avoid; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="index.php">← <?= e(t('back_to_home')) ?></a>
        <button type="button" data-print><?= is_en() ? 'Print / Save as PDF' : 'Print / Simpan sebagai PDF' ?></button>
    </div>
    <p class="print-hint"><?= is_en() ? 'This resume always follows the latest data from the admin dashboard.' : 'CV ini selalu mengikuti data terbaru dari dashboard admin.' ?></p>

    <main class="paper">
        <header class="head">
            <div>
                <h1><?= e($ownerName) ?></h1>
                <p class="role"><?= e($headline) ?></p>
                <p class="summary"><?= e($bio) ?></p>
                <div class="contact">
                    <span><?= e($email) ?></span>
                    <span><b><?= e(setting('location')) ?></b></span>
                    <span><?= e(preg_replace('#^https?://#', '', setting('linkedin'))) ?></span>
                    <span><?= e(preg_replace('#^https?://#', '', setting('github'))) ?></span>
                    <?php if (setting('whatsapp')): ?><span><?= e(setting('whatsapp')) ?></span><?php endif; ?>
                </div>
            </div>
            <img src="<?= e(image_url(setting('avatar'))) ?>" alt="<?= e($ownerName) ?>">
        </header>

        <div class="grid">
            <div>
                <?php if ($work): ?>
                <section class="section">
                    <h2><?= is_en() ? 'Experience & involvement' : 'Pengalaman & keterlibatan' ?></h2>
                    <?php foreach ($work as $item): ?>
                    <div class="item">
                        <div class="meta"><span><?= e($item['organization']) ?></span><span><?= e($item['year']) ?></span></div>
                        <h3><?= e($item['title']) ?></h3>
                        <p><?= e($item['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <?php if ($projects): ?>
                <section class="section">
                    <h2><?= is_en() ? 'Selected projects' : 'Proyek pilihan' ?></h2>
                    <?php foreach ($projects as $project): ?>
                    <div class="item">
                        <div class="meta"><span><?= e(category_label($project['category'])) ?></span><span><?= e(date('Y', strtotime($project['created_at']))) ?></span></div>
                        <h3><?= e($project['title']) ?></h3>
                        <p><?= e(excerpt_text(tr($project, 'summary'), 180)) ?></p>
                        <div class="tags"><?php foreach (array_slice(tech_array($project['tech_stack']), 0, 6) as $tech): ?><span><?= e($tech) ?></span><?php endforeach; ?></div>
                    </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <?php if ($education): ?>
                <section class="section">
                    <h2><?= is_en() ? 'Education' : 'Pendidikan' ?></h2>
                    <?php foreach ($education as $item): ?>
                    <div class="item">
                        <div class="meta"><span><?= e($item['organization']) ?></span><span><?= e($item['year']) ?></span></div>
                        <h3><?= e($item['title']) ?></h3>
                        <p><?= e($item['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>
            </div>

            <aside>
                <div class="side-block">
                    <h3><?= is_en() ? 'Education' : 'Pendidikan' ?></h3>
                    <strong><?= e(setting('major')) ?></strong>
                    <p><?= e(setting('campus')) ?></p>
                    <small>IPK <?= e(setting('ipk')) ?> / 4.00 · <?= e(setting('grad_year')) ?></small>
                </div>

                <?php foreach ($skillGroups as $group => $items): ?>
                <div class="side-block">
                    <h3><?= e($group) ?></h3>
                    <span style="font-size:11px"><?= e(implode(' · ', $items)) ?></span>
                </div>
                <?php endforeach; ?>

                <?php if ($soft): ?>
                <div class="side-block">
                    <h3><?= is_en() ? 'Soft skills' : 'Soft skills' ?></h3>
                    <div class="badges"><?php foreach ($soft as $s): ?><span><?= e($s) ?></span><?php endforeach; ?></div>
                </div>
                <?php endif; ?>

                <div class="side-block">
                    <h3><?= is_en() ? 'Certifications' : 'Sertifikasi' ?></h3>
                    <?php foreach (array_slice($certificates, 0, 4) as $cert): ?>
                    <div class="cert"><strong><?= e($cert['title']) ?></strong><span><?= e($cert['issuer']) ?> · <?= e($cert['year']) ?></span></div>
                    <?php endforeach; ?>
                </div>

                <?php if ($awards): ?>
                <div class="side-block">
                    <h3><?= is_en() ? 'Awards' : 'Penghargaan' ?></h3>
                    <?php foreach ($awards as $award): ?>
                    <div class="cert"><strong><?= e($award['title']) ?></strong><span><?= e($award['year']) ?></span></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </aside>
        </div>

        <div class="footer">
            <span><?= e($ownerName) ?> · <?= e($headline) ?></span>
            <span><?= e(setting('site_name')) ?> · <?= date('Y') ?></span>
        </div>
    </main>
    <script nonce="<?= e(csp_nonce()) ?>">
    (function () {
        var btn = document.querySelector('[data-print]');
        if (btn) btn.addEventListener('click', function () { window.print(); });
    })();
    </script>
</body>
</html>
