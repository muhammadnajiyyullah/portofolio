<?php
require_once __DIR__ . '/config.php';

$activeNav = 'home';
$bodyClass = 'page-home';
$ownerName = setting('owner_name', 'Raka Pratama');
$headline  = tr(['headline' => setting('headline_id'), 'headline_en' => setting('headline_en')], 'headline');
$bio       = tr(['bio' => setting('bio_id'), 'bio_en' => setting('bio_en')], 'bio');
$about     = tr(['about' => setting('about_id'), 'about_en' => setting('about_en')], 'about');
$email     = setting('email');
$avatar    = image_url(setting('avatar'));
$cvLink    = setting('cv_file') !== '' ? setting('cv_file') : 'cv.php';
$funFacts  = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', setting('fun_facts')) ?: [])));

$projects     = all_rows('projects', "status = 'published'", [], 'featured DESC, sort_order ASC, id ASC');
$skills       = all_rows('skills', '1=1', [], 'sort_order ASC, id ASC');
$journey      = all_rows('journey', '1=1', [], 'sort_order ASC, id ASC');
$certificates = all_rows('certificates', '1=1', [], 'sort_order ASC, id ASC');
$posts        = all_rows('posts', "status = 'published'", [], 'published_at DESC, sort_order ASC');
$testimonials = all_rows('testimonials', '1=1', [], 'sort_order ASC, id ASC');
$guestbook    = all_rows('guestbook', '1=1', [], 'id DESC LIMIT 6');

$skillGroups = [];
foreach ($skills as $skill) {
    $skillGroups[$skill['category']][] = $skill;
}
$skillGroupMeta = [
    'Frontend' => ['icon' => 'layout', 'label' => 'Frontend'],
    'Backend' => ['icon' => 'server', 'label' => 'Backend'],
    'Database' => ['icon' => 'database', 'label' => 'Database'],
    'Tools' => ['icon' => 'tools', 'label' => 'Tools'],
    'Soft Skills' => ['icon' => 'users', 'label' => 'Soft Skills'],
];
$categories = [];
foreach ($projects as $project) {
    $categories[$project['category']] = category_label($project['category']);
}

$orbitNames = ['React', 'PHP', 'TypeScript', 'Figma'];
$orbitSkills = [];
foreach ($orbitNames as $name) {
    foreach ($skills as $skill) {
        if (strcasecmp($skill['name'], $name) === 0) {
            $orbitSkills[] = $skill;
            break;
        }
    }
}
if (!$orbitSkills) {
    $orbitSkills = array_slice($skills, 0, 4);
}

$journeyIcons = [
    'Pendidikan' => 'graduation',
    'Organisasi' => 'users',
    'Magang' => 'briefcase',
    'Lomba' => 'trophy',
    'Proyek' => 'folder',
    'Karier' => 'rocket',
];

$pageTitle = $ownerName . ' — ' . $headline;
require __DIR__ . '/inc/header.php';
?>

