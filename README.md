# Dev Journal — Personal Portfolio

Portofolio pribadi untuk lulusan Informatika, lengkap dengan dashboard admin untuk mengelola seluruh konten tanpa menyentuh kode. Dibangun dengan **PHP 8.1 + MySQL** murni (tanpa framework, tanpa Composer), sehingga ringan dan mudah dijalankan di Laragon.

Persona demo: **Raka Pratama** — Informatics Graduate, Bandung. Semua data awal bersifat fiktif dan bisa diganti dari dashboard.

---

## ✨ Fitur

### Halaman publik
- Hero dengan typewriter, badge open-to-work, orbit skill, dan avatar SVG.
- About, skills per kategori (tanpa progress bar persentase), journey timeline, certificates, blog, testimonials.
- Filter proyek per kategori + halaman detail (problem, solution, role, result, galeri, tech stack, reaction).
- Blog dan halaman artikel, resume builder / CV siap cetak.
- GitHub stats & contribution heatmap (live, dengan fallback demo).
- Contact form, guestbook, reaction, visitor counter.
- Command palette (Ctrl/⌘ + K), terminal mode, recruiter mode, custom cursor, Konami easter egg.
- Bilingual ID/EN (cookie `lang`), dark/light mode, 3 preset warna (Soft Lavender, Quiet Sage, Neon Studio), 3 preset font.
- Responsif, mobile-first, animasi halus, menghormati `prefers-reduced-motion`.

### Dashboard admin (`/admin`)
- Login aman (password hash bcrypt, CSRF, lockout percobaan login, session regenerate, idle timeout).
- Dashboard statistik: kunjungan 14 hari, proyek terpopuler, pesan terbaru, catatan terbaru.
- Profil: identitas, bio ID/EN, status, media, CV PDF, kontak & sosial.
- CRUD proyek, skill, journey, sertifikat, blog, testimoni — dengan upload gambar, tag teknologi, slug otomatis, dan rich text editor.
- Drag & drop untuk mengatur urutan konten.
- Draft / publish + preview konten.
- Tampilan: palet warna, font, custom cursor, tampilkan/sembunyikan GitHub section.
- Inbox pesan kontak dan moderasi guestbook.
- **Pengguna & Keamanan:** buat akun admin, reset password, aktif/nonaktif, hapus akun, dan pantau riwayat percobaan login.
- Dark mode admin, sidebar responsif.

---

## 🧰 Kebutuhan

- PHP **8.1+** dengan ekstensi `pdo_mysql`, `fileinfo`, `gd`/`getimagesize` (biasanya sudah aktif di Laragon).
- MySQL / MariaDB.
- Apache (Laragon) atau PHP built-in server.

---

## 🚀 Menjalankan di Laragon

1. Letakkan folder proyek di `D:\laragon\www\portofolio`.
2. Jalankan **Laragon → Start All** (Apache + MySQL).
3. Buka `http://portofolio.test` atau `http://localhost/portofolio`.
4. Database `portofolio` akan **dibuat otomatis** saat koneksi pertama, beserta tabel dan data seed.

Konfigurasi default ada di `config.php`:

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'portofolio';
const DB_USER = 'root';
const DB_PASS = '';
```

Ubah `DB_USER` / `DB_PASS` bila MySQL Laragon kamu memakai password.

### Tanpa Laragon

```bash
cd D:\laragon\www\portofolio
D:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe -S 127.0.0.1:8080 -t .
```

Lalu buka `http://127.0.0.1:8080`. Pastikan MySQL sudah berjalan.

---

## 🔐 Login admin

| | |
|---|---|
| URL | `/admin/login.php` |
| Akun utama | `naji` |
| Akun default | `admin` — **dinonaktifkan** pada instalasi ini |

Akun `admin` / `admin123` hanya dibuat otomatis pada instalasi baru (saat tabel `admins` masih kosong). Segera buat akun pribadi dan nonaktifkan akun default melalui **Pengguna & Keamanan**.

Pada instalasi ini, akun `naji` sudah aktif dengan password yang di-hash bcrypt. Kredensial diberikan terpisah oleh pemilik proyek — jangan disimpan di dalam repository. Setelah login pertama, ganti password melalui **Profil → Keamanan akun**.

