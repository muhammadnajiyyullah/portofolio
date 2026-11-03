<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/entities.php';
require_admin();
$adminUser = admin_user();

$adminTitle = 'Dashboard';
$adminDesc = 'Ringkasan konten dan aktivitas website.';
$adminActive = 'dashboard';

/* Statistik */
$stats = [
    'projects'      => (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'published'     => (int) db()->query("SELECT COUNT(*) FROM projects WHERE status = 'published'")->fetchColumn(),
    'drafts'        => (int) db()->query("SELECT COUNT(*) FROM projects WHERE status = 'draft'")->fetchColumn(),
    'posts'         => (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'skills'        => (int) db()->query('SELECT COUNT(*) FROM skills')->fetchColumn(),
    'certs'         => (int) db()->query('SELECT COUNT(*) FROM certificates')->fetchColumn(),
    'unread'        => (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn(),
    'messages'      => (int) db()->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
    'guestbook'     => (int) db()->query('SELECT COUNT(*) FROM guestbook')->fetchColumn(),
    'visits'        => total_visits(),
    'project_views' => (int) db()->query('SELECT COALESCE(SUM(views),0) FROM projects')->fetchColumn(),
    'reactions'     => (int) db()->query('SELECT COALESCE(SUM(count),0) FROM reactions')->fetchColumn(),
];

$chartRows = db()->query('SELECT day, views FROM visits WHERE day >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) ORDER BY day ASC')->fetchAll();
$chartMap = [];
foreach ($chartRows as $r) {
    $chartMap[$r['day']] = (int) $r['views'];
}
$chart = [];
$today = new DateTimeImmutable('today');
for ($i = 13; $i >= 0; $i--) {
    $d = $today->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
    $chart[] = ['day' => $d, 'views' => $chartMap[$d] ?? 0];
}
$maxViews = max(1, max(array_column($chart, 'views')));
$total14 = array_sum(array_column($chart, 'views'));

$recentMessages = db()->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 4')->fetchAll();
$topProjects = db()->query('SELECT * FROM projects ORDER BY views DESC LIMIT 4')->fetchAll();
$recentPosts = db()->query('SELECT * FROM posts ORDER BY id DESC LIMIT 3')->fetchAll();

$me = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
$me->execute([$adminUser['id'] ?? 0]);
$currentHash = (string) $me->fetchColumn();
$usingDefaultPassword = $currentHash && password_verify('admin123', $currentHash);

require __DIR__ . '/../inc/admin-header.php';
?>

<?php if ($usingDefaultPassword): ?>
<div class="dash-alert">
    <span class="dash-alert__icon"><?= icon('shield', 20) ?></span>
    <div>
        <strong>Password masih default</strong>
        <p>Akun ini masih memakai password <code>admin123</code>. Segera ganti agar dashboard tetap aman.</p>
    </div>
    <a href="profile.php#security" class="btn btn--sm btn--dark">Ganti password</a>
</div>
<?php endif; ?>

<div class="dash-hero">
    <div>
        <p class="dash-hero__eyebrow"><?= strtoupper(format_date(date('Y-m-d'))) ?></p>
        <h2>Halo, <?= e(explode(' ', $adminUser['name'] ?? 'Admin')[0]) ?> 👋</h2>
        <p>Semua yang tampil di portofolio bisa diatur dari sini. Mulai dari profil, proyek, sampai pesan masuk.</p>
    </div>
    <div class="dash-hero__actions">
        <a href="collection.php?type=projects&action=new" class="btn btn--primary"><?= icon('plus', 16) ?> Proyek baru</a>
        <a href="collection.php?type=posts&action=new" class="btn btn--outline"><?= icon('edit', 15) ?> Tulis catatan</a>
    </div>
</div>

<div class="stat-grid">
    <article class="stat-card stat-card--accent">
        <div class="stat-card__top"><span class="stat-card__icon"><?= icon('folder', 18) ?></span><span class="stat-card__tag"><?= $stats['published'] ?> publish</span></div>
        <strong><?= $stats['projects'] ?></strong>
        <span>Total proyek</span>
        <small><?= $stats['drafts'] ?> masih draft</small>
    </article>
    <article class="stat-card">
        <div class="stat-card__top"><span class="stat-card__icon"><?= icon('eye', 18) ?></span><span class="stat-card__tag">+<?= $total14 ?> / 14 hari</span></div>
        <strong><?= number_format($stats['visits'], 0, ',', '.') ?></strong>
        <span>Total kunjungan</span>
        <small><?= number_format($stats['project_views'], 0, ',', '.') ?> views proyek</small>
    </article>
    <article class="stat-card">
        <div class="stat-card__top"><span class="stat-card__icon"><?= icon('mail', 18) ?></span><?php if ($stats['unread']): ?><span class="stat-card__dot"></span><?php endif; ?></div>
        <strong><?= $stats['unread'] ?></strong>
        <span>Pesan belum dibaca</span>
        <small>dari <?= $stats['messages'] ?> pesan masuk</small>
    </article>
    <article class="stat-card">
        <div class="stat-card__top"><span class="stat-card__icon"><?= icon('heart', 18) ?></span></div>
        <strong><?= number_format($stats['reactions'] + $stats['guestbook'], 0, ',', '.') ?></strong>
        <span>Interaksi pengunjung</span>
        <small><?= $stats['reactions'] ?> reaksi · <?= $stats['guestbook'] ?> guestbook</small>
    </article>
</div>

<div class="dash-grid">
    <section class="admin-card dash-chart">
        <div class="admin-card__head">
            <div><h3>Kunjungan 14 hari terakhir</h3><p><?= $total14 ?> kunjungan · rata-rata <?= round($total14 / 14, 1) ?>/hari</p></div>
            <span class="admin-card__badge"><?= icon('trending', 14) ?> Live</span>
        </div>
        <div class="chart">
            <?php foreach ($chart as $point):
                $height = max(4, (int) round(($point['views'] / $maxViews) * 100));
                $isToday = $point['day'] === date('Y-m-d');
                ?>
            <div class="chart__col" title="<?= e(format_date($point['day'])) ?>: <?= $point['views'] ?> kunjungan">
                <span class="chart__value"><?= $point['views'] ?></span>
                <span class="chart__bar <?= $isToday ? 'is-today' : '' ?>" style="--h: <?= $height ?>%"></span>
                <span class="chart__label"><?= date('d/m', strtotime($point['day'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-card dash-inbox">
        <div class="admin-card__head">
            <div><h3>Pesan terbaru</h3><p>Inbox form kontak</p></div>
            <a href="messages.php" class="admin-card__link">Lihat semua <?= icon('arrow-right', 14) ?></a>
        </div>
        <div class="mini-inbox">
            <?php if (!$recentMessages): ?>
            <p class="empty-note">Belum ada pesan masuk.</p>
            <?php endif; ?>
            <?php foreach ($recentMessages as $msg): ?>
            <a href="messages.php" class="mini-inbox__item <?= $msg['is_read'] ? '' : 'is-unread' ?>">
                <span class="mini-inbox__avatar"><?= e(avatar_initial($msg['name'])) ?></span>
                <div><strong><?= e($msg['name']) ?></strong><span><?= e(excerpt_text($msg['subject'] ?: $msg['body'], 42)) ?></span></div>
                <time><?= e(format_date($msg['created_at'], false)) ?></time>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="dash-grid dash-grid--even">
    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Proyek paling dilihat</h3><p>Berdasarkan total views</p></div>
            <a href="collection.php?type=projects" class="admin-card__link">Kelola <?= icon('arrow-right', 14) ?></a>
        </div>
        <div class="rank-list">
            <?php foreach ($topProjects as $i => $p): ?>
            <div class="rank-item">
                <span class="rank-item__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <img src="../<?= e(image_url($p['thumbnail'])) ?>" alt="" width="44" height="34">
                <div><strong><?= e($p['title']) ?></strong><span><?= e(category_label($p['category'])) ?> · <?= e($p['status']) ?></span></div>
                <span class="rank-item__views"><?= number_format((int) $p['views'], 0, ',', '.') ?> <small>views</small></span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div><h3>Catatan terbaru</h3><p>Blog & jurnal belajar</p></div>
            <a href="collection.php?type=posts" class="admin-card__link">Kelola <?= icon('arrow-right', 14) ?></a>
        </div>
        <div class="rank-list">
            <?php foreach ($recentPosts as $post): ?>
            <div class="rank-item">
                <span class="rank-item__num"><?= icon('file', 15) ?></span>
                <img src="../<?= e(image_url($post['cover'])) ?>" alt="" width="44" height="34">
                <div><strong><?= e($post['title']) ?></strong><span><?= e(format_date($post['published_at'])) ?> · <?= e($post['status']) ?></span></div>
                <a href="collection.php?type=posts&action=edit&id=<?= (int) $post['id'] ?>" class="rank-item__edit"><?= icon('edit', 15) ?></a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="dash-shortcuts">
    <a href="profile.php"><?= icon('user', 18) ?><span><strong>Profil & identitas</strong><small>Nama, bio, foto, CV, sosial media</small></span><?= icon('arrow-right', 15) ?></a>
    <a href="appearance.php"><?= icon('palette', 18) ?><span><strong>Tema & tampilan</strong><small>Warna, font, dan fitur interaktif</small></span><?= icon('arrow-right', 15) ?></a>
    <a href="../cv.php" target="_blank" rel="noopener"><?= icon('file', 18) ?><span><strong>Resume Builder</strong><small>CV otomatis dari data terbaru</small></span><?= icon('external', 15) ?></a>
</div>

<?php require __DIR__ . '/../inc/admin-footer.php'; ?>