<section class="hero" id="home">
    <div class="hero__bg" aria-hidden="true">
        <span class="blob blob--1"></span>
        <span class="blob blob--2"></span>
        <span class="hero__grid-lines"></span>
    </div>

    <div class="container hero__grid">
        <div class="hero__content">
            <div class="hero__topline">
                <?php if (bool_setting('open_to_work', true)): ?>
                <span class="status-pill"><span class="status-dot"></span><?= e(t('open_to_work')) ?></span>
                <?php endif; ?>
                <span class="hero__location"><?= icon('map-pin', 14) ?> <?= e(setting('location')) ?></span>
            </div>

            <p class="hero__eyebrow"><?= e(t('hero_greeting')) ?></p>
            <h1 class="hero__title">
                <?= e(explode(' ', $ownerName)[0]) ?> <span><?= e(trim(str_replace(explode(' ', $ownerName)[0], '', $ownerName))) ?></span><span class="accent-dot">.</span>
            </h1>

            <p class="hero__headline">
                <span class="typewriter" data-roles="<?= e(setting('roles', 'Web Developer,Software Engineer')) ?>"></span><span class="typewriter__caret" aria-hidden="true"></span>
            </p>

            <p class="hero__bio"><?= e($bio) ?></p>

            <div class="hero__actions">
                <a href="#projects" class="btn btn--primary"><?= e(t('view_projects')) ?> <?= icon('arrow-right', 17) ?></a>
                <a href="<?= e($cvLink) ?>" class="btn btn--outline" <?= setting('cv_file') !== '' ? 'download' : 'target="_blank"' ?>><?= icon('download', 16) ?> <?= e(t('download_cv')) ?></a>
                <button type="button" class="btn btn--ghost hero__recruiter" data-open-recruiter><?= icon('briefcase', 16) ?> <?= e(t('recruiter_mode')) ?></button>
            </div>

            <div class="hero__stats">
                <div><strong><?= count($projects) ?></strong><span><?= e(t('stat_projects')) ?></span></div>
                <span class="hero__stats-line"></span>
                <div><strong><?= e(setting('years_coding', '4')) ?>+</strong><span><?= e(t('stat_years')) ?></span></div>
                <span class="hero__stats-line"></span>
                <div><strong><?= number_format(total_visits(), 0, ',', '.') ?></strong><span><?= e(t('stat_visits')) ?></span></div>
            </div>

            <button type="button" class="hero__cmd" data-open-palette>
                <?= icon('command', 14) ?>
                <span><?= e(t('command_hint')) ?></span>
                <kbd>Ctrl</kbd><kbd>K</kbd>
            </button>
        </div>

        <div class="hero__visual" data-parallax-scene>
            <div class="avatar-stage" data-parallax="0.06">
                <span class="avatar-stage__ring avatar-stage__ring--1"></span>
                <span class="avatar-stage__ring avatar-stage__ring--2"></span>
                <span class="avatar-stage__dash"></span>
                <div class="avatar-stage__image">
                    <img src="<?= e($avatar) ?>" alt="<?= e($ownerName) ?>" width="640" height="640" fetchpriority="high">
                </div>
                <?php foreach ($orbitSkills as $i => $os): ?>
                <span class="orbit-chip orbit-chip--<?= $i + 1 ?>" data-parallax="<?= 0.04 + ($i * 0.02) ?>">
                    <?= icon($os['icon'] ?: 'code', 14) ?><span><?= e($os['name']) ?></span>
                </span>
                <?php endforeach; ?>
                <div class="avatar-stage__badge">
                    <span class="status-dot"></span>
                    <div><strong><?= e(setting('site_name', 'Dev Journal')) ?></strong><small>v2.0 · <?= date('Y') ?></small></div>
                </div>
            </div>

            <aside class="currently-card" aria-label="<?= e(t('currently')) ?>">
                <div class="currently-card__head">
                    <span class="status-dot"></span>
                    <span><?= e(t('currently')) ?></span>
                    <span class="currently-card__time"><?= date('H:i') ?> WIB</span>
                </div>
                <div class="currently-card__row">
                    <?= icon('book', 16) ?>
                    <div><span><?= e(t('currently_learning')) ?></span><strong><?= e(setting('status_learning')) ?></strong></div>
                </div>
                <div class="currently-card__row">
                    <?= icon('rocket', 16) ?>
                    <div><span><?= e(t('currently_building')) ?></span><strong><?= e(setting('status_building')) ?></strong></div>
                </div>
                <div class="currently-card__row">
                    <?= icon('music', 16) ?>
                    <div><span><?= e(t('currently_listening')) ?></span><strong><?= e(setting('status_listening')) ?></strong></div>
                </div>
            </aside>
        </div>
    </div>

    <div class="hero__scroll">
        <span><?= e(t('scroll_hint')) ?></span>
        <span class="hero__scroll-line"></span>
    </div>

    <div class="marquee" aria-hidden="true">
        <div class="marquee__track">
            <?php
            $marqueeWords = array_merge(['Web Development', 'UI Engineering', 'Product Design', 'Informatics'], array_slice(array_column($skills, 'name'), 0, 8));
            for ($r = 0; $r < 2; $r++):
                foreach ($marqueeWords as $w): ?>
                    <span><?= e($w) ?></span><i>✦</i>
                <?php endforeach;
            endfor; ?>
        </div>
    </div>