Akun `admin` sudah dinonaktifkan dan password default `admin123` sudah dihapus. Jika ingin memakainya lagi, aktifkan akunnya lalu reset password melalui **Pengguna & Keamanan** (password lama tidak akan berlaku).

**Kebijakan password:** minimal 10 karakter, minimal 3 jenis karakter (huruf kecil, huruf besar, angka, simbol), tidak boleh mengandung username, dan tidak boleh memakai password umum seperti `admin123`.

**Lockout login:** 5 percobaan gagal per akun atau 10 percobaan gagal per IP dalam 15 menit akan mengunci sementara. Semua percobaan dicatat di halaman **Pengguna & Keamanan**.

---

## 📁 Struktur singkat

```
portofolio/
├─ config.php              # kredensial DB & konstanta aplikasi
├─ index.php               # homepage
├─ project.php             # detail proyek
├─ blog.php / post.php     # daftar & detail artikel
├─ cv.php                  # resume builder / print CV
├─ api.php                 # endpoint AJAX (kontak, guestbook, reaction)
├─ inc/
│  ├─ functions.php        # DB, migrasi, auth, upload, helper, ikon
│  ├─ seed.php             # data awal
│  ├─ entities.php         # definisi field CRUD admin
│  ├─ lang.php             # kamus ID/EN
│  ├─ header.php / footer.php
│  └─ admin-header.php / admin-footer.php
├─ admin/
│  ├─ login.php, logout.php
│  ├─ index.php            # dashboard
│  ├─ profile.php          # profil, media, password
│  ├─ appearance.php       # tema, font, fitur
│  ├─ collection.php       # CRUD generik semua konten
│  ├─ messages.php         # inbox & guestbook
│  └─ users.php            # akun admin, reset password, log login
├─ assets/
│  ├─ css/style.css        # halaman publik
│  ├─ css/admin.css        # dashboard
│  ├─ js/app.js            # interaksi publik
│  ├─ js/admin.js          # interaksi admin
│  └─ img/                 # ilustrasi SVG bawaan
└─ uploads/                # file hasil upload (dibuat otomatis)
```

---

## 🎨 Mengubah tampilan

- **Warna & font:** Admin → Tampilan. Tersedia Soft Lavender (default), Quiet Sage, dan Neon Studio.
- **Dark mode:** tombol matahari/bulan di navbar. Preferensi disimpan di `localStorage`.
- **Bahasa:** tombol ID/EN di navbar. Preferensi disimpan pada cookie `lang`.
- **Urutan konten:** seret baris pada tabel admin (drag handle di kiri). Urutan disimpan via AJAX dengan CSRF.
- **Draft/publish:** setiap proyek & artikel punya status. Draft hanya bisa dilihat lewat tombol Preview saat login sebagai admin.

---

## 🖼️ Media & upload

- Format gambar: JPG, PNG, WEBP, GIF. Maksimal **5 MB**.
- CV: PDF, maksimal 5 MB.
- File disimpan di `uploads/` dengan nama acak. Folder dibuat otomatis saat upload pertama.
- `uploads/.htaccess` memblokir eksekusi skrip di dalam folder upload.
- Bila `cv_file` kosong, tombol Download CV otomatis mengarah ke `cv.php` (resume builder).

---

## 🔎 Catatan tentang data demo

- Nama, kampus, email, nomor WhatsApp, dan tautan sosial adalah **contoh fiktif**.
- Tautan demo eksternal (GitHub/LinkedIn/Instagram) hanya placeholder. Ganti melalui **Admin → Profil**.
- Username GitHub `rakapratama` dipakai untuk mencoba GitHub API. Jika akun tidak tersedia, kosong, atau API gagal, section GitHub menampilkan fallback demo (heatmap pola + proyek pilihan).
- Statistik kunjungan dihitung sederhana per hari (satu baris per tanggal).

---

## 🛡️ Keamanan

- **Password & akun**
  - Password disimpan dengan `password_hash()` (bcrypt) dan diverifikasi dengan `password_verify()`.
  - Kebijakan password kuat: minimal 10 karakter, 3 jenis karakter, tanpa username/password umum, dan tanpa pengulangan berlebihan.
  - Login memakai prepared statement, dummy hash untuk menyamakan waktu respons, dan `session_regenerate_id()` setelah berhasil.
  - Lockout berbasis database: 5 gagal per akun atau 10 gagal per IP dalam 15 menit.
  - Setiap percobaan login dicatat di tabel `login_attempts` dan dapat dilihat di **Pengguna & Keamanan**.
  - Akun bisa dibuat, direset, dinonaktifkan, dan dihapus. Sistem mencegah menonaktifkan/menghapus akun yang sedang dipakai dan menghapus akun admin terakhir.
