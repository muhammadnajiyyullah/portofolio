<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/entities.php';

require_admin();

/* ------------------------------------------------------------------
 |  Resolusi entitas
 |----------------------------------------------------------------- */

$type = (string) ($_GET['type'] ?? 'projects');
$entity = entity($type);

if (!$entity) {
    flash('Jenis konten tidak dikenal.', 'error');
    redirect('index.php');
}

$table  = $entity['table'];
$fields = $entity['fields'];
$action = (string) ($_GET['action'] ?? 'list');
$id     = (int) ($_GET['id'] ?? 0);
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$postAction = $isPost ? (string) ($_POST['action'] ?? '') : '';
if ($isPost && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
}

$adminTitle  = $entity['label'];
$adminDesc   = $entity['desc'];
$adminActive = $type;
$listUrl     = 'collection.php?type=' . urlencode($type);

/* ------------------------------------------------------------------
 |  Helper lokal
 |----------------------------------------------------------------- */

function collection_preview_url(string $type, array $row): ?string
{
    if ($type === 'projects' && !empty($row['slug'])) {
        return '../project.php?slug=' . urlencode((string) $row['slug']) . '&preview=1';
    }
    if ($type === 'posts' && !empty($row['slug'])) {
        return '../post.php?slug=' . urlencode((string) $row['slug']) . '&preview=1';
    }
    return null;
}

function collection_file_paths(array $entity, array $row): array
{
    $paths = [];
    $thumb = $entity['thumb'] ?? null;
    if ($thumb && isset($row[$thumb]) && $row[$thumb] !== '') {
        $paths[] = (string) $row[$thumb];
    }
    foreach ($entity['fields'] as $name => $field) {
        if (($field['type'] ?? '') === 'image' && $name !== $thumb && !empty($row[$name])) {
            $paths[] = (string) $row[$name];
        }
        if (($field['type'] ?? '') === 'images' && !empty($row[$name])) {
            foreach (preg_split('/\r?\n/', (string) $row[$name]) ?: [] as $line) {
                if (trim($line) !== '') {
                    $paths[] = trim($line);
                }
            }
        }
    }
    return array_values(array_unique($paths));
}

function collection_row(int $id, string $table): ?array
{
    $st = db()->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function collection_next_order(string $table): int
{
    try {
        return (int) db()->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}")->fetchColumn();
    } catch (Throwable $e) {
        return 1;
    }
}

/* ------------------------------------------------------------------
 |  Drag & drop reorder (JSON)
 |----------------------------------------------------------------- */

if ($isPost && ($postAction === 'reorder' || $action === 'reorder')) {
    verify_csrf();

    $payload = json_decode((string) file_get_contents('php://input'), true);
    $ids = array_values(array_filter(array_map('intval', (array) ($payload['ids'] ?? []))));

    header('Content-Type: application/json; charset=utf-8');

    if (!$ids) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Tidak ada item yang bisa diurutkan.']);
        exit;
    }

    try {
        $st = db()->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
        foreach ($ids as $index => $rowId) {
            $st->execute([$index + 1, $rowId]);
        }
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Urutan gagal disimpan.']);
    }
    exit;
}

/* ------------------------------------------------------------------
 |  Hapus item
 |----------------------------------------------------------------- */

if ($isPost && $postAction === 'delete') {
    verify_csrf();

    $row = collection_row($id, $table);
    if ($row) {
        foreach (collection_file_paths($entity, $row) as $path) {
            delete_upload($path);
        }
        db()->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);

        if ($type === 'projects') {
            db()->prepare('DELETE FROM reactions WHERE project_id = ?')->execute([$id]);
        }

        flash(($row[$entity['title']] ?? 'Item') . ' berhasil dihapus.');
    } else {
        flash('Item tidak ditemukan.', 'error');
    }

    redirect($listUrl);
}

/* ------------------------------------------------------------------
 |  Simpan item
 |----------------------------------------------------------------- */

$errors = [];
$values = [];
$existing = $id > 0 ? collection_row($id, $table) : null;
$isSave = $isPost && $postAction === 'save';

