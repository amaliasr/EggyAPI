# Egg Farm API — Backend (Fase 1)

REST API Laravel untuk aplikasi pencatatan produksi & penjualan telur peternakan. Paket ini berisi **kode aplikasi** (migration, model, controller, service, repository, routes, test) untuk ditumpangkan di atas instalasi Laravel baru — bukan project Laravel lengkap (vendor/, bootstrap cache, dsb tidak disertakan, sesuai praktik standar).

## Kenapa harus "bikin Laravel baru" padahal kode sudah ada?

Ini pertanyaan wajar, jadi penting dijelaskan sekali di sini:

Kode yang saya buatkan (folder `app/`, `database/`, `routes/api.php`, dst di paket ini) adalah **logika khusus aplikasi Anda** — aturan bisnis telur, tabel database, endpoint API. Ini yang saya tulis satu per satu.

Yang **belum ada** di paket ini adalah **mesin Laravel itu sendiri**: folder `vendor/` (kode inti framework Laravel + semua library pendukung — ribuan file, puluhan ribu baris, ukurannya bisa >50 MB), file `artisan`, `public/index.php`, dan file konfigurasi bawaan lainnya. Ini bukan sesuatu yang saya "tulis" — ini didownload otomatis oleh Composer dari server resmi Laravel setiap kali orang membuat project baru, sama seperti Anda mengunduh MS Word dulu sebelum bisa membuka file `.docx`.

Alasan tidak saya sertakan langsung:
- **Sama untuk semua project** — isi `vendor/` bukan kode custom, jadi tidak ada gunanya saya tulis ulang; itu murni hasil download.
- **Ukurannya besar sekali** dan akan membuat paket ini jauh lebih berat tanpa manfaat.
- **Praktik standar industri** — bahkan di perusahaan software sungguhan, folder `vendor/` **tidak pernah** disertakan/di-commit ke Git; selalu di-generate ulang lewat Composer di komputer masing-masing developer.

Jadi alurnya: `composer create-project` mengunduh "mesin" Laravel kosong → kode di paket ini ditumpuk di atasnya sebagai "isi/logika bisnis"nya. Dua-duanya sama-sama wajib.

**Kabar baiknya:** Anda tidak perlu melakukan penyalinan file manual satu-satu. Jalankan `install.bat` (lihat di bawah) — skrip ini mengerjakan seluruh proses (buat project, salin kode, pasang dependency, siapkan `.env`, migrasi) secara otomatis, tinggal isi kredensial database saat diminta.

## Cara termudah: `install.bat` (Windows)

1. Extract (klik kanan → "Extract All...") file zip paket ini ke lokasi mana saja, misalnya `D:\Programming\Eggy\`. Hasil extract adalah sebuah folder bernama **`egg-farm-api`** yang isinya `install.bat`, `app/`, `database/`, `composer.json`, dst — folder inilah **paket kode**, bukan project yang dijalankan.
2. Pastikan `composer` dan `php` sudah bisa dipanggil dari terminal (`composer -V` dan `php -v` harus menampilkan versi, bukan error "not recognized"). Untuk XAMPP, tambahkan `C:\xampp\php` ke PATH Windows dulu bila belum.
3. Masuk ke DALAM folder `egg-farm-api` hasil extract tadi, lalu **double-click `install.bat` di situ** (atau klik kanan → "Run as administrator" bila muncul masalah izin).
4. Ikuti instruksi di layar — skrip akan berhenti sejenak dan minta Anda mengisi `.env` (kredensial MySQL) sebelum lanjut migrasi.
5. Skrip membuat project Laravel baru **di sebelah** folder paket tadi (bukan di dalamnya), dengan nama **`egg-farm-api-app`**. Jadi kalau paket ada di `D:\Programming\Eggy\egg-farm-api\`, project sesungguhnya akan muncul di `D:\Programming\Eggy\egg-farm-api-app\` — **folder inilah yang Anda buka di editor / jalankan `php artisan serve` selanjutnya**, bukan folder `egg-farm-api` yang asli (folder itu boleh diabaikan/dihapus setelah instalasi berhasil).

Jika `install.bat` gagal di tengah jalan, pesan error di layar akan menunjukkan di langkah mana — lanjutkan manual dari situ mengikuti langkah-langkah di bagian "Instalasi" di bawah, atau cek "Troubleshooting instalasi".

## Cakupan Fase 1

Login (Sanctum), RBAC (`atasan` / `karyawan`), manajemen akun karyawan (CRUD + aktif/nonaktif), audit log dasar, serta **seluruh migration & model untuk 23 tabel** (struktur database final sesuai dokumen rancangan — modul berikutnya tinggal menambah controller/service, tanpa mengubah skema). Data contoh (§17 dokumen rancangan) sudah termasuk di seeder.

## Versi Laravel yang dipakai

Paket ini menyasar **Laravel 12** (bukan 11, bukan 13). Alasannya: per September 2026, Laravel 11 sudah lewat masa dukungan keamanan (berakhir 12 Maret 2026) — Composer versi baru akan **menolak instalasi** paket itu karena dianggap rawan (lihat Troubleshooting §"Problem 1" di bawah bila Anda mengalaminya). Laravel 13 sudah keluar tapi mewajibkan PHP ^8.3, sementara instalasi PHP yang umum lewat XAMPP saat ini masih PHP 8.2. Laravel 12 (PHP 8.2–8.5, dukungan keamanan sampai 24 Februari 2027) adalah titik paling cocok: aman, dan tidak memaksa Anda upgrade PHP dulu. Jika PHP Anda sudah 8.3+, silakan pakai Laravel 13 — kode di paket ini tetap kompatibel karena tidak memakai fitur khusus versi tertentu.

## Instalasi manual (langkah demi langkah, kalau tidak pakai `install.bat`)

```bash
# 1. Buat project Laravel baru — SEBUT VERSI-nya, jangan biarkan default
#    (default composer create-project akan mengambil versi terbaru = Laravel 13,
#    yang mewajibkan PHP 8.3+)
composer create-project laravel/laravel egg-farm-api "12.*"
cd egg-farm-api

