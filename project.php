<?php
require_once __DIR__ . '/config.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$isPreview = !empty($_GET['preview']) && admin_user();
$project = one_row('projects', 'slug = ?', [$slug]);

if (!$project || ($project['status'] !== 'published' && !$isPreview)) {
    http_response_code(404);
    $activeNav = 'projects';
    $bodyClass = 'page-404';
    $pageTitle = is_en() ? 'Project not found — ' . setting('owner_name') : 'Proyek tidak ditemukan — ' . setting('owner_name');
    require __DIR__ . '/inc/header.php';
    ?>
    <section class="section section--404">
        <div class="container not-found">
            <span class="not-found__code">404</span>
            <h1><?= is_en() ? 'This project has wandered off.' : 'Proyek ini sepertinya tersesat.' ?></h1>
            <p><?= is_en() ? 'The link may be broken, or the project is still a draft.' : 'Tautannya mungkin salah, atau proyeknya masih berstatus draft.' ?></p>
            <a href="index.php#projects" class="btn btn--primary"><?= e(t('view_all_projects')) ?> <?= icon('arrow-right', 16) ?></a>
        </div>
    </section>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

$viewKey = 'viewed_project_' . $project['id'];
if (empty($_SESSION[$viewKey])) {
    db()->prepare('UPDATE projects SET views = views + 1 WHERE id = ?')->execute([$project['id']]);
    $project['views'] = (int) $project['views'] + 1;
    $_SESSION[$viewKey] = 1;
}

$gallery = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $project['gallery']) ?: [])));
$techs = tech_array($project['tech_stack']);

$reactionRows = db()->prepare('SELECT emoji, count FROM reactions WHERE project_id = ?');
$reactionRows->execute([$project['id']]);
$reactionCounts = [];
foreach ($reactionRows->fetchAll() as $r) {
    $reactionCounts[$r['emoji']] = (int) $r['count'];
}
$reactionEmojis = ['🔥', '👏', '💡'];

$next = next_project((int) $project['id']);

$activeNav = 'projects';
$bodyClass = 'page-project';
$pageTitle = $project['title'] . ' — ' . setting('owner_name');
$pageDesc = excerpt_text(tr($project, 'summary'), 170);
$ogImage = image_url($project['thumbnail']);
require __DIR__ . '/inc/header.php';
?>

