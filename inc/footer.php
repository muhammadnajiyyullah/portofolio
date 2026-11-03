<?php
$ownerName  = setting('owner_name', 'Raka Pratama');
$headline   = tr(['headline' => setting('headline_id'), 'headline_en' => setting('headline_en')], 'headline');
$email      = setting('email');
$cvLink     = setting('cv_file') !== '' ? image_url(setting('cv_file')) : 'cv.php';

$footerProjects = all_rows('projects', "status = 'published'", [], 'sort_order ASC, id ASC');
$footerSkills   = all_rows('skills', '1=1', [], 'sort_order ASC, id ASC');
$recruiterProjects = array_slice($footerProjects, 0, 3);

$skillGroups = [];
foreach ($footerSkills as $s) {
    $skillGroups[$s['category']][] = $s['name'];
}
$recruiterSkills = [];
foreach ($skillGroups as $group => $items) {
    $recruiterSkills[$group] = array_slice($items, 0, 6);
}
$skillGroups = null;

$paletteI18n = [
    'pages'      => t('palette_pages'),
    'projects'   => t('palette_projects'),
    'commands'   => t('palette_commands'),
    'navigate'   => t('palette_navigate'),
    'open'       => t('palette_open'),
    'close'      => t('palette_close'),
    'welcome'    => t('terminal_welcome'),
    'noResults'  => is_en() ? 'No results. Try “help”.' : 'Tidak ada hasil. Coba “help”.',
    'copied'     => t('recruiter_copied'),
    'sent'       => t('form_success'),
    'failed'     => t('form_error'),
    'guestbook'  => t('guestbook_success'),
];
$paletteProjects = array_map(fn ($p) => [
    'title'    => $p['title'],
    'slug'     => $p['slug'],
    'category' => category_label($p['category']),
], $footerProjects);
$palettePages = [
    ['label' => t('nav_home'), 'href' => 'index.php'],
    ['label' => t('nav_about'), 'href' => 'index.php#about'],
    ['label' => t('nav_projects'), 'href' => 'index.php#projects'],
    ['label' => t('nav_skills'), 'href' => 'index.php#skills'],
    ['label' => t('nav_journey'), 'href' => 'index.php#journey'],
    ['label' => t('nav_blog'), 'href' => 'blog.php'],
    ['label' => t('nav_contact'), 'href' => 'index.php#contact'],
    ['label' => t('nav_guestbook'), 'href' => 'index.php#guestbook'],
    ['label' => t('recruiter_mode'), 'action' => 'recruiter'],
    ['label' => t('resume_builder'), 'href' => 'cv.php'],
];
?>
</main>