# 2. Salin seluruh isi paket ini ke dalam project (timpa file yang sama)
#    - app/            → app/
#    - database/       → database/
#    - routes/api.php  → routes/api.php
#    - bootstrap/app.php → bootstrap/app.php
#    - composer.json   → gabungkan bagian "require" ke composer.json project
#    - .env.example    → .env.example
#    - phpunit.xml     → phpunit.xml

# 3. Install dependency tambahan
composer require laravel/sanctum "kreait/laravel-firebase:7.*" barryvdh/laravel-dompdf maatwebsite/excel ramsey/uuid

# 4. Siapkan .env
cp .env.example .env
php artisan key:generate
# isi DB_DATABASE, DB_USERNAME, DB_PASSWORD sesuai MySQL Anda (buat database "egg_farm" dahulu)

# 5. Migrasi + data contoh
php artisan migrate --seed

# 6. Jalankan
php artisan serve
```

Semua file PHP sudah dicek `php -l` (73 file, tanpa error sintaks) sebelum dikirim.

## Troubleshooting instalasi

**Problem 1 — Composer menolak install karena "affected by security advisories" (semua versi laravel/framework ^11.0 diblokir):**
Ini bukan bug — Laravel 11 memang sudah kedaluwarsa keamanannya. Solusi yang benar adalah **pindah ke Laravel 12** (sudah dilakukan di `composer.json` paket ini), bukan mematikan pemblokirannya (`policy.advisories.block: false`) — mematikan itu artinya Anda sengaja memasang framework yang diketahui rawan.

**Problem 2 — `phpoffice/phpspreadsheet requires ext-gd * -> it is missing from your system`:**
Ekstensi `gd` PHP belum aktif. Di XAMPP:
1. Buka `C:\xampp\php\php.ini` dengan teks editor.
2. Cari baris `;extension=gd`, hapus tanda titik-koma di depannya jadi `extension=gd`.
3. Simpan, lalu restart Apache (dan restart terminal/Command Prompt Anda) supaya `php.ini` yang baru terbaca.
4. Cek dengan `php -m | findstr gd` (Windows) — harus muncul `gd`.
5. Jalankan lagi `composer install`/`composer require`.

**Problem 3 — `Could not parse version constraint ^^12.0: Invalid version string "^^12.0"` (tanda `^` jadi dobel):**
Ini bukan masalah versi Laravel, tapi cara Command Prompt/`.bat` Windows memproses karakter `^` (di Windows, `^` punya arti khusus untuk "escape", jadi kadang dobel saat diteruskan ke Composer). Perbaikannya: **ganti tanda `^12.0` dengan `12.*`** (artinya sama persis — "versi 12.x manapun") — ini yang sudah dipakai di `install.bat` versi terbaru dan di contoh perintah pada README ini. Kalau Anda mengetik perintah composer secara manual dan menemukan error serupa untuk paket lain, ganti pola `^X.Y` menjadi `X.*` dengan cara yang sama.

**Kenapa `maatwebsite/excel` tetap `^3.1`, bukan versi terbaru (4.x)?**
Versi 4.x package ini mewajibkan PHP ^8.3. Versi 3.1.x (masih dirawat, mendukung sampai Laravel 13) tidak mewajibkan itu, jadi tetap jalan di PHP 8.2 seperti instalasi XAMPP Anda.

**Setelah `composer install`/`composer require` berhasil sekali:** commit atau simpan file `composer.lock` yang terbentuk — ini mengunci versi persis yang sudah teruji supaya instalasi berikutnya (di server hosting, atau oleh anggota tim lain) tidak menarik ulang versi terbaru yang mungkin berbeda.

## Akun contoh (dari seeder — ganti password setelah instalasi)

| Role | Email | Password |
|---|---|---|
| Atasan | amaliayuya@gmail.com | password |
| Karyawan | budi@eggyapps.test | password |
| Karyawan | sari@eggyapps.test | password |
| Karyawan | andi@eggyapps.test | password |

## Menjalankan test

```bash
php artisan test
# atau: ./vendor/bin/phpunit
```

Test memakai SQLite in-memory (lihat `phpunit.xml`) — tidak menyentuh database MySQL Anda.

## Endpoint API — 14 modul lengkap (per 24 September 2026)

Semua endpoint di bawah butuh `Authorization: Bearer <token>` kecuali `/auth/login`. `(atasan)` berarti hanya role atasan; tanpa keterangan berarti kedua role bisa akses (data karyawan otomatis dibatasi ke miliknya sendiri di kode, kecuali atasan yang melihat semua).

```
Auth
  POST   /api/v1/auth/login
  POST   /api/v1/auth/logout
  GET    /api/v1/auth/me
  POST   /api/v1/auth/fcm-token

