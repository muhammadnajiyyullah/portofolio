<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_admin();

$tab = (($_GET['tab'] ?? '') === 'guestbook') ? 'guestbook' : 'inbox';
$self = $tab === 'guestbook' ? 'messages.php?tab=guestbook' : 'messages.php';

$adminTitle  = $tab === 'guestbook' ? 'Guestbook' : 'Inbox';
$adminDesc   = $tab === 'guestbook'
    ? 'Pesan singkat yang ditinggalkan pengunjung di halaman publik.'
    : 'Pesan yang masuk melalui form kontak portofolio.';
$adminActive = $tab === 'guestbook' ? 'guestbook' : 'messages';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle_read' && $id > 0) {
        db()->prepare('UPDATE messages SET is_read = IF(is_read = 1, 0, 1) WHERE id = ?')->execute([$id]);
        flash('Status pesan diperbarui.');
    } elseif ($action === 'mark_all_read') {
        db()->exec('UPDATE messages SET is_read = 1 WHERE is_read = 0');
        flash('Semua pesan ditandai sudah dibaca.');
    } elseif ($action === 'delete_message' && $id > 0) {
        db()->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
        flash('Pesan berhasil dihapus.');
    } elseif ($action === 'delete_guestbook' && $id > 0) {
        db()->prepare('DELETE FROM guestbook WHERE id = ?')->execute([$id]);
        flash('Entri guestbook berhasil dihapus.');
    }

    redirect($self);
}

$messages = all_rows('messages', '1=1', [], 'created_at DESC, id DESC');
$guestbook = all_rows('guestbook', '1=1', [], 'id DESC');
$unread = (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();

require __DIR__ . '/../inc/admin-header.php';
?>

<div class="inbox-tabs">
    <a href="messages.php" class="<?= $tab === 'inbox' ? 'is-active' : '' ?>">
        <?= icon('mail', 16) ?> Inbox
        <span><?= count($messages) ?></span>
        <?php if ($unread): ?><em><?= $unread ?> baru</em><?php endif; ?>
    </a>
    <a href="messages.php?tab=guestbook" class="<?= $tab === 'guestbook' ? 'is-active' : '' ?>">
        <?= icon('book', 16) ?> Guestbook
        <span><?= count($guestbook) ?></span>
    </a>
</div>

<?php if ($tab === 'inbox'): ?>

<div class="collection-head">
    <div class="collection-head__info">
        <h2><?= $unread ?> <span>belum dibaca</span></h2>
        <p>Dari total <?= count($messages) ?> pesan. Klik “Tandai dibaca” setelah selesai meninjau.</p>
    </div>
    <div class="collection-head__actions">
        <?php if ($unread): ?>
        <form method="post" action="messages.php" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn--outline btn--sm"><?= icon('check', 15) ?> Tandai semua dibaca</button>
        </form>
        <?php endif; ?>
        <a href="mailto:<?= e(setting('email')) ?>" class="btn btn--ghost btn--sm"><?= icon('send', 15) ?> Buka email</a>
    </div>
</div>

<?php if (!$messages): ?>
<section class="admin-card">
    <div class="empty-state empty-state--admin">
        <?= icon('mail', 30) ?>
        <h2>Inbox masih kosong.</h2>
        <p>Pesan dari form kontak akan muncul di sini. Coba kirim satu pesan dari halaman publik untuk menguji alurnya.</p>
        <a href="../index.php#contact" target="_blank" rel="noopener" class="btn btn--primary"><?= icon('external', 15) ?> Buka form kontak</a>
    </div>
</section>
<?php else: ?>
<div class="message-list">
    <?php foreach ($messages as $msg): ?>
    <article class="message-card <?= $msg['is_read'] ? '' : 'is-unread' ?>">
        <header class="message-card__head">
            <span class="message-card__avatar"><?= e(avatar_initial($msg['name'])) ?></span>
            <div class="message-card__who">
                <strong><?= e($msg['name']) ?><?php if (!$msg['is_read']): ?><i class="message-card__dot" title="Belum dibaca"></i><?php endif; ?></strong>
                <a href="mailto:<?= e($msg['email']) ?>"><?= e($msg['email']) ?></a>
            </div>
            <div class="message-card__meta">
                <time datetime="<?= e($msg['created_at']) ?>"><?= e(format_date($msg['created_at'])) ?> · <?= e(date('H:i', strtotime($msg['created_at']))) ?></time>
                <?php if ($msg['subject']): ?><span><?= e($msg['subject']) ?></span><?php endif; ?>
            </div>
        </header>
        <div class="message-card__body">
            <p><?= nl2br(e($msg['body'])) ?></p>
        </div>
        <footer class="message-card__actions">
            <form method="post" action="messages.php" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_read">
                <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
                <button type="submit" class="btn btn--ghost btn--sm"><?= icon($msg['is_read'] ? 'eye-off' : 'check', 14) ?> <?= $msg['is_read'] ? 'Tandai belum dibaca' : 'Tandai dibaca' ?></button>
            </form>
            <a href="mailto:<?= e($msg['email']) ?>?subject=<?= rawurlencode('Re: ' . ($msg['subject'] ?: 'Pesan dari portofolio')) ?>" class="btn btn--outline btn--sm"><?= icon('send', 14) ?> Balas</a>
            <form method="post" action="messages.php" class="inline-form" data-confirm="Hapus pesan dari <?= e($msg['name']) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_message">
                <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
                <button type="submit" class="btn btn--ghost btn--sm btn--danger"><?= icon('trash', 14) ?> Hapus</button>
            </form>
        </footer>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: ?>

<div class="collection-head">
    <div class="collection-head__info">
        <h2><?= count($guestbook) ?> <span>entri</span></h2>
        <p>Entri terbaru tampil paling atas di halaman publik. Hapus entri yang tidak relevan.</p>
    </div>
    <div class="collection-head__actions">
        <a href="../index.php#guestbook" target="_blank" rel="noopener" class="btn btn--outline btn--sm"><?= icon('external', 15) ?> Lihat guestbook</a>
    </div>
</div>

<?php if (!$guestbook): ?>
<section class="admin-card">
    <div class="empty-state empty-state--admin">
        <?= icon('book', 30) ?>
        <h2>Belum ada entri.</h2>
        <p>Guestbook adalah tempat pengunjung meninggalkan kesan. Entri baru akan tampil di sini.</p>
        <a href="../index.php#guestbook" target="_blank" rel="noopener" class="btn btn--primary"><?= icon('external', 15) ?> Buka halaman</a>
    </div>
</section>
<?php else: ?>
<div class="guestbook-admin">
    <?php foreach ($guestbook as $entry): ?>
    <article class="guestbook-admin__item">
        <span class="message-card__avatar"><?= e(avatar_initial($entry['name'])) ?></span>
        <div class="guestbook-admin__body">
            <div class="guestbook-admin__head">
                <strong><?= e($entry['name']) ?></strong>
                <time datetime="<?= e($entry['created_at']) ?>"><?= e(format_date($entry['created_at'])) ?> · <?= e(date('H:i', strtotime($entry['created_at']))) ?></time>
            </div>
            <p><?= nl2br(e($entry['message'])) ?></p>
        </div>
        <form method="post" action="messages.php?tab=guestbook" class="inline-form" data-confirm="Hapus entri dari <?= e($entry['name']) ?>?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_guestbook">
            <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
            <button type="submit" class="row-action row-action--danger" title="Hapus"><?= icon('trash', 15) ?></button>
        </form>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/../inc/admin-footer.php'; ?>