<article class="project-detail">
    <header class="project-hero">
        <div class="project-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="index.php"><?= e(t('nav_home')) ?></a><span>/</span>
                <a href="index.php#projects"><?= e(t('nav_projects')) ?></a><span>/</span>
                <span aria-current="page"><?= e($project['title']) ?></span>
            </nav>

            <?php if ($project['status'] !== 'published'): ?>
            <div class="draft-banner"><?= icon('eye-off', 15) ?> <?= is_en() ? 'Draft preview — only visible to you.' : 'Pratinjau draft — hanya terlihat olehmu.' ?></div>
            <?php endif; ?>

            <div class="project-hero__head">
                <div class="project-hero__intro">
                    <div class="project-hero__meta">
                        <span class="project-card__category"><?= e(category_label($project['category'])) ?></span>
                        <?php if ($project['featured']): ?><span class="project-card__featured"><?= icon('sparkles', 12) ?> Featured</span><?php endif; ?>
                        <span class="project-hero__views"><?= icon('eye', 13) ?> <?= number_format((int) $project['views'], 0, ',', '.') ?> views</span>
                    </div>
                    <h1><?= e($project['title']) ?></h1>
                    <p class="project-hero__summary"><?= e(tr($project, 'summary')) ?></p>
                    <div class="project-hero__actions">
                        <?php if ($project['live_url']): ?>
                        <a href="<?= e($project['live_url']) ?>" target="_blank" rel="noopener" class="btn btn--primary"><?= icon('external', 16) ?> <?= e(t('live_demo')) ?></a>
                        <?php endif; ?>
                        <?php if ($project['github_url']): ?>
                        <a href="<?= e($project['github_url']) ?>" target="_blank" rel="noopener" class="btn btn--outline"><?= icon('github', 16) ?> <?= e(t('source_code')) ?></a>
                        <?php endif; ?>
                        <a href="index.php#contact" class="btn btn--ghost"><?= is_en() ? 'Discuss a project' : 'Diskusikan proyek' ?> <?= icon('arrow-right', 15) ?></a>
                    </div>
                </div>
                <div class="project-hero__art">
                    <img src="<?= e(image_url($project['thumbnail'])) ?>" alt="<?= e($project['title']) ?>" width="1200" height="750" fetchpriority="high">
                </div>
            </div>
        </div>
    </header>

    <div class="container project-layout">
        <aside class="project-sidebar">
            <div class="project-sidebar__box">
                <h2><?= is_en() ? 'Project details' : 'Detail proyek' ?></h2>
                <dl>
                    <?php if ($project['role']): ?>
                    <div><dt><?= e(t('role')) ?></dt><dd><?= e($project['role']) ?></dd></div>
                    <?php endif; ?>
                    <div><dt><?= is_en() ? 'Year' : 'Tahun' ?></dt><dd><?= e(date('Y', strtotime($project['created_at']))) ?></dd></div>
                    <div><dt><?= is_en() ? 'Category' : 'Kategori' ?></dt><dd><?= e(category_label($project['category'])) ?></dd></div>
                </dl>
                <?php if ($techs): ?>
                <h3>Tech stack</h3>
                <div class="tech-list tech-list--stack">
                    <?php foreach ($techs as $tech): ?><span><?= e($tech) ?></span><?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="project-sidebar__box project-sidebar__react">
                <h2><?= e(t('reactions')) ?></h2>
                <div class="reaction-bar" data-project-id="<?= (int) $project['id'] ?>">
                    <?php foreach ($reactionEmojis as $emoji): ?>
                    <button type="button" class="reaction" data-emoji="<?= e($emoji) ?>" aria-label="React <?= e($emoji) ?>">
                        <span><?= $emoji ?></span><strong data-count><?= (int) ($reactionCounts[$emoji] ?? 0) ?></strong>
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="reaction-note"><?= is_en() ? 'One click, no account needed.' : 'Satu klik, tanpa perlu akun.' ?></p>
            </div>

            <a href="index.php#projects" class="back-link"><?= icon('arrow-left', 15) ?> <?= e(t('back_to_home')) ?></a>
        </aside>

        <div class="project-main">
            <section class="case-grid">
                <?php
                $cases = [
                    'problem' => ['label' => t('problem'), 'icon' => 'search', 'value' => $project['problem']],
                    'solution' => ['label' => t('solution'), 'icon' => 'zap', 'value' => $project['solution']],
                    'role' => ['label' => t('role'), 'icon' => 'user', 'value' => $project['role']],
                    'result' => ['label' => t('result'), 'icon' => 'trending', 'value' => $project['result']],
                ];
                foreach ($cases as $key => $case):
                    if (!trim((string) $case['value'])) continue; ?>
                <div class="case-card case-card--<?= e($key) ?>">
                    <span class="case-card__icon"><?= icon($case['icon'], 17) ?></span>
                    <h2><?= e($case['label']) ?></h2>
                    <p><?= nl2br(e($case['value'])) ?></p>
                </div>
                <?php endforeach; ?>
            </section>

            <?php if (trim((string) $project['description'])): ?>
            <section class="prose project-prose">
                <p class="eyebrow"><span></span><?= is_en() ? 'The story' : 'Cerita di baliknya' ?></p>
                <?= $project['description'] ?>
            </section>
            <?php endif; ?>

            <?php if ($gallery): ?>
            <section class="project-gallery">
                <h2 class="project-section-title"><?= e(t('gallery')) ?></h2>
                <div class="gallery-grid">
                    <?php foreach ($gallery as $img): ?>
                    <a href="<?= e(image_url($img)) ?>" class="gallery-item" target="_blank" rel="noopener">
                        <img src="<?= e(image_url($img)) ?>" alt="<?= e($project['title']) ?> screenshot" loading="lazy">
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <div class="project-reactions-bottom">
                <span><?= e(t('reactions')) ?></span>
                <div class="reaction-bar" data-project-id="<?= (int) $project['id'] ?>">
                    <?php foreach ($reactionEmojis as $emoji): ?>
                    <button type="button" class="reaction" data-emoji="<?= e($emoji) ?>">
                        <span><?= $emoji ?></span><strong data-count><?= (int) ($reactionCounts[$emoji] ?? 0) ?></strong>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</article>

<?php if ($next): ?>
<section class="next-project">
    <div class="container">
        <a href="project.php?slug=<?= e($next['slug']) ?>" class="next-project__link">
            <div>
                <span><?= e(t('next_project')) ?></span>
                <h2><?= e($next['title']) ?></h2>
            </div>
            <span class="next-project__arrow"><?= icon('arrow-right', 26) ?></span>
        </a>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