</section>

<section class="section" id="about">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span>01 — <?= e(t('about_label')) ?></p>
                <h2 class="section-title"><?= e(t('about_title')) ?></h2>
            </div>
            <div class="section-head__side">
                <span class="section-head__mono">about.me / since 2020</span>
            </div>
        </div>

        <div class="about-grid">
            <div class="about-visual reveal">
                <div class="about-visual__frame">
                    <img src="<?= e(image_url(setting('about_photo'))) ?>" alt="<?= is_en() ? 'Workspace illustration' : 'Ilustrasi ruang kerja' ?>" loading="lazy" width="900" height="1100">
                </div>
                <div class="floating-card floating-card--campus">
                    <?= icon('graduation', 18) ?>
                    <div><span><?= e(setting('campus')) ?></span><strong><?= e(setting('major')) ?> · <?= e(setting('grad_year')) ?></strong></div>
                </div>
                <div class="floating-card floating-card--ipk">
                    <span class="floating-card__label">IPK</span>
                    <strong><?= e(setting('ipk', '3.82')) ?></strong>
                    <small>/ 4.00</small>
                </div>
                <div class="floating-card floating-card--now">
                    <span class="status-dot"></span>
                    <span><?= is_en() ? 'Building' : 'Sedang membuat' ?> <strong><?= e(setting('status_building')) ?></strong></span>
                </div>
            </div>

            <div class="about-content">
                <div class="about-copy">
                    <?php foreach (preg_split('/\n\s*\n/', trim($about)) ?: [] as $para): ?>
                        <p><?= nl2br(e(trim($para))) ?></p>
                    <?php endforeach; ?>
                </div>

                <?php if ($funFacts): ?>
                <div class="fun-facts">
                    <p class="fun-facts__label"><?= is_en() ? 'Small things about me' : 'Hal-hal kecil tentang saya' ?></p>
                    <div class="fun-facts__list">
                        <?php foreach ($funFacts as $fact): ?>
                        <span class="fun-fact"><?= icon('sparkles', 14) ?><?= e($fact) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="about-links">
                    <a href="#journey" class="text-link"><?= e(t('nav_journey')) ?> <?= icon('arrow-right', 14) ?></a>
                    <a href="mailto:<?= e($email) ?>" class="text-link"><?= e($email) ?> <?= icon('arrow-up-right', 14) ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--tint" id="skills">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span>02 — <?= e(t('skills_label')) ?></p>
                <h2 class="section-title"><?= e(t('skills_title')) ?></h2>
            </div>
            <p class="section-head__note"><?= e(t('skills_note')) ?></p>
        </div>

        <div class="skills-grid">
            <?php $g = 0; foreach ($skillGroups as $group => $items):
                $meta = $skillGroupMeta[$group] ?? ['icon' => 'code', 'label' => $group];
                $g++;
                ?>
            <article class="skill-card reveal" style="--d: <?= $g * 0.06 ?>s">
                <div class="skill-card__head">
                    <span class="skill-card__icon"><?= icon($meta['icon'], 19) ?></span>
                    <h3><?= e($meta['label']) ?></h3>
                    <span class="skill-card__count"><?= count($items) ?></span>
                </div>
                <div class="skill-card__badges">
                    <?php foreach ($items as $item): ?>
                    <span class="skill-badge <?= $group === 'Soft Skills' ? 'skill-badge--soft' : '' ?>">
                        <?= icon($item['icon'] ?: 'code', 13) ?><?= e($item['name']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="projects">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span>03 — <?= e(t('projects_label')) ?></p>
                <h2 class="section-title"><?= e(t('projects_title')) ?></h2>
            </div>
            <p class="section-head__note"><?= e(t('projects_note')) ?></p>
        </div>

        <div class="project-filters" role="group" aria-label="Filter proyek">
            <button type="button" class="filter-btn is-active" data-filter="all"><?= e(t('all')) ?> <span><?= count($projects) ?></span></button>
            <?php foreach ($categories as $key => $label): ?>
            <button type="button" class="filter-btn" data-filter="<?= e($key) ?>"><?= e($label) ?> <span><?= count(array_filter($projects, fn ($p) => $p['category'] === $key)) ?></span></button>
            <?php endforeach; ?>
        </div>

        <div class="projects-grid" id="projectsGrid">
            <?php foreach ($projects as $i => $p): ?>
            <article class="project-card <?= $p['featured'] ? 'project-card--featured' : '' ?> reveal" data-category="<?= e($p['category']) ?>" style="--d: <?= min($i * 0.05, 0.25) ?>s">
                <a class="project-card__media" href="project.php?slug=<?= e($p['slug']) ?>" aria-label="<?= e($p['title']) ?>">
                    <img src="<?= e(image_url($p['thumbnail'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy" width="1200" height="750">
                    <span class="project-card__category"><?= e(category_label($p['category'])) ?></span>
                    <?php if ($p['featured']): ?><span class="project-card__featured"><?= icon('sparkles', 12) ?> Featured</span><?php endif; ?>
                    <span class="project-card__hover"><?= icon('arrow-up-right', 16) ?> <?= e(t('view_case')) ?></span>
                </a>
                <div class="project-card__body">
                    <div class="project-card__meta">
                        <span><?= e(implode(' · ', array_slice(tech_array($p['tech_stack']), 0, 2))) ?></span>
                        <span><?= icon('eye', 13) ?> <?= number_format((int) $p['views'], 0, ',', '.') ?></span>
                    </div>
                    <h3><a href="project.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></h3>
                    <p><?= e(tr($p, 'summary')) ?></p>
                    <div class="project-card__foot">
                        <div class="tech-list">
                            <?php foreach (array_slice(tech_array($p['tech_stack']), 0, 4) as $tech): ?>
                            <span><?= e($tech) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="project-card__links">
                            <?php if ($p['github_url']): ?><a href="<?= e($p['github_url']) ?>" target="_blank" rel="noopener" aria-label="GitHub <?= e($p['title']) ?>" title="GitHub"><?= icon('github', 17) ?></a><?php endif; ?>
                            <?php if ($p['live_url']): ?><a href="<?= e($p['live_url']) ?>" target="_blank" rel="noopener" aria-label="Live demo <?= e($p['title']) ?>" title="Live demo"><?= icon('external', 16) ?></a><?php endif; ?>
                            <a href="project.php?slug=<?= e($p['slug']) ?>" class="project-card__go" aria-label="<?= e(t('view_case')) ?>"><?= icon('arrow-right', 16) ?></a>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <p class="filter-empty" id="filterEmpty" hidden><?= is_en() ? 'No projects in this category yet.' : 'Belum ada proyek di kategori ini.' ?></p>
    </div>
</section>

<section class="section section--tint" id="journey">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span>04 — <?= e(t('journey_label')) ?></p>
                <h2 class="section-title"><?= e(t('journey_title')) ?></h2>
            </div>
            <p class="section-head__note"><?= is_en() ? 'Education, organizations, internships, competitions, and work.' : 'Pendidikan, organisasi, magang, lomba, dan pengalaman kerja.' ?></p>
        </div>

        <div class="timeline">
            <?php foreach ($journey as $i => $j): ?>
            <article class="timeline__item reveal" style="--d: <?= min($i * 0.04, 0.2) ?>s">
                <div class="timeline__year"><span><?= e($j['year']) ?></span></div>
                <div class="timeline__rail"><span class="timeline__dot"><?= icon($journeyIcons[$j['type']] ?? 'sparkles', 13) ?></span></div>
                <div class="timeline__card">
                    <div class="timeline__head">
                        <span class="type-pill"><?= e($j['type']) ?></span>
                        <h3><?= e($j['title']) ?></h3>
                    </div>
                    <?php if ($j['organization']): ?><p class="timeline__org"><?= e($j['organization']) ?></p><?php endif; ?>
                    <p class="timeline__desc"><?= e($j['description']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="certificates">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span>05 — <?= e(t('certs_label')) ?></p>
                <h2 class="section-title"><?= e(t('certs_title')) ?></h2>
            </div>
            <p class="section-head__note"><?= is_en() ? 'A few milestones, documented and verifiable.' : 'Beberapa pencapaian yang terdokumentasi dan bisa diverifikasi.' ?></p>
        </div>

        <div class="cert-grid">
            <?php foreach ($certificates as $i => $c): ?>
            <article class="cert-card reveal" style="--d: <?= min($i * 0.06, 0.2) ?>s">
                <div class="cert-card__media">
                    <img src="<?= e(image_url($c['image'])) ?>" alt="<?= e($c['title']) ?>" loading="lazy" width="600" height="420">
                </div>
                <div class="cert-card__body">
                    <span class="cert-card__issuer"><?= e($c['issuer']) ?> · <?= e($c['year']) ?></span>
                    <h3><?= e($c['title']) ?></h3>
                    <?php if ($c['credential_url']): ?>
                    <a href="<?= e($c['credential_url']) ?>" target="_blank" rel="noopener" class="text-link"><?= is_en() ? 'Verify credential' : 'Verifikasi sertifikat' ?> <?= icon('arrow-up-right', 14) ?></a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tint" id="blog">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span>06 — <?= e(t('blog_label')) ?></p>
                <h2 class="section-title"><?= e(t('blog_title')) ?></h2>
            </div>
            <a href="blog.php" class="section-head__link"><?= e(t('view_all_posts')) ?> <?= icon('arrow-right', 15) ?></a>
        </div>

        <div class="post-grid">
            <?php foreach (array_slice($posts, 0, 3) as $i => $post): ?>
            <article class="post-card reveal" style="--d: <?= $i * 0.06 ?>s">
                <a href="post.php?slug=<?= e($post['slug']) ?>" class="post-card__media">
                    <img src="<?= e(image_url($post['cover'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy" width="1000" height="600">
                </a>
                <div class="post-card__body">
                    <div class="post-card__meta">
                        <span><?= e(format_date($post['published_at'])) ?></span>
                        <span>·</span>
                        <span><?= reading_time((string) $post['content']) ?> <?= e(t('min_read')) ?></span>
                    </div>
                    <h3><a href="post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
                    <p><?= e(excerpt_text((string) $post['excerpt'], 120)) ?></p>
                    <a href="post.php?slug=<?= e($post['slug']) ?>" class="text-link"><?= e(t('read_article')) ?> <?= icon('arrow-right', 14) ?></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="testimonials">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span>07 — <?= e(t('testimonials_label')) ?></p>
                <h2 class="section-title"><?= e(t('testimonials_title')) ?></h2>
            </div>
        </div>

        <div class="testimonial-grid">
            <?php foreach ($testimonials as $i => $testi): ?>
            <figure class="testimonial-card reveal" style="--d: <?= $i * 0.06 ?>s">
                <span class="testimonial-card__quote" aria-hidden="true">“</span>
                <blockquote><?= e($testi['quote']) ?></blockquote>
                <figcaption>
                    <img src="<?= e(image_url($testi['avatar'])) ?>" alt="<?= e($testi['name']) ?>" loading="lazy" width="200" height="200">
                    <div><strong><?= e($testi['name']) ?></strong><span><?= e($testi['role']) ?></span></div>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--github" id="github" <?= bool_setting('github_show', true) ? '' : 'hidden' ?>>
    <div class="container">
        <div class="github-panel reveal" data-github-user="<?= e(setting('github_user')) ?>">
            <div class="github-panel__head">
                <div>
                    <p class="eyebrow eyebrow--light"><span></span>08 — <?= e(t('github_label')) ?></p>
                    <h2><?= e(t('github_title')) ?></h2>
                    <p><?= is_en() ? 'Recent repositories pulled live from GitHub — because learning in public is part of the process.' : 'Repo terbaru yang diambil langsung dari GitHub — karena belajar di publik adalah bagian dari proses.' ?></p>
                </div>
                <a href="<?= e(setting('github')) ?>" target="_blank" rel="noopener" class="btn btn--light btn--sm">@<?= e(setting('github_user')) ?> <?= icon('arrow-up-right', 15) ?></a>
            </div>

            <div class="github-panel__stats">
                <div><strong data-gh="repos">—</strong><span><?= is_en() ? 'Public repos' : 'Repo publik' ?></span></div>
                <div><strong data-gh="stars">—</strong><span>Stars</span></div>
                <div><strong data-gh="followers">—</strong><span>Followers</span></div>
                <div><strong data-gh="contrib">—</strong><span><?= is_en() ? 'Contributions (1y)' : 'Kontribusi (1th)' ?></span></div>
            </div>

            <div class="github-panel__body">
                <div class="github-heatmap">
                    <div class="github-heatmap__head">
                        <span><?= is_en() ? 'Contribution activity' : 'Aktivitas kontribusi' ?></span>
                        <span class="github-heatmap__legend"><?= is_en() ? 'Less' : 'Sedikit' ?> <i></i><i></i><i></i><i></i><i></i> <?= is_en() ? 'More' : 'Banyak' ?></span>
                    </div>
                    <div id="ghHeatmap" class="github-heatmap__grid" aria-live="polite">
                        <span class="github-loading"><?= is_en() ? 'Loading contribution graph…' : 'Memuat grafik kontribusi…' ?></span>
                    </div>
                </div>
                <div class="github-repos" id="ghRepos">
                    <span class="github-loading"><?= is_en() ? 'Loading latest repositories…' : 'Memuat repo terbaru…' ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--tint" id="contact">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <p class="eyebrow"><span></span>09 — <?= e(t('contact_label')) ?></p>
                <h2 class="section-title"><?= e(t('contact_title')) ?></h2>
                <p class="contact-info__note"><?= e(t('contact_note')) ?></p>

                <div class="contact-cards">
                    <a href="mailto:<?= e($email) ?>" class="contact-card">
                        <span class="contact-card__icon"><?= icon('mail', 19) ?></span>
                        <div><span><?= e(t('contact_email')) ?></span><strong><?= e($email) ?></strong></div>
                        <?= icon('arrow-up-right', 15) ?>
                    </a>
                    <?php if (setting('whatsapp')): ?>
                    <a href="<?= e(wa_link(setting('whatsapp'))) ?>" target="_blank" rel="noopener" class="contact-card">
                        <span class="contact-card__icon"><?= icon('whatsapp', 19) ?></span>
                        <div><span><?= e(t('contact_whatsapp')) ?></span><strong><?= e(setting('whatsapp')) ?></strong></div>
                        <?= icon('arrow-up-right', 15) ?>
                    </a>
                    <?php endif; ?>
                    <a href="<?= e(setting('linkedin')) ?>" target="_blank" rel="noopener" class="contact-card">
                        <span class="contact-card__icon"><?= icon('linkedin', 19) ?></span>
                        <div><span><?= e(t('contact_linkedin')) ?></span><strong><?= e(preg_replace('#^https?://#', '', setting('linkedin'))) ?></strong></div>
                        <?= icon('arrow-up-right', 15) ?>
                    </a>
                    <a href="<?= e(setting('github')) ?>" target="_blank" rel="noopener" class="contact-card">
                        <span class="contact-card__icon"><?= icon('github', 19) ?></span>
                        <div><span><?= e(t('contact_github')) ?></span><strong><?= e('@' . setting('github_user')) ?></strong></div>
                        <?= icon('arrow-up-right', 15) ?>
                    </a>
                </div>

                <div class="availability-card">
                    <span class="status-dot"></span>
                    <div>
                        <strong><?= is_en() ? 'Available for new opportunities' : 'Terbuka untuk peluang baru' ?></strong>
                        <p><?= is_en() ? 'Usually replies within 24 hours.' : 'Biasanya membalas dalam 24 jam.' ?></p>
                    </div>
                </div>
            </div>

            <div class="contact-form-wrap reveal">
                <form id="contactForm" class="contact-form" method="post" action="api.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="contact">
                    <div class="honeypot" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="form-row">
                        <div class="field">
                            <label for="cf-name"><?= e(t('form_name')) ?></label>
                            <input type="text" id="cf-name" name="name" placeholder="Nama kamu" required maxlength="120">
                        </div>
                        <div class="field">
                            <label for="cf-email"><?= e(t('form_email')) ?></label>
                            <input type="email" id="cf-email" name="email" placeholder="nama@email.com" required maxlength="180">
                        </div>
                    </div>
                    <div class="field">
                        <label for="cf-subject"><?= e(t('form_subject')) ?></label>
                        <input type="text" id="cf-subject" name="subject" placeholder="<?= is_en() ? 'What is this about?' : 'Ada yang bisa dibantu?' ?>" maxlength="200">
                    </div>
                    <div class="field">
                        <label for="cf-message"><?= e(t('form_message')) ?></label>
                        <textarea id="cf-message" name="message" rows="6" placeholder="<?= is_en() ? 'Tell me about your idea, role, or project…' : 'Ceritakan ide, posisi, atau proyekmu…' ?>" required maxlength="4000"></textarea>
                    </div>
                    <div class="form-foot">
                        <p class="form-note"><?= icon('lock', 13) ?> <?= is_en() ? 'Your message goes straight to my inbox.' : 'Pesanmu langsung masuk ke inbox saya.' ?></p>
                        <button type="submit" class="btn btn--primary"><?= icon('send', 16) ?> <span data-submit-label><?= e(t('form_send')) ?></span></button>
                    </div>
                    <p class="form-status" data-form-status role="status" aria-live="polite"></p>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="section section--guestbook" id="guestbook">
    <div class="container">
        <div class="guestbook-grid">
            <div class="guestbook-intro">
                <p class="eyebrow"><span></span>10 — <?= e(t('guestbook_label')) ?></p>
                <h2 class="section-title"><?= e(t('guestbook_title')) ?></h2>
                <p><?= is_en() ? 'A small corner for visitors. Say hi, leave a note, or tell me what you are building.' : 'Sudut kecil untuk pengunjung. Sapa saya, tinggalkan pesan, atau ceritakan apa yang sedang kamu bangun.' ?></p>
                <form id="guestbookForm" class="guestbook-form" method="post" action="api.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="guestbook">
                    <div class="honeypot" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="field">
                        <label for="gb-name"><?= e(t('guestbook_name')) ?></label>
                        <input type="text" id="gb-name" name="name" required maxlength="120" placeholder="<?= is_en() ? 'Your name' : 'Nama kamu' ?>">
                    </div>
                    <div class="field">
                        <label for="gb-message"><?= e(t('guestbook_message')) ?></label>
                        <textarea id="gb-message" name="message" rows="3" required maxlength="500" placeholder="<?= is_en() ? 'Say something nice…' : 'Tulis sesuatu yang baik…' ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn--outline"><?= icon('send', 15) ?> <?= e(t('guestbook_send')) ?></button>
                    <p class="form-status" data-form-status role="status" aria-live="polite"></p>
                </form>
            </div>

            <div class="guestbook-list" id="guestbookList">
                <?php if (!$guestbook): ?>
                <p class="guestbook-empty"><?= e(t('guestbook_empty')) ?></p>
                <?php endif; ?>
                <?php foreach ($guestbook as $entry): ?>
                <article class="guestbook-entry">
                    <span class="guestbook-entry__avatar"><?= e(avatar_initial($entry['name'])) ?></span>
                    <div>
                        <div class="guestbook-entry__head"><strong><?= e($entry['name']) ?></strong><span><?= e(format_date($entry['created_at'])) ?></span></div>
                        <p><?= e($entry['message']) ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