<section class="contact-cta" aria-labelledby="ctaTitle">
    <div class="container">
        <div class="cta-panel reveal">
            <div class="cta-panel__glow" aria-hidden="true"></div>
            <p class="eyebrow eyebrow--light"><span></span><?= is_en() ? 'Next chapter' : 'Bab selanjutnya' ?></p>
            <h2 id="ctaTitle"><?= is_en() ? 'Let’s build something worth remembering.' : 'Mari bangun sesuatu yang layak dikenang.' ?></h2>
            <p><?= is_en() ? 'I’m open to full-time roles, internships, and meaningful collaborations.' : 'Saya terbuka untuk posisi penuh waktu, magang, dan kolaborasi yang bermakna.' ?></p>
            <div class="cta-panel__actions">
                <a href="index.php#contact" class="btn btn--light"><?= icon('send', 17) ?> <?= e(t('contact_label')) ?></a>
                <a href="mailto:<?= e($email) ?>" class="btn btn--ghost-light"><?= icon('mail', 17) ?> <?= e($email) ?></a>
            </div>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container">
        <div class="footer__grid">
            <div class="footer__brand">
                <a href="index.php" class="brand">
                    <span class="brand__mark"><?= e(setting('owner_initial', 'RP')) ?></span>
                    <span class="brand__text"><?= e(setting('site_name', 'Dev Journal')) ?><span class="brand__dot">.</span></span>
                </a>
                <p><?= e(t('footer_tagline')) ?></p>
                <div class="footer__socials">
                    <a href="<?= e(setting('github')) ?>" target="_blank" rel="noopener" aria-label="GitHub"><?= icon('github', 18) ?></a>
                    <a href="<?= e(setting('linkedin')) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><?= icon('linkedin', 18) ?></a>
                    <a href="mailto:<?= e($email) ?>" aria-label="Email"><?= icon('mail', 18) ?></a>
                    <?php if (setting('instagram')): ?>
                    <a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram', 18) ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer__col">
                <h3><?= e(t('footer_quick')) ?></h3>
                <a href="index.php#about"><?= e(t('nav_about')) ?></a>
                <a href="index.php#projects"><?= e(t('nav_projects')) ?></a>
                <a href="index.php#journey"><?= e(t('nav_journey')) ?></a>
                <a href="blog.php"><?= e(t('nav_blog')) ?></a>
                <a href="index.php#contact"><?= e(t('nav_contact')) ?></a>
            </div>

            <div class="footer__col">
                <h3><?= e(t('footer_social')) ?></h3>
                <a href="<?= e(setting('github')) ?>" target="_blank" rel="noopener">GitHub <?= icon('arrow-up-right', 13) ?></a>
                <a href="<?= e(setting('linkedin')) ?>" target="_blank" rel="noopener">LinkedIn <?= icon('arrow-up-right', 13) ?></a>
                <?php if (setting('whatsapp')): ?>
                <a href="<?= e(wa_link(setting('whatsapp'))) ?>" target="_blank" rel="noopener">WhatsApp <?= icon('arrow-up-right', 13) ?></a>
                <?php endif; ?>
                <a href="cv.php"><?= e(t('resume_builder')) ?> <?= icon('arrow-up-right', 13) ?></a>
            </div>

            <div class="footer__col footer__col--status">
                <h3><?= e(t('currently')) ?></h3>
                <div class="footer__status"><span class="status-dot"></span><span><?= e(setting('status_building')) ?></span></div>
                <div class="footer__status"><span class="status-dot status-dot--muted"></span><span><?= e(setting('status_learning')) ?></span></div>
                <div class="footer__meta">
                    <span><?= icon('eye', 14) ?> <?= number_format(total_visits(), 0, ',', '.') ?> <?= e(t('visitor')) ?></span>
                    <span><?= icon('map-pin', 14) ?> <?= e(setting('location')) ?></span>
                </div>
            </div>
        </div>

        <div class="footer__bottom">
            <p>© <?= date('Y') ?> <?= e($ownerName) ?>. <?= e(t('footer_rights')) ?></p>
            <p class="footer__built"><?= e(t('footer_built')) ?></p>
            <div class="footer__bottom-links">
                <a href="cv.php"><?= e(t('resume_builder')) ?></a>
                <a href="admin/login.php"><?= e(t('admin')) ?></a>
                <button type="button" class="link-btn" data-open-terminal><?= icon('terminal', 13) ?> Terminal</button>
            </div>
        </div>
    </div>
</footer>

<button type="button" class="back-top" id="backTop" aria-label="Kembali ke atas"><?= icon('arrow-up', 18) ?></button>