if ($isSave) {
    if ($id > 0 && !$existing) {
        flash('Item yang ingin diedit tidak ditemukan.', 'error');
        redirect($listUrl);
    }

    /* Nilai teks dasar */
    foreach ($fields as $name => $field) {
        $fieldType = $field['type'] ?? 'text';
        $raw = $_POST[$name] ?? '';

        switch ($fieldType) {
            case 'checkbox':
                $values[$name] = isset($_POST[$name]) ? 1 : 0;
                break;
            case 'number':
                $values[$name] = (int) $raw;
                break;
            case 'richtext':
                $values[$name] = clean_html((string) $raw);
                break;
            case 'date':
                $raw = trim((string) $raw);
                $values[$name] = $raw !== '' ? $raw : null;
                break;
            case 'tags':
                $values[$name] = implode(', ', tech_array((string) $raw));
                break;
            default:
                $values[$name] = is_array($raw) ? '' : trim((string) $raw);
                break;
        }
    }

    /* Slug otomatis + unik */
    if (isset($fields['slug'])) {
        $from = (string) ($fields['slug']['from'] ?? 'title');
        $slug = (string) ($values['slug'] ?? '');
        if ($slug === '') {
            $slug = (string) ($values[$from] ?? '');
        }
        $values['slug'] = unique_slug($table, slugify($slug), $id);
    }

    /* Validasi field wajib */
    foreach ($fields as $name => $field) {
        if (empty($field['required'])) {
            continue;
        }
        $value = $values[$name] ?? '';
        $check = is_string($value) ? trim(strip_tags($value)) : (string) $value;
        if ($check === '') {
            $errors[$name] = ($field['label'] ?? $name) . ' wajib diisi.';
        }
    }

    if (isset($fields['slug']) && $values['slug'] === 'item') {
        $errors['slug'] = 'Slug tidak valid. Gunakan huruf, angka, atau tanda hubung.';
    }

    /* Upload & hapus media */
    if (!$errors) {
        $cleanup = [];

        foreach ($fields as $name => $field) {
            $fieldType = $field['type'] ?? 'text';
            $current = (string) ($values[$name] ?? '');
            $old = (string) ($existing[$name] ?? '');

            if ($fieldType === 'image') {
                $current = $old;
                if (!empty($_POST['remove_' . $name])) {
                    $cleanup[] = $old;
                    $current = '';
                }
                try {
                    $uploaded = upload_file($name, 'image');
                    if ($uploaded !== null) {
                        if ($old !== '') {
                            $cleanup[] = $old;
                        }
                        $current = $uploaded;
                    }
                } catch (Throwable $e) {
                    $errors[$name] = $e->getMessage();
                }
                $values[$name] = $current;
            }

            if ($fieldType === 'images') {
                $paths = [];
                foreach (preg_split('/\r?\n/', $current) ?: [] as $line) {
                    if (trim($line) !== '') {
                        $paths[] = trim($line);
                    }
                }

                $removed = $_POST['remove_' . $name] ?? [];
                if (is_array($removed)) {
                    foreach ($removed as $removedPath) {
                        $removedPath = trim((string) $removedPath);
                        $paths = array_values(array_filter($paths, fn ($p) => $p !== $removedPath));
                        if ($removedPath !== '') {
                            $cleanup[] = $removedPath;
                        }
                    }
                }

                try {
                    foreach (upload_files($name . '_upload', 'image') as $uploaded) {
                        $paths[] = $uploaded;
                    }
                } catch (Throwable $e) {
                    $errors[$name] = $e->getMessage();
                }

                $values[$name] = implode("\n", array_values(array_unique($paths)));
            }
        }

        if (!$errors) {
            if (isset($fields['status']) && $values['status'] === 'published'
                && isset($fields['published_at']) && empty($values['published_at'])) {
                $values['published_at'] = date('Y-m-d');
            }

            if (isset($fields['sort_order']) && ($values['sort_order'] ?? 0) <= 0) {
                $values['sort_order'] = $id > 0
                    ? (int) ($existing['sort_order'] ?? 1)
                    : collection_next_order($table);
            }

            $columns = array_keys($fields);
            try {
                if ($id > 0) {
                    $set = implode(', ', array_map(fn ($c) => "`{$c}` = ?", $columns));
                    $params = [];
                    foreach ($columns as $column) {
                        $params[] = $values[$column] ?? null;
                    }
                    $params[] = $id;
                    db()->prepare("UPDATE {$table} SET {$set} WHERE id = ?")->execute($params);
                    flash(($values[$entity['title']] ?? 'Item') . ' berhasil diperbarui.');
                } else {
                    $place = implode(', ', array_fill(0, count($columns), '?'));
                    $params = [];
                    foreach ($columns as $column) {
                        $params[] = $values[$column] ?? null;
                    }
                    db()->prepare("INSERT INTO {$table} (" . implode(', ', array_map(fn ($c) => "`{$c}`", $columns)) . ") VALUES ({$place})")->execute($params);
                    flash(($values[$entity['title']] ?? 'Item') . ' berhasil ditambahkan.');
                }

                foreach (array_filter($cleanup) as $path) {
                    delete_upload($path);
                }

                redirect($listUrl);
            } catch (Throwable $e) {
                $errors['_form'] = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
} else {
    /* Nilai awal saat membuka form */
    if ($existing) {
        $values = $existing;
    } else {
        foreach ($fields as $name => $field) {
            $fieldType = $field['type'] ?? 'text';
            if ($fieldType === 'checkbox') {
                $values[$name] = 0;
            } elseif ($name === 'published_at') {
                $values[$name] = date('Y-m-d');
            } elseif ($name === 'sort_order') {
                $values[$name] = collection_next_order($table);
            } else {
                $values[$name] = $field['default'] ?? '';
            }
        }
    }
}

/* ------------------------------------------------------------------
 |  Daftar item
 |----------------------------------------------------------------- */

$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
$hasStatus = isset($fields['status']);
$where = [];
$params = [];

if ($q !== '') {
    $likes = [];
    foreach (($entity['search'] ?? []) as $column) {
        $likes[] = "`{$column}` LIKE ?";
        $params[] = '%' . $q . '%';
    }
    if ($likes) {
        $where[] = '(' . implode(' OR ', $likes) . ')';
    }
}

if ($hasStatus && $statusFilter !== '' && isset($fields['status']['options'][$statusFilter])) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

$whereSql = $where ? implode(' AND ', $where) : '1=1';
$rows = all_rows($table, $whereSql, $params, $entity['order']);
$totalCount = (int) db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
$isFiltered = $q !== '' || $statusFilter !== '';

/* ------------------------------------------------------------------
 |  Render
 |----------------------------------------------------------------- */

$isForm = $action === 'new' || $action === 'edit';
$editing = $id > 0;
$formValues = array_merge($existing ?? [], $values);
$formTitle = $editing ? 'Edit ' . strtolower($entity['label']) : 'Tambah ' . strtolower($entity['label']);

if ($isForm) {
    if ($editing && !$existing) {
        flash('Item tidak ditemukan.', 'error');
        redirect($listUrl);
    }
    $adminTitle = $formTitle;
    $adminDesc = $editing ? 'Perbarui konten lalu simpan.' : 'Isi detail konten baru.';
}

require __DIR__ . '/../inc/admin-header.php';
?>

<?php if ($isForm): ?>

<div class="collection-bar">
    <a href="<?= e($listUrl) ?>" class="back-link"><?= icon('arrow-left', 15) ?> Kembali ke <?= e(strtolower($entity['label'])) ?></a>
    <?php if ($editing): ?>
    <?php $previewUrl = collection_preview_url($type, $formValues); ?>
    <div class="collection-bar__actions">
        <?php if ($previewUrl): ?>
        <a href="<?= e($previewUrl) ?>" target="_blank" rel="noopener" class="btn btn--outline btn--sm"><?= icon('eye', 15) ?> Preview</a>
        <?php endif; ?>
        <span class="collection-bar__id">ID #<?= (int) $formValues['id'] ?></span>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($errors['_form'])): ?>
<div class="admin-flash admin-flash--error"><?= icon('close', 16) ?><span><?= e($errors['_form']) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= e($listUrl) ?>&action=save" enctype="multipart/form-data" class="collection-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($formValues['id'] ?? 0) ?>">

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h3><?= $editing ? 'Detail konten' : 'Konten baru' ?></h3>
                <p>Kolom bertanda <span class="req">*</span> wajib diisi.</p>
            </div>
            <?php if ($hasStatus && isset($formValues['status'])): ?>
            <span class="admin-card__badge"><?= status_badge((string) $formValues['status']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-grid">
            <?php foreach ($fields as $name => $field):
                $fieldType = $field['type'] ?? 'text';
                $value = $formValues[$name] ?? '';
                $isFull = !empty($field['full']) || in_array($fieldType, ['richtext', 'images', 'textarea'], true);
                $error = $errors[$name] ?? '';
                $fieldId = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
                ?>
            <div class="field <?= $isFull ? 'field--full' : '' ?> <?= $error ? 'has-error' : '' ?>">
                <?php if ($fieldType === 'checkbox'): ?>
                <label class="switch-row switch-row--single">
                    <span class="switch">
                        <input type="checkbox" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" value="1" <?= !empty($value) ? 'checked' : '' ?>>
                        <span class="switch__slider"></span>
                    </span>
                    <span>
                        <strong><?= e($field['label'] ?? $name) ?></strong>
                        <?php if (!empty($field['help'])): ?><small><?= e($field['help']) ?></small><?php endif; ?>
                    </span>
                </label>
                <?php else: ?>
                <label for="<?= e($fieldId) ?>">
                    <?= e($field['label'] ?? $name) ?>
                    <?php if (!empty($field['required'])): ?><span class="req">*</span><?php endif; ?>
                </label>

                <?php if ($fieldType === 'textarea'): ?>
                <textarea id="<?= e($fieldId) ?>" name="<?= e($name) ?>" rows="<?= (int) ($field['rows'] ?? 3) ?>" placeholder="<?= e($field['placeholder'] ?? '') ?>"><?= e((string) $value) ?></textarea>

                <?php elseif ($fieldType === 'richtext'): ?>
                <div class="rte" data-rte>
                    <div class="rte__toolbar" role="toolbar" aria-label="Format teks">
                        <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
                        <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
                        <button type="button" data-cmd="formatBlock" data-value="h2" title="Heading 2">H2</button>
                        <button type="button" data-cmd="formatBlock" data-value="h3" title="Heading 3">H3</button>
                        <button type="button" data-cmd="formatBlock" data-value="p" title="Paragraf">¶</button>
                        <span class="rte__sep"></span>
                        <button type="button" data-cmd="insertUnorderedList" title="List">• List</button>
                        <button type="button" data-cmd="insertOrderedList" title="List bernomor">1. List</button>
                        <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Kutipan">❝</button>
                        <span class="rte__sep"></span>
                        <button type="button" data-cmd="createLink" title="Tautan">Link</button>
                        <button type="button" data-cmd="removeFormat" title="Hapus format">Tx</button>
                    </div>
                    <div class="rte__area prose-editor" contenteditable="true" data-rte-editor role="textbox" aria-multiline="true" aria-label="<?= e($field['label'] ?? $name) ?>"><?= $value ?></div>
                    <textarea name="<?= e($name) ?>" hidden data-rte-input><?= e((string) $value) ?></textarea>
                </div>

                <?php elseif ($fieldType === 'select'): ?>
                <select id="<?= e($fieldId) ?>" name="<?= e($name) ?>">
                    <?php foreach (($field['options'] ?? []) as $optValue => $optLabel): ?>
                    <option value="<?= e((string) $optValue) ?>" <?= (string) $value === (string) $optValue ? 'selected' : '' ?>><?= e((string) $optLabel) ?></option>
                    <?php endforeach; ?>
                </select>

                <?php elseif ($fieldType === 'checkbox'): ?>
                <input type="checkbox" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" value="1" <?= !empty($value) ? 'checked' : '' ?>>

                <?php elseif ($fieldType === 'image'): ?>
                <div class="image-field" data-image-field>
                    <div class="image-field__preview">
                        <img src="<?= e(admin_media((string) $value)) ?>" alt="Preview <?= e($field['label'] ?? $name) ?>" data-preview-target>
                    </div>
                    <div class="image-field__meta">
                        <input type="file" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" accept="image/*" data-preview="[data-image-field] [data-preview-target]">
                        <p class="help">JPG, PNG, WEBP, atau GIF. Maks. 5 MB.</p>
                        <?php if ((string) $value !== ''): ?>
                        <label class="checkline"><input type="checkbox" name="remove_<?= e($name) ?>" value="1"> Hapus gambar saat ini</label>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif ($fieldType === 'images'): ?>
                <input type="hidden" name="<?= e($name) ?>" value="<?= e((string) $value) ?>">
                <?php
                $images = [];
                foreach (preg_split('/\r?\n/', (string) $value) ?: [] as $line) {
                    if (trim($line) !== '') {
                        $images[] = trim($line);
                    }
                }
                ?>
                <?php if ($images): ?>
                <div class="gallery-field">
                    <?php foreach ($images as $img): ?>
                    <label class="gallery-field__item">
                        <img src="<?= e(admin_media($img)) ?>" alt="Gambar">
                        <span class="gallery-field__remove"><input type="checkbox" name="remove_<?= e($name) ?>[]" value="<?= e($img) ?>"> Hapus</span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <input type="file" id="<?= e($fieldId) ?>" name="<?= e($name) ?>_upload[]" accept="image/*" multiple>
                <p class="help">Bisa pilih beberapa file sekaligus. Checkbox “Hapus” baru berlaku setelah disimpan.</p>

                <?php elseif ($fieldType === 'slug'): ?>
                <div class="input-suffix">
                    <input type="text" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" value="<?= e((string) $value) ?>" placeholder="otomatis-dari-judul" data-slug-input data-slug-from="<?= e($field['from'] ?? 'title') ?>">
                    <span>.php</span>
                </div>

                <?php elseif ($fieldType === 'url'): ?>
                <input type="url" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" value="<?= e((string) $value) ?>" placeholder="<?= e($field['placeholder'] ?? 'https://') ?>">

                <?php elseif ($fieldType === 'number'): ?>
                <input type="number" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" value="<?= e((string) $value) ?>" step="1">

                <?php elseif ($fieldType === 'date'): ?>
                <input type="date" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" value="<?= e((string) $value) ?>">

                <?php else: ?>
                <input type="text" id="<?= e($fieldId) ?>" name="<?= e($name) ?>" value="<?= e((string) $value) ?>" placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                <?php endif; ?>

                <?php if ($error): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                <?php if (!empty($field['help']) && $fieldType !== 'checkbox'): ?><small class="help"><?= e($field['help']) ?></small><?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="form-sticky">
        <span><?= icon('shield', 15) ?> Perubahan langsung tersimpan ke database.</span>
        <div class="form-sticky__actions">
            <a href="<?= e($listUrl) ?>" class="btn btn--ghost">Batal</a>
            <button type="submit" class="btn btn--primary"><?= icon('check', 16) ?> <?= $editing ? 'Simpan perubahan' : 'Tambah konten' ?></button>
        </div>
    </div>
</form>

<?php else: ?>

<div class="collection-head">
    <div class="collection-head__info">
        <h2><?= count($rows) ?> <span><?= count($rows) === 1 ? 'item' : 'item' ?></span></h2>
        <p><?= $isFiltered ? 'Hasil filter dari ' . $totalCount . ' item.' : 'Total ' . $totalCount . ' item. Seret ikon di kiri untuk mengatur urutan.' ?></p>
    </div>
    <div class="collection-head__actions">
        <form method="get" action="collection.php" class="collection-search">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <label class="collection-search__input">
                <?= icon('search', 16) ?>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari <?= e(strtolower($entity['label'])) ?>…" aria-label="Cari">
            </label>
            <?php if ($hasStatus): ?>
            <select name="status" aria-label="Filter status">
                <option value="">Semua status</option>
                <?php foreach ($fields['status']['options'] as $optValue => $optLabel): ?>
                <option value="<?= e((string) $optValue) ?>" <?= $statusFilter === (string) $optValue ? 'selected' : '' ?>><?= e((string) $optLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <button type="submit" class="btn btn--outline btn--sm"><?= icon('filter', 14) ?> Filter</button>
            <?php if ($isFiltered): ?>
            <a href="<?= e($listUrl) ?>" class="btn btn--ghost btn--sm">Reset</a>
            <?php endif; ?>
        </form>
        <a href="<?= e($listUrl) ?>&action=new" class="btn btn--primary"><?= icon('plus', 16) ?> Tambah</a>
    </div>
</div>

<?php if ($isFiltered): ?>
<p class="collection-note"><?= icon('drag', 14) ?> Urutan drag &amp; drop dinonaktifkan saat filter aktif. Reset filter untuk mengatur ulang.</p>
<?php endif; ?>

<section class="admin-card admin-card--flush">
    <?php if (!$rows): ?>
    <div class="empty-state empty-state--admin">
        <?= icon($entity['icon'] ?? 'folder', 30) ?>
        <h2><?= $isFiltered ? 'Tidak ada hasil.' : 'Belum ada ' . strtolower($entity['label']) . '.' ?></h2>
        <p><?= $isFiltered ? 'Coba kata kunci lain atau reset filter.' : 'Tambahkan konten pertama untuk mulai mengisi halaman.' ?></p>
        <a href="<?= e($listUrl) ?>&action=new" class="btn btn--primary"><?= icon('plus', 15) ?> Tambah sekarang</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="admin-table <?= $isFiltered ? '' : 'sortable' ?>" data-sortable data-type="<?= e($type) ?>">
            <thead>
                <tr>
                    <?php if (!$isFiltered): ?><th class="th-drag" aria-label="Urutan"></th><?php endif; ?>
                    <?php if (!empty($entity['thumb'])): ?><th class="th-thumb">Media</th><?php endif; ?>
                    <th><?= e($entity['title'] === 'name' ? 'Nama' : 'Judul') ?></th>
                    <?php foreach (($entity['columns'] ?? []) as $column => $label): ?>
                    <th><?= e($label) ?></th>
                    <?php endforeach; ?>
                    <th class="th-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr data-id="<?= (int) $row['id'] ?>" <?= $isFiltered ? '' : 'draggable="true"' ?>>
                    <?php if (!$isFiltered): ?>
                    <td class="td-drag"><span class="drag-handle" title="Seret untuk mengatur urutan"><?= icon('drag', 16) ?></span></td>
                    <?php endif; ?>
                    <?php if (!empty($entity['thumb'])): ?>
                    <td class="td-thumb">
                        <img src="<?= e(admin_media($row[$entity['thumb']] ?? '')) ?>" alt="" width="64" height="44">
                    </td>
                    <?php endif; ?>
                    <td class="td-title">
                        <a href="<?= e($listUrl) ?>&action=edit&id=<?= (int) $row['id'] ?>"><?= e($row[$entity['title']] ?? '(tanpa judul)') ?></a>
                        <?php if ($isFiltered): ?><span class="td-order">#<?= (int) $row['sort_order'] ?></span><?php endif; ?>
                    </td>
                    <?php foreach (($entity['columns'] ?? []) as $column => $label): ?>
                    <td>
                        <?php if ($column === 'status'): ?>
                            <?= status_badge((string) $row[$column]) ?>
                        <?php elseif ($column === 'views'): ?>
                            <?= number_format((int) $row[$column], 0, ',', '.') ?>
                        <?php elseif ($column === 'published_at'): ?>
                            <?= e(format_date((string) $row[$column])) ?: '—' ?>
                        <?php elseif ($column === 'icon'): ?>
                            <span class="td-icon"><?= icon((string) ($row[$column] ?: 'code'), 15) ?> <?= e((string) $row[$column]) ?></span>
                        <?php else: ?>
                            <?= e((string) ($row[$column] ?? '')) ?: '—' ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="td-actions">
                        <?php $previewUrl = collection_preview_url($type, $row); ?>
                        <?php if ($previewUrl): ?>
                        <a href="<?= e($previewUrl) ?>" target="_blank" rel="noopener" class="row-action" title="Preview"><?= icon('eye', 15) ?></a>
                        <?php endif; ?>
                        <a href="<?= e($listUrl) ?>&action=edit&id=<?= (int) $row['id'] ?>" class="row-action" title="Edit"><?= icon('edit', 15) ?></a>
                        <form method="post" action="<?= e($listUrl) ?>" class="inline-form" data-confirm="Hapus “<?= e($row[$entity['title']] ?? 'item') ?>” secara permanen?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="row-action row-action--danger" title="Hapus"><?= icon('trash', 15) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php endif; ?>

<?php require __DIR__ . '/../inc/admin-footer.php'; ?>
