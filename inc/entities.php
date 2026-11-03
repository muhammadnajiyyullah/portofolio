<?php
declare(strict_types=1);

/**
 * Definisi koleksi untuk dashboard admin (CRUD generik).
 */
function entities(): array
{
    static $entities = null;
    if ($entities !== null) {
        return $entities;
    }

    $status = ['published' => 'Publish', 'draft' => 'Draft'];

    $entities = [
        'projects' => [
            'table'   => 'projects',
            'label'   => 'Proyek',
            'icon'    => 'folder',
            'desc'    => 'Studi kasus, screenshot, tech stack, dan tautan demo.',
            'order'   => 'sort_order ASC, id DESC',
            'title'   => 'title',
            'thumb'   => 'thumbnail',
            'columns' => ['category' => 'Kategori', 'status' => 'Status', 'views' => 'Views'],
            'search'  => ['title', 'category', 'tech_stack'],
            'fields'  => [
                'title'       => ['label' => 'Judul proyek', 'type' => 'text', 'required' => true, 'placeholder' => 'Contoh: Lumina — Learning Dashboard', 'full' => true],
                'slug'        => ['label' => 'Slug URL', 'type' => 'slug', 'from' => 'title', 'help' => 'Dipakai pada URL project.php?slug=...'],
                'category'    => ['label' => 'Kategori', 'type' => 'select', 'options' => ['Web' => 'Web', 'Mobile' => 'Mobile', 'AI/Data' => 'AI / Data', 'Desain' => 'Desain'], 'default' => 'Web'],
                'summary_id'  => ['label' => 'Ringkasan (ID)', 'type' => 'textarea', 'rows' => 3, 'full' => true],
                'summary_en'  => ['label' => 'Ringkasan (EN)', 'type' => 'textarea', 'rows' => 3, 'full' => true, 'optional' => true],
                'description' => ['label' => 'Studi kasus lengkap', 'type' => 'richtext', 'full' => true, 'help' => 'Tampil di halaman detail proyek.'],
                'problem'     => ['label' => 'Masalah', 'type' => 'textarea', 'rows' => 3],
                'solution'    => ['label' => 'Solusi', 'type' => 'textarea', 'rows' => 3],
                'role'        => ['label' => 'Peran', 'type' => 'textarea', 'rows' => 3],
                'result'      => ['label' => 'Hasil', 'type' => 'textarea', 'rows' => 3],
                'tech_stack'  => ['label' => 'Tech stack', 'type' => 'tags', 'placeholder' => 'Next.js, TypeScript, Tailwind CSS', 'help' => 'Pisahkan dengan koma.', 'full' => true],
                'thumbnail'   => ['label' => 'Thumbnail', 'type' => 'image', 'help' => 'Rasio 16:10, minimal 1200px lebar.'],
                'gallery'     => ['label' => 'Screenshot tambahan', 'type' => 'images'],
                'live_url'    => ['label' => 'Live demo URL', 'type' => 'url', 'placeholder' => 'https://...'],
                'github_url'  => ['label' => 'GitHub URL', 'type' => 'url', 'placeholder' => 'https://github.com/...'],
                'featured'    => ['label' => 'Jadikan unggulan', 'type' => 'checkbox', 'help' => 'Tampil lebih besar di grid proyek.'],
                'status'      => ['label' => 'Status', 'type' => 'select', 'options' => $status, 'default' => 'published'],
                'sort_order'  => ['label' => 'Urutan', 'type' => 'number', 'default' => 0, 'help' => 'Angka kecil tampil lebih dulu. Bisa diatur drag & drop.'],
            ],
        ],

        'skills' => [
            'table'   => 'skills',
            'label'   => 'Skill',
            'icon'    => 'zap',
            'desc'    => 'Daftar teknologi dan soft skill, dikelompokkan per kategori.',
            'order'   => 'sort_order ASC, id ASC',
            'title'   => 'name',
            'columns' => ['category' => 'Kategori', 'icon' => 'Ikon'],
            'search'  => ['name', 'category'],
            'fields'  => [
                'name'       => ['label' => 'Nama skill', 'type' => 'text', 'required' => true],
                'category'   => ['label' => 'Kategori', 'type' => 'select', 'options' => ['Frontend' => 'Frontend', 'Backend' => 'Backend', 'Database' => 'Database', 'Tools' => 'Tools', 'Soft Skills' => 'Soft Skills']],
                'icon'       => ['label' => 'Ikon', 'type' => 'text', 'default' => 'code', 'help' => 'Nama ikon: code, server, database, tools, users, layout, zap, figma, github, dll.'],
                'sort_order' => ['label' => 'Urutan', 'type' => 'number', 'default' => 0],
            ],
        ],

        'journey' => [
            'table'   => 'journey',
            'label'   => 'Journey',
            'icon'    => 'briefcase',
            'desc'    => 'Pendidikan, organisasi, magang, lomba, dan pengalaman kerja.',
            'order'   => 'sort_order ASC, id ASC',
            'title'   => 'title',
            'columns' => ['type' => 'Tipe', 'year' => 'Tahun'],
            'search'  => ['title', 'organization', 'type', 'year'],
            'fields'  => [
                'year'         => ['label' => 'Tahun / periode', 'type' => 'text', 'placeholder' => '2025 atau 2024 — 2025', 'required' => true],
                'title'        => ['label' => 'Judul', 'type' => 'text', 'required' => true],
                'organization' => ['label' => 'Institusi / organisasi', 'type' => 'text'],
                'description'  => ['label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 3, 'full' => true],
                'type'         => ['label' => 'Tipe', 'type' => 'select', 'options' => ['Pendidikan' => 'Pendidikan', 'Organisasi' => 'Organisasi', 'Magang' => 'Magang', 'Lomba' => 'Lomba', 'Proyek' => 'Proyek', 'Karier' => 'Karier']],
                'sort_order'   => ['label' => 'Urutan', 'type' => 'number', 'default' => 0],
            ],
        ],

        'certificates' => [
            'table'   => 'certificates',
            'label'   => 'Sertifikat',
            'icon'    => 'award',
            'desc'    => 'Sertifikasi dan penghargaan dengan tautan verifikasi.',
            'order'   => 'sort_order ASC, id ASC',
            'title'   => 'title',
            'thumb'   => 'image',
            'columns' => ['issuer' => 'Penerbit', 'year' => 'Tahun'],
            'search'  => ['title', 'issuer', 'year'],
            'fields'  => [
                'title'          => ['label' => 'Nama sertifikat', 'type' => 'text', 'required' => true, 'full' => true],
                'issuer'         => ['label' => 'Penerbit', 'type' => 'text'],
                'year'           => ['label' => 'Tahun', 'type' => 'text'],
                'credential_url' => ['label' => 'URL verifikasi', 'type' => 'url', 'placeholder' => 'https://...'],
                'image'          => ['label' => 'Gambar / badge', 'type' => 'image'],
                'sort_order'     => ['label' => 'Urutan', 'type' => 'number', 'default' => 0],
            ],
        ],

        'posts' => [
            'table'   => 'posts',
            'label'   => 'Blog',
            'icon'    => 'file',
            'desc'    => 'Catatan belajar, tutorial, dan refleksi.',
            'order'   => 'sort_order ASC, id DESC',
            'title'   => 'title',
            'thumb'   => 'cover',
            'columns' => ['status' => 'Status', 'published_at' => 'Tanggal'],
            'search'  => ['title', 'excerpt'],
            'fields'  => [
                'title'        => ['label' => 'Judul artikel', 'type' => 'text', 'required' => true, 'full' => true],
                'slug'         => ['label' => 'Slug URL', 'type' => 'slug', 'from' => 'title'],
                'excerpt'      => ['label' => 'Ringkasan', 'type' => 'textarea', 'rows' => 3, 'full' => true],
                'content'      => ['label' => 'Isi artikel', 'type' => 'richtext', 'full' => true],
                'cover'        => ['label' => 'Cover', 'type' => 'image'],
                'status'       => ['label' => 'Status', 'type' => 'select', 'options' => $status, 'default' => 'published'],
                'published_at' => ['label' => 'Tanggal publish', 'type' => 'date'],
                'sort_order'   => ['label' => 'Urutan', 'type' => 'number', 'default' => 0],
            ],
        ],

        'testimonials' => [
            'table'   => 'testimonials',
            'label'   => 'Testimoni',
            'icon'    => 'message',
            'desc'    => 'Kata-kata dari dosen, atasan, atau rekan tim.',
            'order'   => 'sort_order ASC, id ASC',
            'title'   => 'name',
            'thumb'   => 'avatar',
            'columns' => ['role' => 'Jabatan'],
            'search'  => ['name', 'role', 'quote'],
            'fields'  => [
                'name'       => ['label' => 'Nama', 'type' => 'text', 'required' => true],
                'role'       => ['label' => 'Jabatan / hubungan', 'type' => 'text'],
                'quote'      => ['label' => 'Testimoni', 'type' => 'textarea', 'rows' => 4, 'full' => true, 'required' => true],
                'avatar'     => ['label' => 'Foto', 'type' => 'image'],
                'sort_order' => ['label' => 'Urutan', 'type' => 'number', 'default' => 0],
            ],
        ],
    ];

    return $entities;
}

function entity(string $type): ?array
{
    return entities()[$type] ?? null;
}
