<?php
declare(strict_types=1);

/**
 * Seed data awal. Dipanggil satu kali saat tabel settings masih kosong.
 * Semua konten bisa diubah lewat dashboard admin.
 */
function seed_database(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() > 0) {
        return;
    }

    $settings = [
        'site_name'        => 'Dev Journal',
        'owner_name'       => 'Raka Pratama',
        'owner_initial'    => 'RP',
        'headline_id'      => 'Informatics Graduate — Web & Software Developer',
        'headline_en'      => 'Informatics Graduate — Web & Software Developer',
        'bio_id'           => 'Lulusan Informatika yang membangun produk web dengan rasa ingin tahu tinggi. Suka menyederhanakan hal rumit menjadi antarmuka yang tenang, cepat, dan menyenangkan digunakan.',
        'bio_en'           => 'An Informatics graduate who builds web products with a lot of curiosity. I love turning complex problems into calm, fast, and delightful interfaces.',
        'about_id'         => "Halo! Saya Raka, lulusan S1 Informatika yang percaya bahwa produk digital yang baik lahir dari empati — bukan hanya dari baris kode.\n\nSaya menghabiskan sebagian besar waktu untuk membangun aplikasi web end-to-end: mulai dari riset pengguna, merancang antarmuka, menulis kode, sampai merilis dan merawatnya. Selama kuliah saya aktif menjadi asisten laboratorium, mengikuti hackathon, dan mengerjakan beberapa proyek nyata untuk UMKM dan komunitas.\n\nDi luar layar, saya suka mencatat proses belajar, berdiskusi soal desain, dan mencari kopi enak di Bandung. Saya percaya portofolio bukan sekadar daftar proyek, tapi catatan bagaimana seseorang tumbuh.",
        'about_en'         => "Hi! I'm Raka, an Informatics graduate who believes great digital products are born from empathy — not just lines of code.\n\nI spend most of my time building end-to-end web applications: from user research, interface design, and coding, all the way to shipping and maintaining them. During university I worked as a lab assistant, joined hackathons, and delivered real projects for small businesses and communities.\n\nOff-screen, I enjoy writing about my learning process, discussing design, and hunting for good coffee in Bandung. I believe a portfolio is not just a list of projects, but a record of how someone grows.",
        'avatar'           => 'assets/img/avatar.svg',
        'about_photo'      => 'assets/img/about.svg',
        'cv_file'          => '',
        'location'         => 'Bandung, Indonesia',
        'email'            => 'hello@rakapratama.dev',
        'github_user'      => 'rakapratama',
        'github'           => 'https://github.com/rakapratama',
        'linkedin'         => 'https://linkedin.com/in/rakapratama',
        'whatsapp'         => '+62 812-3456-7890',
        'instagram'        => 'https://instagram.com/rakapratama',
        'open_to_work'     => '1',
        'campus'           => 'Universitas Teknologi Nusantara',
        'major'            => 'S1 Informatika',
        'ipk'             => '3.82',
        'grad_year'        => '2024',
        'fun_facts'        => "Ngoding ditemani lo-fi, bukan playlist yang ramai\nLebih suka dokumentasi rapi daripada komentar tebal\nPernah debug enam jam gara-gara satu titik koma\nPercaya desain yang baik adalah bentuk empati",
        'status_learning'  => 'Rust & system design',
        'status_building'  => 'Dev Journal v2',
        'status_listening' => 'Men I Trust — Show Me How',
        'roles'            => 'Web Developer,Software Engineer,UI Engineer,Problem Solver',
        'years_coding'     => '4',
        'theme_preset'     => 'lavender',
        'font_preset'      => 'jakarta',
        'custom_cursor'    => '1',
        'github_show'      => '1',
        'footer_note'      => 'Dirancang & dibangun sendiri dengan rasa penasaran.',
    ];

    $st = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    $pdo->prepare('INSERT INTO admins (username, password_hash, name) VALUES (?, ?, ?)')
        ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Raka Pratama']);

    /* ---------------------------------------------------------------- */
    $projects = [
        [
            'slug' => 'lumina',
            'title' => 'Lumina — Learning Dashboard',
            'category' => 'Web',
            'summary_id' => 'Platform learning management dengan kelas interaktif, progres adaptif, dan dashboard dosen yang ringkas.',
            'summary_en' => 'A learning management platform with interactive classes, adaptive progress, and a focused lecturer dashboard.',
            'description' => <<<'HTML'
<p>Lumina berawal dari percakapan dengan dua dosen yang lelah memindahkan nilai dan materi kuliah antar-spreadsheet. Saya ingin membuat satu ruang kerja yang membuat proses belajar terasa lebih manusiawi — untuk mahasiswa maupun pengajar.</p>
<h3>Pendekatan</h3>
<p>Saya memulai dengan wawancara singkat kepada 8 mahasiswa dan 3 dosen. Temuan utamanya sederhana: mereka tidak kekurangan fitur, mereka kekurangan kejelasan. Dari situ saya merancang ulang hierarki informasi: apa yang harus dikerjakan hari ini, apa yang tertinggal, dan apa yang sudah selesai.</p>
<ul>
<li>Dashboard mahasiswa dengan <em>next best action</em> dan progres per pekan.</li>
<li>Ruang kelas dengan materi, tugas, diskusi, dan pengumuman dalam satu alur.</li>
<li>Dashboard dosen untuk melihat mahasiswa yang butuh perhatian lebih cepat.</li>
<li>Ringkasan materi otomatis memakai LLM, dengan kontrol dan transparansi.</li>
</ul>
<h3>Yang saya pelajari</h3>
<p>Fitur paling kecil — label status dan pengingat yang jelas — ternyata memberi dampak paling besar. Sejak itu saya selalu mencoba menyelesaikan masalah alur sebelum menambah fitur baru.</p>
<blockquote>Desain yang baik sering kali terasa seperti tidak ada desain sama sekali — semuanya mengalir.</blockquote>
HTML,
            'problem' => 'Materi, tugas, dan nilai tersebar di banyak spreadsheet serta grup chat. Mahasiswa kehilangan konteks, dosen mengulang pekerjaan administratif yang sama setiap pekan.',
            'solution' => 'Satu dashboard terpusat dengan alur kelas yang jelas, progres adaptif, dan ringkasan otomatis untuk membantu dosen mengambil keputusan.',
            'role' => 'Product designer & full-stack developer. Riset, desain antarmuka, arsitektur data, hingga deploy.',
            'result' => 'Dipakai oleh 1.200+ mahasiswa pada masa pilot. Waktu distribusi materi turun 68% dan keterlibatan diskusi naik 2,4×.',
            'tech_stack' => 'Next.js, TypeScript, Tailwind CSS, Supabase, OpenAI API',
            'thumbnail' => 'assets/img/projects/lumina.svg',
            'gallery' => "assets/img/projects/lumina-2.svg\nassets/img/projects/lumina-3.svg",
            'live_url' => 'https://lumina.rakapratama.dev',
            'github_url' => 'https://github.com/rakapratama/lumina',
            'featured' => 1,
            'status' => 'published',
            'sort_order' => 1,
            'views' => 1240,
        ],
        [
            'slug' => 'sinara',
            'title' => 'Sinara — Review Intelligence',
            'category' => 'AI/Data',
            'summary_id' => 'Dashboard analisis sentimen untuk membantu UMKM memahami ribuan ulasan pelanggan dalam hitungan detik.',
            'summary_en' => 'A sentiment analysis dashboard that helps small businesses understand thousands of customer reviews in seconds.',
            'description' => <<<'HTML'
<p>Sinara dibuat untuk pemilik usaha kecil yang punya ratusan ulasan di marketplace tetapi tidak punya waktu membacanya satu per satu. Tujuannya bukan menggantikan intuisi pemilik usaha, melainkan mempercepat menemukan pola.</p>
<h3>Cara kerja</h3>
<p>Ulasan diambil dari ekspor marketplace, dibersihkan, lalu diklasifikasikan ke dalam sentimen dan topik (pengiriman, kualitas produk, layanan, harga). Model IndoBERT yang telah di-<em>fine-tune</em> menjadi inti analisis, sementara antarmuka dibuat sesederhana mungkin.</p>
<ul>
<li>Ringkasan sentimen per periode dengan perbandingan otomatis.</li>
<li>Deteksi topik keluhan yang paling sering muncul.</li>
<li>Kutipan ulasan pendukung agar pemilik tetap bisa membaca konteks.</li>
<li>Ekspor laporan PDF untuk rapat mingguan.</li>
</ul>
<blockquote>Data yang baik bukan yang paling banyak, tapi yang paling mudah ditindaklanjuti.</blockquote>
HTML,
            'problem' => 'Pemilik UMKM menerima ratusan ulasan per bulan dan kesulitan menemukan masalah yang benar-benar berulang.',
            'solution' => 'Klasifikasi sentimen dan topik otomatis, disajikan sebagai ringkasan visual yang bisa langsung ditindaklanjuti.',
            'role' => 'Data engineer & frontend developer. Pipeline data, pelatihan model, dan visualisasi.',
            'result' => 'Akurasi klasifikasi 91,4% pada data uji. Waktu review manual turun dari 6 jam menjadi 20 menit per bulan.',
            'tech_stack' => 'Python, FastAPI, IndoBERT, PostgreSQL, React, Recharts',
            'thumbnail' => 'assets/img/projects/sinara.svg',
            'gallery' => '',
            'live_url' => 'https://sinara.rakapratama.dev',
            'github_url' => 'https://github.com/rakapratama/sinara',
            'featured' => 0,
            'status' => 'published',
            'sort_order' => 2,
            'views' => 683,
        ],
        [
            'slug' => 'jejak',
            'title' => 'Jejak — Travel Journal',
            'category' => 'Mobile',
            'summary_id' => 'Aplikasi jurnal perjalanan untuk menyimpan cerita, foto, dan rute dalam satu peta yang terasa personal.',
            'summary_en' => 'A travel journal app to keep stories, photos, and routes in one personal map.',
            'description' => <<<'HTML'
<p>Jejak lahir dari kebiasaan saya mencatat perjalanan di catatan ponsel yang kemudian hilang entah ke mana. Saya ingin jurnal yang otomatis merangkai cerita dari foto, lokasi, dan momen kecil di sepanjang perjalanan.</p>
<h3>Fokus produk</h3>
<p>Alih-alih menjadi media sosial lain, Jejak sengaja dibuat privat: tidak ada <em>like</em>, tidak ada feed publik. Pengguna hanya bisa membagikan satu <em>story card</em> jika ingin.</p>
<ul>
<li>Peta perjalanan dengan garis waktu dan <em>photo pins</em>.</li>
<li>Entri jurnal dengan cuaca, lokasi, dan mood otomatis.</li>
<li>Mode offline untuk area tanpa sinyal.</li>
<li>Story card yang bisa diekspor ke Instagram.</li>
</ul>
HTML,
            'problem' => 'Cerita perjalanan tersebar di galeri, catatan, dan story yang hilang setelah 24 jam.',
            'solution' => 'Jurnal privat yang menggabungkan foto, lokasi, dan tulisan menjadi satu alur perjalanan yang bisa dikenang.',
            'role' => 'Mobile developer & product designer. Seluruh alur produk dari riset sampai rilis.',
            'result' => '1.000+ unduhan organik dalam tiga bulan pertama dengan rating 4,8/5 dari 120 ulasan.',
            'tech_stack' => 'Flutter, Dart, Firebase, Mapbox, Riverpod',
            'thumbnail' => 'assets/img/projects/jejak.svg',
            'gallery' => '',
            'live_url' => 'https://jejak.rakapratama.dev',
            'github_url' => 'https://github.com/rakapratama/jejak',
            'featured' => 0,
            'status' => 'published',
            'sort_order' => 3,
            'views' => 512,
        ],
        [
            'slug' => 'tanam',
            'title' => 'Tanam — Farm Marketplace',
            'category' => 'Web',
            'summary_id' => 'Marketplace hasil tani yang menghubungkan petani lokal langsung dengan rumah tangga di kota.',
            'summary_en' => 'A produce marketplace connecting local farmers directly with urban households.',
            'description' => <<<'HTML'
<p>Tanam adalah proyek kolaborasi bersama komunitas tani di Lembang. Masalahnya bukan sekadar jual-beli: petani kesulitan memprediksi permintaan, sementara pembeli tidak tahu kapan panen berikutnya tersedia.</p>
<h3>Solusi</h3>
<p>Kami membangun katalog panen dengan jadwal yang transparan, pre-order mingguan, dan pengiriman yang dikonsolidasikan agar biaya tetap masuk akal. Saya bertanggung jawab pada sisi web dan integrasi pembayaran.</p>
<ul>
<li>Katalog panen dengan status stok dan estimasi panen.</li>
<li>Sistem pre-order per pekan dengan batas kapasitas.</li>
<li>Dashboard petani untuk mengelola produk dan pesanan.</li>
<li>Notifikasi WhatsApp untuk pesanan dan jadwal kirim.</li>
</ul>
HTML,
            'problem' => 'Rantai distribusi panjang membuat harga tidak adil bagi petani, sementara pembeli kesulitan mendapatkan produk segar secara rutin.',
            'solution' => 'Marketplace langsung dengan pre-order mingguan, jadwal panen transparan, dan pengiriman terkonsolidasi.',
            'role' => 'Full-stack developer. Katalog, checkout, dashboard petani, dan integrasi pembayaran.',
            'result' => 'Dipakai 24 petani dan 600+ rumah tangga. Penjualan petani naik rata-rata 31% pada tiga bulan pertama.',
            'tech_stack' => 'Laravel, MySQL, Tailwind CSS, Alpine.js, Midtrans',
            'thumbnail' => 'assets/img/projects/tanam.svg',
            'gallery' => '',
            'live_url' => 'https://tanam.rakapratama.dev',
            'github_url' => 'https://github.com/rakapratama/tanam',
            'featured' => 0,
            'status' => 'published',
            'sort_order' => 4,
            'views' => 428,
        ],
        [
            'slug' => 'resep-nusantara',
            'title' => 'Resep Nusantara',
            'category' => 'Desain',
            'summary_id' => 'Eksplorasi desain antarmuka aplikasi resep yang hangat, sederhana, dan kaya cerita kuliner.',
            'summary_en' => 'A UI design exploration for a recipe app — warm, simple, and rich with culinary stories.',
            'description' => <<<'HTML'
<p>Resep Nusantara adalah eksplorasi desain yang saya kerjakan untuk melatih kemampuan sistem desain. Fokusnya bukan jumlah layar, melainkan konsistensi dan karakter visual yang terasa lokal tanpa jatuh ke klise.</p>
<h3>Proses</h3>
<p>Saya membangun token warna, tipografi, dan komponen dari nol, lalu menguji alurnya dengan lima calon pengguna. Palet rempah — terracotta, kuning kunyit, hijau daun — menjadi arah utama.</p>
<ul>
<li>Design token dan komponen yang dapat digunakan ulang.</li>
<li>Alur memasak dengan mode tangan basah dan langkah besar.</li>
<li>Halaman cerita di balik setiap resep.</li>
<li>Prototipe interaktif untuk uji pengguna.</li>
</ul>
HTML,
            'problem' => 'Aplikasi resep lokal umumnya penuh iklan dan sulit dibaca saat memasak.',
            'solution' => 'Antarmuka hangat dengan langkah besar, navigasi sederhana, dan cerita di balik setiap resep.',
            'role' => 'Product designer. Riset, design system, prototipe, dan uji pengguna.',
            'result' => 'Skor kemudahan penggunaan 86 SUS, naik 27 poin dibanding prototipe awal. Digunakan sebagai studi kasus kelas desain.',
            'tech_stack' => 'Figma, Design Tokens, React, Framer Motion',
            'thumbnail' => 'assets/img/projects/resep.svg',
            'gallery' => '',
            'live_url' => 'https://dribbble.com/rakapratama',
            'github_url' => 'https://github.com/rakapratama/resep-nusantara',
            'featured' => 0,
            'status' => 'published',
            'sort_order' => 5,
            'views' => 377,
        ],
        [
            'slug' => 'cuacaku',
            'title' => 'CuacaKu — Calm Weather PWA',
            'category' => 'Web',
            'summary_id' => 'Aplikasi cuaca minimalis yang menampilkan informasi penting tanpa distraksi, bisa dipasang seperti aplikasi native.',
            'summary_en' => 'A minimal weather app that shows what matters without distraction, installable like a native app.',
            'description' => <<<'HTML'
<p>CuacaKu adalah proyek kecil untuk bereksperimen dengan performa dan aksesibilitas. Sebagian besar aplikasi cuaca penuh dengan angka dan ikon; saya ingin versi yang lebih tenang.</p>
<p>Dibangun sebagai PWA dengan cache offline, aplikasi ini tetap menampilkan data terakhir saat koneksi buruk. Skor Lighthouse 98 untuk performance dan 100 untuk accessibility.</p>
<ul>
<li>Prakiraan per jam dan tujuh hari dengan grafik sederhana.</li>
<li>Mode offline dan pemasangan ke home screen.</li>
<li>Dukungan mode gelap otomatis.</li>
<li>Tanpa iklan dan tanpa pelacakan.</li>
</ul>
HTML,
            'problem' => 'Aplikasi cuaca penuh iklan dan informasi berlebihan yang justru menyulitkan pembacaan cepat.',
            'solution' => 'PWA ringan dengan hierarki jelas, data offline, dan aksesibilitas yang baik.',
            'role' => 'Frontend developer & designer. Seluruh pembuatan produk.',
            'result' => 'Lighthouse performance 98, accessibility 100. Dipakai 300+ pengguna bulanan tanpa biaya server.',
            'tech_stack' => 'Vue 3, Vite, OpenWeather API, Service Worker',
            'thumbnail' => 'assets/img/projects/cuaca.svg',
            'gallery' => '',
            'live_url' => 'https://cuacaku.rakapratama.dev',
            'github_url' => 'https://github.com/rakapratama/cuacaku',
            'featured' => 0,
            'status' => 'published',
            'sort_order' => 6,
            'views' => 295,
        ],
    ];

    $cols = array_keys($projects[0]);
    $place = implode(', ', array_map(fn ($c) => ':' . $c, $cols));
    $stmt = $pdo->prepare('INSERT INTO projects (' . implode(', ', $cols) . ') VALUES (' . $place . ')');
    foreach ($projects as $row) {
        $stmt->execute($row);
    }

    /* ---------------------------------------------------------------- */
    $skills = [
        ['HTML & CSS', 'Frontend', 'layout'],
        ['JavaScript', 'Frontend', 'code'],
        ['TypeScript', 'Frontend', 'code'],
        ['React', 'Frontend', 'layers'],
        ['Next.js', 'Frontend', 'zap'],
        ['Tailwind CSS', 'Frontend', 'palette'],
        ['Vue 3', 'Frontend', 'layers'],
        ['PHP', 'Backend', 'server'],
        ['Laravel', 'Backend', 'server'],
        ['Node.js', 'Backend', 'server'],
        ['Express', 'Backend', 'server'],
        ['Python', 'Backend', 'terminal'],
        ['FastAPI', 'Backend', 'zap'],
        ['MySQL', 'Database', 'database'],
        ['PostgreSQL', 'Database', 'database'],
        ['Redis', 'Database', 'database'],
        ['Supabase', 'Database', 'database'],
        ['Firebase', 'Database', 'database'],
        ['Git & GitHub', 'Tools', 'github'],
        ['Docker', 'Tools', 'tools'],
        ['Figma', 'Tools', 'figma'],
        ['Vite', 'Tools', 'zap'],
        ['Vercel', 'Tools', 'rocket'],
        ['Postman', 'Tools', 'send'],
        ['Problem Solving', 'Soft Skills', 'sparkles'],
        ['Komunikasi', 'Soft Skills', 'message'],
        ['Kolaborasi Tim', 'Soft Skills', 'users'],
        ['Public Speaking', 'Soft Skills', 'users'],
        ['Adaptif & Cepat Belajar', 'Soft Skills', 'trending'],
    ];
    $stmt = $pdo->prepare('INSERT INTO skills (name, category, icon, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($skills as $i => $s) {
        $stmt->execute([$s[0], $s[1], $s[2], $i + 1]);
    }

    /* ---------------------------------------------------------------- */
    $journey = [
        ['2025', 'Backend Engineer', 'Karsa Studio', 'Membangun layanan API dan sistem internal untuk klien fintech. Fokus pada kualitas kode, observability, dan kolaborasi lintas tim.', 'Karier'],
        ['2025', 'Juara 2 — Hackathon Nasional', 'Kementerian Ekonomi Digital', 'Membangun prototipe platform pencocokan kerja untuk difabel dalam 36 jam bersama tim berisi empat orang.', 'Lomba'],
        ['2024', 'Lulus Cum Laude — S1 Informatika', 'Universitas Teknologi Nusantara', 'Menyelesaikan skripsi tentang sistem rekomendasi materi belajar adaptif. IPK akhir 3,82/4,00.', 'Pendidikan'],
        ['2024', 'Freelance Web Developer', 'Self-employed', 'Mengerjakan website company profile, katalog produk, dan sistem pemesanan untuk UMKM di Bandung dan sekitarnya.', 'Proyek'],
        ['2023', 'Frontend Developer Intern', 'PT Digital Nusantara', 'Mengembangkan komponen antarmuka dan design system untuk produk internal dengan React dan TypeScript.', 'Magang'],
        ['2022', 'Asisten Laboratorium Pemrograman', 'Universitas Teknologi Nusantara', 'Membimbing praktikum algoritma dan pemrograman web untuk 120+ mahasiswa. Menyusun modul praktikum yang dipakai hingga kini.', 'Organisasi'],
        ['2021', 'Ketua Divisi Media & Teknologi', 'Himpunan Mahasiswa Informatika', 'Memimpin tim enam orang untuk mengelola website, media sosial, dan dokumentasi kegiatan himpunan.', 'Organisasi'],
        ['2020', 'Mulai Kuliah Informatika', 'Universitas Teknologi Nusantara', 'Awal perjalanan belajar logika, struktur data, dan dasar rekayasa perangkat lunak.', 'Pendidikan'],
    ];
    $stmt = $pdo->prepare('INSERT INTO journey (year, title, organization, description, type, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($journey as $i => $j) {
        $stmt->execute([$j[0], $j[1], $j[2], $j[3], $j[4], $i + 1]);
    }

    /* ---------------------------------------------------------------- */
    $certs = [
        ['Frontend Developer Professional', 'Dicoding Indonesia', '2024', 'https://dicoding.com/certificates/example-1', 'assets/img/cert-1.svg'],
        ['AWS Cloud Practitioner Essentials', 'Amazon Web Services', '2024', 'https://aws.amazon.com/training/example-2', 'assets/img/cert-2.svg'],
        ['Machine Learning for Everyone', 'Coursera', '2023', 'https://coursera.org/verify/example-3', 'assets/img/cert-3.svg'],
        ['Juara 2 National Hackathon', 'Kementerian Ekonomi Digital', '2025', 'https://example.com/hackathon', 'assets/img/cert-4.svg'],
    ];
    $stmt = $pdo->prepare('INSERT INTO certificates (title, issuer, year, credential_url, image, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($certs as $i => $c) {
        $stmt->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $i + 1]);
    }

    /* ---------------------------------------------------------------- */
    $posts = [
        [
            'slug' => 'merawat-kode-setelah-lulus',
            'title' => 'Yang Tidak Diajarkan di Kelas: Merawat Kode Setelah Lulus',
            'excerpt' => 'Menulis kode sampai jalan itu satu hal. Membuatnya tetap bisa dipahami enam bulan kemudian adalah keterampilan lain yang jarang dibahas di bangku kuliah.',
            'cover' => 'assets/img/posts/post-1.svg',
            'published_at' => '2026-09-18',
            'content' => <<<'HTML'
<p>Selama kuliah, saya menilai keberhasilan sebuah proyek dari satu hal: apakah programnya berjalan. Setelah lulus, standarnya berubah. Kode yang berjalan tetapi tidak bisa dipahami orang lain — termasuk diri saya enam bulan kemudian — ternyata jauh lebih mahal.</p>
<h2>Kode adalah surat untuk masa depan</h2>
<p>Ada satu kebiasaan kecil yang mengubah cara saya bekerja: membayangkan pembaca kode saya adalah saya sendiri, lelah, jam sebelas malam, dan sedang panik karena ada bug produksi. Apa yang bisa ia pahami dalam tiga menit pertama?</p>
<ul>
<li>Nama variabel yang menjelaskan maksud, bukan tipe data.</li>
<li>Fungsi yang melakukan satu hal dengan baik.</li>
<li>Komentar untuk <em>mengapa</em>, bukan <em>apa</em>.</li>
<li>Pesan error yang membantu, bukan sekadar kode angka.</li>
</ul>
<h2>Dokumentasi bukan formalitas</h2>
<p>Saya dulu menganggap dokumentasi sebagai tugas tambahan. Sekarang saya memperlakukannya seperti bagian dari definisi selesai. Satu halaman README yang jelas menghemat berjam-jam diskusi tim.</p>
<blockquote>Menulis kode adalah kegiatan berkomunikasi. Kompilator hanya salah satu pendengarnya.</blockquote>
<p>Jika kamu sedang mulai membangun portofolio atau proyek pertama, jangan menunggu sampai semuanya sempurna. Mulailah dengan menulis keputusanmu. Enam bulan lagi, catatan itu akan terasa seperti hadiah.</p>
HTML,
            'status' => 'published',
            'sort_order' => 1,
        ],
        [
            'slug' => 'design-system-untuk-proyek-solo',
            'title' => 'Membangun Design System Kecil untuk Proyek Solo',
            'excerpt' => 'Design system tidak harus besar. Bahkan untuk proyek satu orang, beberapa token dan komponen kecil bisa menyelamatkan konsistensi.',
            'cover' => 'assets/img/posts/post-2.svg',
            'published_at' => '2026-08-02',
            'content' => <<<'HTML'
<p>Proyek solo punya satu risiko besar: inkonsistensi. Kita mengubah tombol di satu halaman, tetapi lupa halaman lain. Design system kecil adalah cara paling murah untuk mencegahnya.</p>
<h2>Mulai dari yang paling sering dipakai</h2>
<p>Saya tidak perlu puluhan komponen. Cukup mulai dari token warna, skala tipografi, radius, dan bayangan. Setelah itu, tiga komponen inti: tombol, kartu, dan input.</p>
<ul>
<li><strong>Token dulu, komponen kemudian.</strong> Konsistensi lahir dari nilai yang sama.</li>
<li><strong>Dokumentasikan satu contoh pemakaian.</strong> Bukan dokumentasi panjang.</li>
<li><strong>Batasi variasi.</strong> Dua ukuran tombol lebih baik daripada tujuh.</li>
<li><strong>Uji dalam kondisi nyata.</strong> Mode gelap dan teks panjang sering membongkar masalah.</li>
</ul>
<h2>Nilai lebih dari estetika</h2>
<p>Design system yang baik mempercepat keputusan. Saya tidak lagi menghabiskan sepuluh menit memilih warna hover; cukup menggunakan token yang sudah ada. Energi itu bisa dipakai untuk hal yang lebih penting: memahami pengguna.</p>
<blockquote>Sistem yang baik membebaskan kreativitas, bukan membatasinya.</blockquote>
HTML,
            'status' => 'published',
            'sort_order' => 2,
        ],
        [
            'slug' => 'checklist-deploy-pertama',
            'title' => 'Dari Skripsi ke Production: Checklist Deploy Pertama',
            'excerpt' => 'Catatan kecil tentang hal-hal yang sering terlewat saat pertama kali membawa proyek kuliah ke server sungguhan.',
            'cover' => 'assets/img/posts/post-3.svg',
            'published_at' => '2026-06-21',
            'content' => <<<'HTML'
<p>Deploy pertama selalu terasa mendebarkan. Aplikasi yang berjalan mulus di laptop tiba-tiba punya masalah yang tidak pernah muncul sebelumnya. Berikut checklist yang sekarang selalu saya pakai.</p>
<h2>Sebelum menekan tombol deploy</h2>
<ul>
<li>Variabel lingkungan lengkap dan tidak ada kredensial di repository.</li>
<li>Backup database dan uji proses restore.</li>
<li>Pastikan halaman error dan <em>loading</em> sudah didesain.</li>
<li>Aktifkan HTTPS, periksa header keamanan dasar.</li>
<li>Siapkan logging dan cara cepat membaca error.</li>
<li>Ukur performa dasar sebelum menambah fitur.</li>
</ul>
<h2>Setelah deploy</h2>
<p>Jangan langsung pergi. Buka aplikasi di perangkat lain, uji alur utama, kirim satu pesan kontak, dan periksa apakah email masuk. Hal-hal kecil inilah yang membedakan “sudah jalan” dari “siap dipakai”.</p>
<blockquote>Production bukan garis akhir. Ia adalah tempat di mana pelajaran sebenarnya dimulai.</blockquote>
<p>Skripsi mengajarkan saya membangun sesuatu yang benar. Deployment mengajarkan saya membangun sesuatu yang bisa dipercaya.</p>
HTML,
            'status' => 'published',
            'sort_order' => 3,
        ],
    ];
    $stmt = $pdo->prepare('INSERT INTO posts (slug, title, excerpt, content, cover, status, published_at, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($posts as $p) {
        $stmt->execute([$p['slug'], $p['title'], $p['excerpt'], $p['content'], $p['cover'], $p['status'], $p['published_at'], $p['sort_order']]);
    }

    /* ---------------------------------------------------------------- */
    $testimonials = [
        ['Dr. Ayu Lestari', 'Dosen Pembimbing, Universitas Teknologi Nusantara', 'Raka adalah tipe mahasiswa yang tidak menunggu instruksi. Ia membaca konteks, mengajukan pertanyaan yang tepat, lalu mengeksekusi dengan rapi. Skripsinya menjadi salah satu yang paling matang di angkatannya.', 'assets/img/avatar-1.svg'],
        ['Bagas Wirawan', 'Engineering Lead, PT Digital Nusantara', 'Selama magang, Raka cepat menjadi bagian dari tim. Ia tidak hanya menyelesaikan tiket, tetapi juga merapikan komponen yang sudah lama kami abaikan. Kodenya mudah dibaca dan komunikasinya jelas.', 'assets/img/avatar-2.svg'],
        ['Nadia Putri', 'Product Designer, kolaborator Tanam', 'Bekerja dengan Raka terasa seperti punya rekan yang benar-benar peduli pada pengalaman pengguna. Ia menghormati proses desain, tetapi selalu membawa solusi teknis yang membuat ide kami bisa terwujud.', 'assets/img/avatar-3.svg'],
    ];
    $stmt = $pdo->prepare('INSERT INTO testimonials (name, role, quote, avatar, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($testimonials as $i => $tst) {
        $stmt->execute([$tst[0], $tst[1], $tst[2], $tst[3], $i + 1]);
    }

    /* ---------------------------------------------------------------- */
    $messages = [
        ['Dinda Maharani', 'dinda@nusantaratalent.com', 'Peluang Frontend Engineer', "Halo Raka, saya menemukan portofolio kamu dan terkesan dengan proyek Lumina. Kami sedang membuka posisi Frontend Engineer untuk tim produk di Jakarta (hybrid). Apakah kamu terbuka untuk berdiskusi pekan depan?", 0, '2026-10-28 09:14:00'],
        ['Yoga Saputra', 'yoga@komunitaskita.id', 'Kolaborasi workshop web', "Hai Raka! Kami ingin mengundangmu sebagai pemateri workshop 'Web Performance untuk Pemula' bulan depan. Durasi sekitar 90 menit, bisa online. Kalau tertarik, saya kirim detailnya ya.", 1, '2026-10-21 16:40:00'],
    ];
    $stmt = $pdo->prepare('INSERT INTO messages (name, email, subject, body, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($messages as $m) {
        $stmt->execute($m);
    }

    $guestbook = [
        ['Fajar', 'Portofolionya rapi banget, suka sama bagian terminal mode-nya!', '2026-10-25 14:02:00'],
        ['Sinta', 'Studi kasus Lumina bikin belajar banyak soal alur desain. Semangat!', '2026-10-20 10:17:00'],
        ['Dimas', 'Izin belajar struktur kodenya ya, keren untuk portofolio lulusan baru.', '2026-10-12 20:55:00'],
    ];
    $stmt = $pdo->prepare('INSERT INTO guestbook (name, message, created_at) VALUES (?, ?, ?)');
    foreach ($guestbook as $g) {
        $stmt->execute($g);
    }

    $reactions = [
        [1, '🔥', 42], [1, '👏', 28], [1, '💡', 17],
        [2, '🔥', 19], [2, '👏', 12],
        [3, '🔥', 24], [3, '👏', 9],
        [4, '🔥', 11], [4, '💡', 7],
        [5, '👏', 15], [5, '💡', 8],
        [6, '🔥', 9],
    ];
    $stmt = $pdo->prepare('INSERT INTO reactions (project_id, emoji, count) VALUES (?, ?, ?)');
    foreach ($reactions as $r) {
        $stmt->execute($r);
    }

    $stmt = $pdo->prepare('INSERT IGNORE INTO visits (day, views) VALUES (?, ?)');
    $base = new DateTimeImmutable('today');
    $sample = [18, 24, 21, 33, 29, 41, 37, 26, 45, 39, 52, 47, 61, 58];
    foreach ($sample as $i => $v) {
        $stmt->execute([$base->sub(new DateInterval('P' . (13 - $i) . 'D'))->format('Y-m-d'), $v]);
    }
}