- **Sesi**
  - Cookie `HttpOnly`, `SameSite=Lax`, dan `Secure` otomatis saat HTTPS.
  - `session.use_strict_mode`, ID sesi panjang, fingerprint User-Agent, dan idle timeout 2 jam (`SESSION_IDLE`).
  - Logout hanya melalui POST + CSRF; sesi dihancurkan dan ID diregenerasi.
- **Request & output**
  - Semua form admin dan endpoint AJAX memakai CSRF token.
  - Seluruh halaman mengirim header keamanan: CSP dengan nonce, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, COOP/CORP, dan HSTS saat HTTPS.
  - Inline script hanya dijalankan bila membawa nonce; tidak ada `onclick` inline.
  - Rich text dibersihkan dengan sanitizer berbasis DOM allowlist (`clean_html()`), bukan sekadar regex.
  - JSON di dalam `<script>` di-escape dengan `JSON_HEX_TAG/AMP/APOS/QUOT`.
- **Rate limit endpoint publik**
  - Kontak & guestbook: maksimal 3 kiriman per IP per 10 menit (ditambah throttle sesi).
  - Reaction: maksimal 60 per IP per menit.
  - Disimpan di tabel `rate_limits`, sehingga tetap berlaku meski cookie dihapus.
- **Upload & server**
  - Upload divalidasi `is_uploaded_file()`, ukuran, MIME (`finfo`), dan dimensi gambar.
  - `uploads/.htaccess` memblokir eksekusi PHP/CGI dan mematikan directory listing.
  - Root `.htaccess` memblokir akses langsung ke `config.php`, `README.md`, file `.sql/.log/.bak`, dan memberi `X-Robots-Tag: noindex` untuk halaman admin.
  - Folder `inc/` ditolak akses langsung; `robots.txt` melarang `/admin/`, `/inc/`, dan `/uploads/`.
  - Error teknis tidak ditampilkan ke publik (`APP_DEBUG = false`); detail masuk ke log server.
- **Disarankan saat deploy:** aktifkan HTTPS, ganti kredensial database, dan pastikan `APP_DEBUG` tetap `false`.

---

## 🧪 Uji cepat setelah instalasi

1. Buka homepage — loader hilang, hero & typewriter berjalan.
2. Coba filter proyek, dark mode, ID/EN, `Ctrl+K`, terminal mode, recruiter mode.
3. Kirim pesan kontak dan guestbook; cek **Admin → Inbox / Guestbook**.
4. Login admin, tambah proyek + gambar, atur urutan dengan drag, preview, lalu publish.
5. Buka detail proyek dan klik reaction.
6. Buka **Tampilan**, ganti palet/font, simpan, lalu lihat perubahan di halaman publik.
7. Buka **Pengguna & Keamanan**, buat akun uji, reset password, aktif/nonaktif, lalu hapus.
8. Buka `cv.php` dan uji print / simpan PDF.

---

## 🛠️ Troubleshooting

**Database tidak terbuat otomatis**
Pastikan MySQL berjalan dan user `root` punya izin membuat database. Cek juga `DB_HOST`/`DB_PORT`.

**Halaman blank / error 500**
Set `APP_DEBUG = true` sementara di `config.php` untuk melihat detail, lalu kembalikan ke `false`. Cek juga log Laragon dan pastikan PHP ≥ 8.1.

**Upload gagal**
Periksa izin tulis folder `uploads/`, `upload_max_filesize`, dan `post_max_size` di `php.ini`.

**GitHub stats kosong**
API publik memiliki rate limit. Buka DevTools → Network untuk melihat respons; fallback demo akan tampil jika request gagal.

**Font tidak muncul**
Google Fonts membutuhkan koneksi internet. Tanpa internet, browser memakai font sistem.

---

## 📄 Lisensi

Kode boleh dipakai dan dimodifikasi untuk portofolio pribadi. Ilustrasi SVG dibuat khusus untuk proyek ini. Konten teks demo bersifat fiktif — ganti dengan cerita dan pencapaianmu sendiri.