<div class="recruiter-modal" id="recruiterModal" aria-hidden="true">
    <div class="modal-backdrop" data-close-recruiter></div>
    <div class="recruiter-panel" role="dialog" aria-modal="true" aria-labelledby="recruiterTitle">
        <div class="recruiter-panel__head">
            <div>
                <p class="eyebrow"><span></span><?= e(t('recruiter_mode')) ?></p>
                <h2 id="recruiterTitle"><?= e(t('recruiter_title')) ?></h2>
                <p class="recruiter-panel__sub"><?= e(t('recruiter_sub')) ?></p>
            </div>
            <button type="button" class="icon-btn" data-close-recruiter aria-label="<?= e(t('close')) ?>"><?= icon('close', 18) ?></button>
        </div>

        <div class="recruiter-panel__identity">
            <img src="<?= e(image_url(setting('avatar'))) ?>" alt="<?= e($ownerName) ?>" width="72" height="72">
            <div>
                <h3><?= e($ownerName) ?></h3>
                <p><?= e($headline) ?></p>
                <span class="status-pill"><span class="status-dot"></span><?= e(t('open_to_work')) ?></span>
            </div>
        </div>

        <div class="recruiter-panel__body">
            <section>
                <h4><?= e(t('recruiter_skills')) ?></h4>
                <div class="recruiter-skills">
                    <?php foreach ($recruiterSkills as $group => $items): ?>
                    <div>
                        <span><?= e($group) ?></span>
                        <p><?= e(implode(' · ', $items)) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <section>
                <h4><?= e(t('recruiter_projects')) ?></h4>
                <div class="recruiter-projects">
                    <?php foreach ($recruiterProjects as $rp): ?>
                    <a href="project.php?slug=<?= e($rp['slug']) ?>">
                        <span><?= e($rp['title']) ?></span>
                        <small><?= e(category_label($rp['category'])) ?> · <?= e(implode(', ', array_slice(tech_array($rp['tech_stack']), 0, 3))) ?></small>
                        <?= icon('arrow-up-right', 15) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <section>
                <h4><?= e(t('recruiter_contact')) ?></h4>
                <div class="recruiter-contact">
                    <a href="mailto:<?= e($email) ?>"><?= icon('mail', 16) ?><?= e($email) ?></a>
                    <?php if (setting('whatsapp')): ?><a href="<?= e(wa_link(setting('whatsapp'))) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 16) ?><?= e(setting('whatsapp')) ?></a><?php endif; ?>
                    <a href="<?= e(setting('linkedin')) ?>" target="_blank" rel="noopener"><?= icon('linkedin', 16) ?><?= is_en() ? 'LinkedIn profile' : 'Profil LinkedIn' ?></a>
                </div>
            </section>
        </div>

        <div class="recruiter-panel__foot">
            <button type="button" class="btn btn--outline" data-copy-email="<?= e($email) ?>"><?= icon('copy', 16) ?> <?= e(t('recruiter_copy')) ?></button>
            <a href="<?= e($cvLink) ?>" class="btn btn--primary" <?= setting('cv_file') !== '' ? 'download' : 'target="_blank"' ?>><?= icon('download', 16) ?> <?= e(t('recruiter_print')) ?></a>
        </div>
    </div>
</div>

<div class="palette" id="palette" aria-hidden="true">
    <div class="modal-backdrop" data-close-palette></div>
    <div class="palette__panel" role="dialog" aria-modal="true" aria-label="<?= e(t('palette_title')) ?>">
        <div class="palette__bar">
            <span class="palette__prompt" id="palettePrompt"><?= icon('search', 18) ?></span>
            <input type="text" id="paletteInput" placeholder="<?= e(t('palette_placeholder')) ?>" autocomplete="off" spellcheck="false" aria-label="<?= e(t('palette_placeholder')) ?>">
            <button type="button" class="palette__mode" id="paletteMode" title="<?= e(t('terminal_mode')) ?>"><?= icon('terminal', 15) ?> <span>terminal</span></button>
            <kbd>esc</kbd>
        </div>
        <div class="palette__results" id="paletteResults"></div>
        <div class="palette__terminal" id="paletteTerminal" hidden>
            <div class="terminal-log" id="terminalLog"></div>
        </div>
        <div class="palette__foot">
            <span><kbd>↑</kbd><kbd>↓</kbd> <?= e(t('palette_navigate')) ?></span>
            <span><kbd>↵</kbd> <?= e(t('palette_open')) ?></span>
            <span><kbd>esc</kbd> <?= e(t('palette_close')) ?></span>
            <span class="palette__foot-brand"><span class="status-dot"></span><?= e(setting('site_name', 'Dev Journal')) ?></span>
        </div>
    </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<canvas class="confetti" id="confetti" aria-hidden="true"></canvas>

<script nonce="<?= e(csp_nonce()) ?>">
window.DJ = <?= json_for_script([
    'lang'     => current_lang(),
    'base'     => '',
    'csrf'     => csrf_token(),
    'email'    => $email,
    'cv'       => $cvLink,
    'projects' => $paletteProjects,
    'pages'    => $palettePages,
    'i18n'     => $paletteI18n,
    'github'   => setting('github_user'),
    'githubShow' => bool_setting('github_show', true) ? 1 : 0,
    'customCursor' => bool_setting('custom_cursor', true) ? 1 : 0,
]) ?>;
</script>
<script src="<?= e(asset_url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
