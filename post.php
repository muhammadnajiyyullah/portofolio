<?php
require_once __DIR__ . '/config.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$isPreview = !empty($_GET['preview']) && admin_user();
$post = one_row('posts', 'slug = ?', [$slug]);

if (!$post || ($post['status'] !== 'published' && !$isPreview)) {
    http_response_code(404);
    $activeNav = 'blog';
    $bodyClass = 'page-404';
    $pageTitle = is_en() ? 'Article not found' : 'Artikel tidak ditemukan';
    require __DIR__ . '/inc/header.php';
    ?>
    <section class="section section--404">
        <div class="container not-found">
            <span class="not-found__code">404</span>
            <h1><?= is_en() ? 'This note is missing.' : 'Catatan ini tidak ditemukan.' ?></h1>
            <p><?= is_en() ? 'It may have been moved or is still a draft.' : 'Mungkin sudah dipindahkan atau masih berupa draft.' ?></p>
            <a href="blog.php" class="btn btn--primary"><?= e(t('back_to_blog')) ?> <?= icon('arrow-right', 16) ?></a>
        </div>
    </section>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

$related = array_values(array_filter(
    all_rows('posts', "status = 'published' AND id <> ?", [$post['id']], 'published_at DESC LIMIT 3'),
    fn ($p) => true
));
$related = array_slice($related, 0, 2);

$activeNav = 'blog';
$bodyClass = 'page-post';
$pageTitle = $post['title'] . ' — ' . setting('owner_name');
$pageDesc = excerpt_text((string) $post['excerpt'], 170);
$ogImage = image_url($post['cover']);
require __DIR__ . '/inc/header.php';
?>

<article class="post-detail">
    <header class="post-header">
        <div class="container post-header__inner">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="index.php"><?= e(t('nav_home')) ?></a><span>/</span>
                <a href="blog.php"><?= e(t('nav_blog')) ?></a><span>/</span>
                <span aria-current="page"><?= e($post['title']) ?></span>
            </nav>
            <?php if ($post['status'] !== 'published'): ?>
            <div class="draft-banner"><?= icon('eye-off', 15) ?> <?= is_en() ? 'Draft preview — only visible to you.' : 'Pratinjau draft — hanya terlihat olehmu.' ?></div>
            <?php endif; ?>
            <div class="post-header__meta">
                <span class="type-pill"><?= e(t('blog_label')) ?></span>
                <span><?= e(format_date($post['published_at'])) ?></span>
                <span>·</span>
                <span><?= reading_time((string) $post['content']) ?> <?= e(t('min_read')) ?></span>
            </div>
            <h1><?= e($post['title']) ?></h1>
            <p class="post-header__excerpt"><?= e($post['excerpt']) ?></p>
            <div class="post-header__author">
                <img src="<?= e(image_url(setting('avatar'))) ?>" alt="<?= e(setting('owner_name')) ?>" width="44" height="44">
                <div><strong><?= e(setting('owner_name')) ?></strong><span><?= e(tr(['h' => setting('headline_id'), 'h_en' => setting('headline_en')], 'h')) ?></span></div>
            </div>
        </div>
        <div class="container">
            <figure class="post-cover">
                <img src="<?= e(image_url($post['cover'])) ?>" alt="<?= e($post['title']) ?>" width="1000" height="600" fetchpriority="high">
            </figure>
        </div>
    </header>

    <div class="container post-body">
        <aside class="post-share">
            <span><?= is_en() ? 'Share' : 'Bagikan' ?></span>
            <button type="button" class="share-btn" data-share="copy" data-label="<?= is_en() ? 'Copy link' : 'Salin tautan' ?>" aria-label="<?= is_en() ? 'Copy link' : 'Salin tautan' ?>"><?= icon('copy', 16) ?></button>
            <a class="share-btn" href="https://wa.me/?text=<?= rawurlencode($post['title'] . ' — ' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . $_SERVER['REQUEST_URI']) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('whatsapp', 16) ?></a>
            <a class="share-btn" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . $_SERVER['REQUEST_URI']) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><?= icon('linkedin', 16) ?></a>
        </aside>

        <div class="prose post-prose">
            <?= $post['content'] ?>
        </div>

        <div class="post-author-box">
            <img src="<?= e(image_url(setting('avatar'))) ?>" alt="<?= e(setting('owner_name')) ?>" width="72" height="72">
            <div>
                <span><?= is_en() ? 'Written by' : 'Ditulis oleh' ?></span>
                <h3><?= e(setting('owner_name')) ?></h3>
                <p><?= e(tr(['b' => setting('bio_id'), 'b_en' => setting('bio_en')], 'b')) ?></p>
                <div class="post-author-box__links">
                    <a href="index.php#about" class="text-link"><?= e(t('nav_about')) ?> <?= icon('arrow-right', 14) ?></a>
                    <a href="index.php#contact" class="text-link"><?= e(t('nav_contact')) ?> <?= icon('arrow-right', 14) ?></a>
                </div>
            </div>
        </div>

        <div class="post-nav">
            <a href="blog.php" class="back-link"><?= icon('arrow-left', 15) ?> <?= e(t('back_to_blog')) ?></a>
        </div>
    </div>
</article>

<?php if ($related): ?>
<section class="section section--tint related-posts">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span><?= is_en() ? 'Keep reading' : 'Lanjut membaca' ?></p>
                <h2 class="section-title"><?= is_en() ? 'Other notes.' : 'Catatan lainnya.' ?></h2>
            </div>
        </div>
        <div class="post-grid">
            <?php foreach ($related as $i => $rp): ?>
            <article class="post-card reveal" style="--d: <?= $i * 0.06 ?>s">
                <a href="post.php?slug=<?= e($rp['slug']) ?>" class="post-card__media">
                    <img src="<?= e(image_url($rp['cover'])) ?>" alt="<?= e($rp['title']) ?>" loading="lazy" width="1000" height="600">
                </a>
                <div class="post-card__body">
                    <div class="post-card__meta"><span><?= e(format_date($rp['published_at'])) ?></span><span>·</span><span><?= reading_time((string) $rp['content']) ?> <?= e(t('min_read')) ?></span></div>
                    <h3><a href="post.php?slug=<?= e($rp['slug']) ?>"><?= e($rp['title']) ?></a></h3>
                    <p><?= e(excerpt_text((string) $rp['excerpt'], 120)) ?></p>
                    <a href="post.php?slug=<?= e($rp['slug']) ?>" class="text-link"><?= e(t('read_article')) ?> <?= icon('arrow-right', 14) ?></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
