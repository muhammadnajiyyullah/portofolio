<?php
if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../config.php';
}

track_visit();

$siteName   = setting('site_name', 'Dev Journal');
$ownerName  = setting('owner_name', 'Raka Pratama');
$headline   = tr(['headline' => setting('headline_id'), 'headline_en' => setting('headline_en')], 'headline');
$preset     = setting('theme_preset', 'lavender');
$fontPreset = setting('font_preset', 'jakarta');
$pageTitle  = $pageTitle ?? ($ownerName . ' — ' . $headline);
$pageDesc   = $pageDesc ?? excerpt_text(tr(['bio' => setting('bio_id'), 'bio_en' => setting('bio_en')], 'bio'), 160);
$ogImage    = $ogImage ?? image_url(setting('avatar'), 'assets/img/avatar.svg');
$activeNav  = $activeNav ?? 'home';
$bodyClass  = $bodyClass ?? 'page-home';
$base       = '';
?><!doctype html>
<html lang="<?= is_en() ? 'en' : 'id' ?>" data-mode="light" data-preset="<?= e($preset) ?>" data-font="<?= e($fontPreset) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="author" content="<?= e($ownerName) ?>">
    <meta name="theme-color" content="#FAF8FF">
    <link rel="canonical" href="<?= e((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Sora:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">

    <script nonce="<?= e(csp_nonce()) ?>">
    (function () {
        try {
            var mode = localStorage.getItem('dj-mode');
            if (!mode) {
                mode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.dataset.mode = mode;
        } catch (e) {}
    })();
    </script>

    <script type="application/ld+json" nonce="<?= e(csp_nonce()) ?>">
    {
        "@context": "https://schema.org",
        "@type": "Person",
        "name": <?= json_for_script($ownerName) ?>,
        "jobTitle": <?= json_for_script($headline) ?>,
        "email": <?= json_for_script(setting('email')) ?>,
        "url": <?= json_for_script((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/') ?>,
        "sameAs": [<?= json_for_script(setting('github')) ?>, <?= json_for_script(setting('linkedin')) ?>]
    }
    </script>
</head>
<body class="<?= e($bodyClass) ?>">

<a class="skip-link" href="#main">Lewati ke konten utama</a>

<div class="loader" id="loader" aria-hidden="true">
    <div class="loader__inner">
        <div class="loader__mark"><?= e(setting('owner_initial', 'RP')) ?></div>
        <div class="loader__bar"><span></span></div>
        <p class="loader__text"><?= e(t('loading')) ?></p>
    </div>
</div>

<div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>
<div class="cursor-dot" id="cursorDot" aria-hidden="true"></div>
<div class="cursor-ring" id="cursorRing" aria-hidden="true"></div>

<header class="site-header" id="siteHeader">
    <nav class="nav container" aria-label="Navigasi utama">
        <a href="index.php" class="brand" aria-label="<?= e($siteName) ?> — home">
            <span class="brand__mark"><?= e(setting('owner_initial', 'RP')) ?></span>
            <span class="brand__text"><?= e($siteName) ?><span class="brand__dot">.</span></span>
        </a>

        <div class="nav__links" id="navLinks">
            <a href="index.php" class="<?= $activeNav === 'home' ? 'is-active' : '' ?>"><?= e(t('nav_home')) ?></a>
            <a href="index.php#about" class="<?= $activeNav === 'about' ? 'is-active' : '' ?>"><?= e(t('nav_about')) ?></a>
            <a href="index.php#projects" class="<?= $activeNav === 'projects' ? 'is-active' : '' ?>"><?= e(t('nav_projects')) ?></a>
            <a href="index.php#skills" class="<?= $activeNav === 'skills' ? 'is-active' : '' ?>"><?= e(t('nav_skills')) ?></a>
            <a href="index.php#journey" class="<?= $activeNav === 'journey' ? 'is-active' : '' ?>"><?= e(t('nav_journey')) ?></a>
            <a href="blog.php" class="<?= $activeNav === 'blog' ? 'is-active' : '' ?>"><?= e(t('nav_blog')) ?></a>
            <a href="index.php#contact" class="<?= $activeNav === 'contact' ? 'is-active' : '' ?>"><?= e(t('nav_contact')) ?></a>
        </div>

        <div class="nav__actions">
            <div class="lang-switch" role="group" aria-label="<?= e(t('language')) ?>">
                <button type="button" data-lang="id" class="<?= is_en() ? '' : 'is-active' ?>" aria-pressed="<?= is_en() ? 'false' : 'true' ?>">ID</button>
                <button type="button" data-lang="en" class="<?= is_en() ? 'is-active' : '' ?>" aria-pressed="<?= is_en() ? 'true' : 'false' ?>">EN</button>
            </div>
            <button type="button" class="icon-btn theme-toggle" id="themeToggle" aria-label="<?= e(t('theme_dark')) ?>" title="<?= e(t('theme_dark')) ?>">
                <span class="theme-toggle__sun"><?= icon('sun', 17) ?></span>
                <span class="theme-toggle__moon"><?= icon('moon', 17) ?></span>
            </button>
            <button type="button" class="icon-btn palette-trigger" data-open-palette aria-label="<?= e(t('palette_title')) ?>" title="<?= e(t('palette_title')) ?>">
                <?= icon('search', 17) ?>
                <kbd>⌘K</kbd>
            </button>
            <button type="button" class="btn btn--sm btn--outline nav__recruiter" data-open-recruiter>
                <?= icon('briefcase', 15) ?><span><?= e(t('recruiter_mode')) ?></span>
            </button>
            <button type="button" class="menu-toggle" id="menuToggle" aria-expanded="false" aria-controls="mobileMenu" aria-label="<?= e(t('menu')) ?>">
                <span></span><span></span>
            </button>
        </div>
    </nav>
</header>

<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <div class="mobile-menu__inner">
        <p class="mobile-menu__label"><?= e(t('menu')) ?></p>
        <nav aria-label="Navigasi mobile">
            <a href="index.php"><?= e(t('nav_home')) ?><span>01</span></a>
            <a href="index.php#about"><?= e(t('nav_about')) ?><span>02</span></a>
            <a href="index.php#projects"><?= e(t('nav_projects')) ?><span>03</span></a>
            <a href="index.php#skills"><?= e(t('nav_skills')) ?><span>04</span></a>
            <a href="index.php#journey"><?= e(t('nav_journey')) ?><span>05</span></a>
            <a href="blog.php"><?= e(t('nav_blog')) ?><span>06</span></a>
            <a href="index.php#contact"><?= e(t('nav_contact')) ?><span>07</span></a>
        </nav>
        <div class="mobile-menu__foot">
            <button type="button" class="btn btn--primary btn--block" data-open-recruiter><?= icon('briefcase', 16) ?> <?= e(t('recruiter_mode')) ?></button>
            <div class="mobile-menu__socials">
                <a href="<?= e(setting('github')) ?>" target="_blank" rel="noopener" aria-label="GitHub"><?= icon('github', 20) ?></a>
                <a href="<?= e(setting('linkedin')) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><?= icon('linkedin', 20) ?></a>
                <a href="mailto:<?= e(setting('email')) ?>" aria-label="Email"><?= icon('mail', 20) ?></a>
            </div>
        </div>
    </div>
</div>

<main id="main">
