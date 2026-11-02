<div align="center">

# 🏠 HUNI — Platform Tata Kelola Kos & Hunian Modern

**Ekosistem Terpadu Manajemen Properti, Kontrak Digital, Penagihan Otomatis, dan Asisten AI untuk Pemilik serta Penghuni Kos.**

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white)](https://alpinejs.dev)
[![Pest PHP](https://img.shields.io/badge/Tests-Pest_PHP-15803D?style=for-the-badge&logo=pest&logoColor=white)](https://pestphp.com)

</div>

---

## 📖 Tentang HUNI

**HUNI** adalah platform operasional kos modern yang dirancang untuk mengatasi kompleksitas pengelolaan hunian sewa: mulai dari pencatatan okupansi kamar, penerbitan kontrak sewa digital, otomatisasi penagihan dan verifikasi pembayaran, manajemen inventaris & komplain fasilitas, hingga asisten virtual berbasis kecerdasan buatan (**HUNI AI**) yang memahami tata tertib kos secara presisi.

Aplikasi ini mengusung estetika **Dark Studio Shell** dengan kanvas arsitektural titik-titik (*deep obsidian dot grid* `#121417`), dipadukan dengan kartu konten berlatar putih presisi (*high-contrast precision cards*) dan aksen hijau hutan (*deep emerald green* `#15803D`).

---

## ✨ Fitur Utama

### 🏢 1. Portal Pemilik Kos (Owner Portal)
- **Ringkasan Finansial & Operasional:** Pantau total pendapatan bulanan, jumlah tagihan tertunda (*pending invoices*), okupansi kamar aktif, dan log aktivitas real-time.
- **Manajemen Properti & Kamar:** Kelola banyak properti, konfigurasi tipe kamar (Standard, Deluxe, VIP), harga sewa, deposit, serta fasilitas tiap kamar.
- **Undangan Sewa & Kontrak Digital:** Buat kode undangan sewa dengan masa berlaku, verifikasi otomatis saat diklaim calon penghuni, dan arsipkan kontrak digital secara terstruktur.
- **Penagihan & Invoice Berkala:** Otomatisasi generate tagihan sewa bulanan dan biaya utilitas/listrik tambahan.
- **Verifikasi Pembayaran:** Tinjau bukti transfer yang diunggah penghuni dengan alur validasi persetujuan (*approval*) atau penolakan disertai catatan.
- **Pusat Komplain & Tiket:** Pantau kendala fasilitas yang dilaporkan penghuni lengkap dengan foto dokumentasi kerusakan dan status pengerjaan (*Menunggu*, *Diproses*, *Selesai*).
- **Inventaris & Fasilitas:** Registrasi barang/aset di setiap kamar dan fasilitas bersama kos.
- **Laporan & Finansial:** Laporan laba/rugi, arus kas, dan fitur cetak laporan (*Print-ready Report*).

### 🏠 2. Portal Penghuni Kos (Tenant Portal)
- **Beranda Penghuni:** Informasi kamar aktif, nomor kontrak sewa, periode jatuh tempo, dan ringkasan status pembayaran.
- **Tagihan & Bayar:** Riwayat invoice terbit, rincian pembayaran, dan form upload bukti transfer langsung dari perangkat.
- **Lapor Kendala & Tiket Komplain:** Laporkan kerusakan fasilitas (lampu mati, AC rusak, air bocor) lengkap dengan foto pendukung.
- **Asisten HUNI AI:** Chatbot cerdas terintegrasi untuk menjawab pertanyaan penghuni seputar aturan kos, jam bertamu, tata tertib parkir, dan tata cara pembayaran.

### 🎨 3. Desain Antarmuka Modern & Responsif
- **Dark Studio Shell:** Sidebar & topbar berwarna obsidian gelap (`#16191E`) yang elegan dan nyaman di mata.
- **Active Navigation Light Button:** Tab menu yang sedang aktif tampil dengan latar putih terang kontras dan ikon beraksen hijau matang (`#15803D`).
- **Architectural Dot Canvas:** Latar belakang utama menggunakan pola titik-titik citron di atas warna hitam pekat (`#121417`).
- **Mobile Drawer Nav:** Drawer navigasi responsif yang halus dan mudah diakses di ponsel pintar maupun tablet.

---

## 🛠️ Tech Stack & Spesifikasi

| Lapisan | Teknologi |
|---|---|
| **Framework Backend** | [Laravel 13.x](https://laravel.com) |
| **Runtime Bahasa** | [PHP 8.5+](https://php.net) |
| **Styling & CSS** | [Tailwind CSS v4](https://tailwindcss.com) & Vite |
| **Interaktivitas Frontend** | [Alpine.js 3.x](https://alpinejs.dev) |
| **Grafik & Visualisasi** | [ApexCharts.js](https://apexcharts.com) |
| **Pengujian (Testing)** | [Pest PHP](https://pestphp.com) (Feature, Unit, Integration) |
| **Database** | SQLite (Default Dev) / MySQL / PostgreSQL |
| **Code Formatter** | [Laravel Pint](https://laravel.com/docs/pint) |

---

## 🚀 Panduan Instalasi Lokal

Ikuti langkah-langkah berikut untuk menjalankan HUNI App di komputer lokal Anda:

### 1. Kloning Repositori
```bash
git clone https://github.com/simply-aksan/huniapp.git
cd huniapp
```

### 2. Pasang Dependensi PHP & JavaScript
```bash
# Pasang paket Composer
composer install

# Pasang paket Node.js
npm install
```

### 3. Konfigurasi Lingkungan (`.env`)
Salin file konfigurasi contoh dan buat application key:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Setup Database & Seeder
Buat database SQLite dan jalankan migrasi beserta data awal pengujian:
```bash
# Buat file database sqlite jika belum ada
touch database/database.sqlite

# Jalankan migrasi dan seeder
php artisan migrate --seed
```

### 5. Kompilasi Aset Frontend
```bash
npm run build
# Atau untuk mode development dengan hot reload:
npm run dev
```

### 6. Jalankan Server Lokal
```bash
php artisan serve
```
Akses aplikasi melalui peramban di `http://127.0.0.1:8000` (atau `http://huniapp.test` jika menggunakan Laravel Herd/Valet).

---

## 🔑 Akun Demo Pengujian

Setelah menjalankan `php artisan db:seed`, Anda dapat langsung menggunakan akun demo berikut:

| Peran (Role) | Email | Kata Sandi | Halaman Utama |
|---|---|---|---|
| **Pemilik Kos (Owner)** | `aksan@huniapp.test` | `password` | `/owner` |
| **Penghuni Kos (Tenant)** | `budi@huniapp.test` | `password` | `/tenant` |

> *Tip: Halaman login dilengkapi dengan tombol **Akses Cepat Pengujian (Quick Demo)** untuk mengisi kredensial dengan satu klik.*

---

## 🧪 Menjalankan Pengujian (Automated Testing)

Aplikasi HUNI memiliki cakupan pengujian otomatis yang komprehensif menggunakan **Pest PHP**:

```bash
# Menjalankan seluruh test suite
vendor/bin/pest

# Menjalankan dengan mode compact
php artisan test --compact

# Menjalankan pengujian fitur spesifik
vendor/bin/pest tests/Feature/AuthenticationTest.php
vendor/bin/pest tests/Feature/Phase7FinalQATest.php
```

---

## 📁 Struktur Direktori Penting

```plaintext
huniapp/
├── app/
│   ├── Http/Controllers/     # Controller Owner, Tenant, Auth, & Komplain
│   ├── Models/               # Model Eloquent (User, Property, Room, Contract, Bill, dll.)
│   ├── Notifications/        # Notifikasi in-app dan email
│   └── Policies/             # Kebijakan otorisasi per peran
├── database/
│   ├── factories/            # Model factories untuk testing
│   ├── migrations/           # Skema tabel database
│   └── seeders/              # Seeder data awal & demo
├── resources/
│   ├── css/                  # Styling kustom & token tema Tailwind v4
│   ├── js/                   # Skrip JavaScript & inisialisasi ApexCharts
│   └── views/
│       ├── auth/             # View Autentikasi (Login terpusat)
│       ├── components/       # Komponen Blade reusable (Sidebar, Topbar, UI cards, dll.)
│       ├── layouts/          # Base, Owner, dan Tenant layout wrappers
│       ├── owner/            # Halaman-halaman portal pemilik kos
│       └── tenant/           # Halaman-halaman portal penghuni kos
├── routes/
│   └── web.php               # Rute aplikasi dengan middleware proteksi peran
└── tests/
    └── Feature/              # Pengujian fitur, otorisasi, dan validasi fungsional
```

---

## 👨‍💻 Penulis & Lisensi

- **Author / Developer:** [simply-aksan](https://github.com/simply-aksan)
- **Lisensi:** Open-source software berlisensi [MIT License](LICENSE).