Karyawan (atasan)
  GET|POST      /api/v1/employees
  GET|PUT       /api/v1/employees/{id}
  PATCH         /api/v1/employees/{id}/status

Master data — kategori telur & sumber kandang
  GET                    /api/v1/egg-categories
  GET                    /api/v1/egg-categories/{id}
  POST|PUT (atasan)      /api/v1/egg-categories[/{id}]
  PATCH (atasan)         /api/v1/egg-categories/{id}/status
  GET                    /api/v1/farm-sources
  GET                    /api/v1/farm-sources/{id}
  POST|PUT (atasan)      /api/v1/farm-sources[/{id}]
  PATCH (atasan)         /api/v1/farm-sources/{id}/status

Harga Telur — price snapshot, tanpa cron (activate-due dijalankan otomatis tiap list/active dipanggil)
  GET             /api/v1/prices
  GET             /api/v1/prices/active
  GET             /api/v1/prices/{id}
  POST (atasan)   /api/v1/prices
  PUT (atasan)    /api/v1/prices/{id}          (hanya status terjadwal)
  DELETE (atasan) /api/v1/prices/{id}          (batalkan jadwal)
  POST (atasan)   /api/v1/prices/activate-due  (pemicu manual opsional)

Produksi Harian
  GET|POST  /api/v1/daily-productions
  GET|PUT   /api/v1/daily-productions/{id}
  PATCH (atasan) /api/v1/daily-productions/{id}/lock

Pengambilan Telur — mengurangi stok terpusat saat dibuat
  GET|POST  /api/v1/egg-pickups
  GET       /api/v1/egg-pickups/{id}

Pelanggan — autocomplete/quick-add dari form Penjualan & Retur, semua role boleh cari & tambah
  GET        /api/v1/customers?search=budi
  POST       /api/v1/customers
  GET|PUT    /api/v1/customers/{id}

Retur — DUA jalur, boleh dipakai berdampingan (isi salah satu: egg_pickup_id ATAU sale_id)
  GET|POST  /api/v1/egg-returns
  GET       /api/v1/egg-returns/{id}
  · Jalur lama (egg_pickup_id): sisa telur dibawa keliling yang tidak laku, dikembalikan ke gudang.
  · Jalur baru (sale_id): pelanggan sudah beli (tercatat di sales) lalu komplain/mengembalikan
    sebagian/semua. Terima customer_id ATAU new_customer (quick-add) seperti di Penjualan;
    kalau tidak diisi sama sekali, ikut pelanggan yang sudah tercatat di transaksi aslinya.
    refund_amount hanya dicatat sebagai informasi — TIDAK otomatis mengubah total_amount/
    paid_amount pada Sale (keputusan refund tetap manual).

Penjualan — harga snapshot dari harga aktif saat transaksi dibuat
  GET|POST  /api/v1/sales
  GET       /api/v1/sales/{id}
  POST      /api/v1/sales/{id}/payments
  · Bisa disertai customer_id (pelanggan yang sudah tercatat) ATAU new_customer: {name, phone}
    untuk menambah pelanggan baru sekalian saat transaksi dibuat (quick-add). Kalau dua-duanya
    kosong, tetap boleh diisi customer_name/customer_phone bebas (walk-in, tidak tercatat).

