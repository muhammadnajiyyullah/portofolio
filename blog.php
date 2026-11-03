<?php
require_once __DIR__ . '/config.php';

$activeNav = 'blog';
$bodyClass = 'page-blog';
$pageTitle = (is_en() ? 'Dev Journal' : 'Dev Journal') . ' — ' . setting('owner_name');
$pageDesc = is_en()
    ? 'Learning notes, tutorials, and reflections on building software with care.'
    : 'Catatan belajar, tutorial, dan refleksi tentang membangun perangkat lunak dengan hati-hati.';

$posts = all_rows('posts', "status = 'published'", [], 'published_at DESC, sort_order ASC, id DESC');
$featured = $posts[0] ?? null;
$rest = array_slice($posts, 1);

require __DIR__ . '/inc/header.php';
?>

<section class="page-hero">
    <div class="container">
        <p class="eyebrow"><span></span>06 — <?= e(t('blog_label')) ?></p>
        <h1><?= is_en() ? 'Notes from the workshop.' : 'Catatan dari ruang kerja.' ?></h1>
        <p class="page-hero__sub"><?= is_en() ? 'Learning notes, tutorials, and small reflections — written while building things.' : 'Catatan belajar, tutorial, dan refleksi kecil — ditulis sambil membangun sesuatu.' ?></p>
        <div class="page-hero__meta">
            <span><?= count($posts) ?> <?= is_en() ? 'articles' : 'artikel' ?></span>
            <span>·</span>
            <span><?= is_en() ? 'No algorithms, just notes.' : 'Tanpa algoritma, hanya catatan.' ?></span>
        </div>
    </div>
</section>

<section class="section section--blog-list">
    <div class="container">
        <?php if (!$posts): ?>
        <div class="empty-state">
            <?= icon('file', 30) ?>
            <h2><?= is_en() ? 'No notes yet.' : 'Belum ada catatan.' ?></h2>
            <p><?= is_en() ? 'The first article is still brewing.' : 'Artikel pertama masih diseduh.' ?></p>
        </div>
        <?php else: ?>

        <?php if ($featured): ?>
        <article class="blog-featured reveal">
            <a href="post.php?slug=<?= e($featured['slug']) ?>" class="blog-featured__media">
                <img src="<?= e(image_url($featured['cover'])) ?>" alt="<?= e($featured['title']) ?>" width="1000" height="600" fetchpriority="high">
            </a>
            <div class="blog-featured__body">
                <div class="post-card__meta">
                    <span><?= e(format_date($featured['published_at'])) ?></span><span>·</span><span><?= reading_time((string) $featured['content']) ?> <?= e(t('min_read')) ?></span>
                </div>
                <h2><a href="post.php?slug=<?= e($featured['slug']) ?>"><?= e($featured['title']) ?></a></h2>
                <p><?= e(excerpt_text((string) $featured['excerpt'], 190)) ?></p>
                <a href="post.php?slug=<?= e($featured['slug']) ?>" class="btn btn--primary btn--sm"><?= e(t('read_article')) ?> <?= icon('arrow-right', 15) ?></a>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($rest): ?>
        <div class="post-grid post-grid--archive">
            <?php foreach ($rest as $i => $post): ?>
            <article class="post-card reveal" style="--d: <?= $i * 0.06 ?>s">
                <a href="post.php?slug=<?= e($post['slug']) ?>" class="post-card__media">
                    <img src="<?= e(image_url($post['cover'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy" width="1000" height="600">
                </a>
                <div class="post-card__body">
                    <div class="post-card__meta">
                        <span><?= e(format_date($post['published_at'])) ?></span><span>·</span><span><?= reading_time((string) $post['content']) ?> <?= e(t('min_read')) ?></span>
                    </div>
                    <h3><a href="post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
                    <p><?= e(excerpt_text((string) $post['excerpt'], 130)) ?></p>
                    <a href="post.php?slug=<?= e($post['slug']) ?>" class="text-link"><?= e(t('read_article')) ?> <?= icon('arrow-right', 14) ?></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