Stok — ledger, tidak pernah diedit langsung
  GET             /api/v1/stock/balances
  GET             /api/v1/stock/movements
  POST (atasan)   /api/v1/stock/adjustments

Insentif — 4 metode (berat, persentase_omzet, peringkat, target)
  GET               /api/v1/my-incentives                              (insentif milik sendiri)
  GET|POST (atasan) /api/v1/incentive-programs
  GET|PUT (atasan)  /api/v1/incentive-programs/{id}
  PATCH (atasan)    /api/v1/incentive-programs/{id}/status
  POST (atasan)     /api/v1/incentive-programs/{id}/calculate           (hitung ulang estimasi, aman diulang)
  POST (atasan)     /api/v1/employee-incentives/{id}/approve
  POST (atasan)     /api/v1/employee-incentives/{id}/pay

Target Penjualan
  GET            /api/v1/sales-targets
  GET            /api/v1/sales-targets/{id}        (termasuk progres tercapai vs target)
  POST|PUT|DELETE (atasan) /api/v1/sales-targets[/{id}]

Laporan (read-only, wajib date_from & date_to)
  GET             /api/v1/reports/production
  GET             /api/v1/reports/sales
  GET             /api/v1/reports/stock
  GET (atasan)    /api/v1/reports/incentive-programs/{id}

Pengumuman
  GET                /api/v1/announcements
  GET                /api/v1/announcements/{id}
  POST|PUT (atasan)  /api/v1/announcements[/{id}]
  PATCH (atasan)     /api/v1/announcements/{id}/status

Notifikasi (milik sendiri)
  GET    /api/v1/notifications
  PATCH  /api/v1/notifications/{id}/read
  POST   /api/v1/notifications/mark-all-read

Koreksi — ajukan siapa saja, tinjau atasan (persetujuan TIDAK otomatis menerapkan perubahan — lihat catatan di CorrectionRequestService)
  GET|POST        /api/v1/correction-requests
  GET             /api/v1/correction-requests/{id}
  POST (atasan)   /api/v1/correction-requests/{id}/review

Pengaturan & Audit Log (atasan)
  GET|PUT  /api/v1/settings[/{key}]
  GET      /api/v1/audit-logs
```

**Catatan jujur soal modul Insentif**: rumus 4 metode di `IncentiveService::calculate()` adalah interpretasi terbaik saya dari ringkasan keputusan desain yang tersimpan ("4 metode: berat, persentase omzet, bonus peringkat, target") — bukan disalin ulang dari rumus detail dokumen "Analisis & Rancangan" Tahap 1 (dokumen itu tidak ikut terbaca ulang saat modul ini ditulis). Sebelum dipakai menghitung insentif sungguhan, cocokkan sekali lagi dengan dokumen aslinya; kalau ada perbedaan, gampang disesuaikan karena logikanya terkumpul di satu method.

**Catatan soal modul Koreksi**: menyetujui (`disetujui`) permintaan koreksi HANYA mengubah status permintaannya, tidak otomatis mengedit data asli yang dirujuk (`reference_type`/`reference_id`) — itu sengaja, supaya tidak ada mass-assignment otomatis ke sembarang model berdasarkan input klien. Atasan menerapkan perubahannya secara manual lewat endpoint modul terkait setelah menyetujui.

**Catatan soal modul Pelanggan & retur berbasis penjualan**: fitur ini hanya ada di database/kode `egg-farm-api` (proyek REST API ini). Aplikasi web (Livewire) dibuat di sesi Claude yang terpisah dan — sepengetahuan saya — mengakses Eloquent-nya sendiri langsung ke database, bukan lewat REST API ini. Apakah tabel `customers` yang baru ini otomatis "terlihat" oleh aplikasi web tersebut tergantung apakah kedua proyek memakai **database yang sama** atau terpisah — itu pertanyaan arsitektur dari sesi lain yang belum terjawab. Kalau keduanya memakai database yang sama, migrasi ini otomatis berlaku untuk web app juga; kalau terpisah, web app perlu pembaruan sendiri (model, form, dst) di sesi/proyeknya masing-masing.

## Arsitektur

Controller tetap tipis → memanggil **Service** (`app/Services`) untuk logika bisnis. Modul Karyawan memakai **Repository** terpisah (`app/Repositories`) sebagai pola awal dari Fase 1; modul-modul berikutnya (harga, produksi, stok, dst) sengaja langsung memakai Eloquent di dalam Service tanpa lapisan Repository/Interface tambahan — lebih ringkas untuk 11 modul sekaligus, tanpa mengurangi fitur apa pun. Ledger stok terpusat ada di satu tempat (`StockService`) dan dipakai bersama oleh modul Produksi, Pengambilan, dan Retur supaya aturan "stok dari pergerakan, bukan kolom saldo" konsisten di semua modul.
