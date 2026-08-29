# BMS Koperasi

Sistem manajemen warehouse dan transaksi koperasi berbasis Laravel dan Vue.js.

## Deskripsi Project

Aplikasi ini digunakan untuk mengelola operasional gudang koperasi, mulai dari data barang, supplier, pembeli, transaksi masuk dan keluar, hingga monitoring stok dan laporan sederhana.

Fungsi utama aplikasi meliputi:

- manajemen data barang
- manajemen data supplier dan pembeli
- pencatatan barang masuk
- pencatatan barang keluar
- monitoring stok barang
- penampilan log transaksi
- dashboard ringkasan operasional

## Teknologi yang Digunakan

- Backend: Laravel 12 / PHP
- Frontend: Vue 3 + Vite
- HTTP Client: Axios
- Database: MySQL / MariaDB / database yang didukung Laravel
- Package manager:
  - Composer untuk PHP
  - npm untuk JavaScript

## Struktur Project

```text
koperasi/
├── backend/         # Project Laravel API dan logic server
├── frontend/        # Project Vue.js frontend
├── README.md        # Dokumentasi project
├── .gitignore
└── .env             # (opsional, bila dibuat di root)
```

## Prasyarat Sistem

Pastikan environment berikut sudah tersedia di komputer Anda:

- PHP 8.2 atau versi yang kompatibel
- Composer
- Node.js 18+ / 20+
- npm
- MySQL atau MariaDB
- Web browser
- Git (opsional tapi disarankan)

## Instalasi dan Setup

### 1. Clone Project

```bash
git clone <url-repository>
cd koperasi
```

### 2. Install Dependency Backend

Masuk ke folder backend lalu install composer:

```bash
cd backend
composer install
```

Jika composer belum terinstall, install Composer terlebih dahulu dari:

https://getcomposer.org/download/

### 3. Setup Environment Backend

Buat file `.env` dari contoh yang ada:

```bash
copy .env.example .env
```

Jika file `.env.example` tidak ada, buat file baru dengan isi minimal berikut:

```env
APP_NAME="BMS Koperasi"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=koperasi
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

Generate key aplikasi Laravel:

```bash
php artisan key:generate
```

### 4. Setup Database

Buat database baru di MySQL:

```sql
CREATE DATABASE koperasi;
```

Lalu jalankan migrasi:

```bash
php artisan migrate
```

Jika ingin data awal contoh:

```bash
php artisan db:seed
```

### 5. Jalankan Backend

```bash
php artisan serve
```

Server backend akan berjalan pada:

```text
http://localhost:8000
```

### 6. Install Dependency Frontend

Masuk ke folder frontend dan install package:

```bash
cd ../frontend
npm install
```

Jika Node.js belum terinstall, download dari:

https://nodejs.org/

### 7. Setup Environment Frontend

Buat file `.env` di folder frontend jika diperlukan untuk konfigurasi khusus:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_ADMIN_EMAIL=admin@bms-koperasi.test
VITE_ADMIN_PASSWORD=password
```

Jika aplikasi menggunakan default login yang sudah dikodekan di frontend, Anda bisa langsung masuk tanpa `.env` tambahan. Namun tetap disarankan untuk menambahkan variabel lingkungan agar lebih mudah dikonfigurasi.

### 8. Jalankan Frontend

```bash
npm run dev
```

Frontend akan berjalan pada:

```text
http://localhost:5173
```

## Cara Menjalankan Project Secara Bersamaan

Buka dua terminal terpisah:

Terminal 1 (Backend):

```bash
cd backend
php artisan serve
```

Terminal 2 (Frontend):

```bash
cd frontend
npm run dev
```

Setelah itu, buka browser ke:

```text
http://localhost:5173
```

## Login Default

Jika token login belum ada di browser, aplikasi bisa menggunakan akun default berikut:

```text
Email: admin@bms-koperasi.test
Password: password
```

## Fitur Utama

### Dashboard
- ringkasan operasional
- total pemasukan dan pengeluaran
- keuntungan
- item dengan stok rendah
- aktivitas transaksi terbaru

### Data Barang
- tambah, edit, hapus barang
- penentuan satuan barang
- kategori tipe barang (basah / kering)

### Data Supplier & Pembeli
- simpan data mitra dan pelanggan
- dipakai untuk transaksi masuk dan keluar

### Barang Masuk
- catat pembelian produk dari supplier
- input nama supplier, barang, harga, qty, dan satuan

### Barang Keluar
- catat penjualan ke pembeli
- otomatis mengurangi stok
- menghitung total transaksi

### Stok Barang
- rekap masuk, keluar, sisa stok
- informasi harga beli dan jual
- filter dan pencarian barang

### Log Transaksi
- riwayat seluruh penjualan dan pembelian
- tombol cetak nota/kwitansi

## Build Produksi Frontend

Untuk melakukan build produksi frontend:

```bash
cd frontend
npm run build
```

Hasil build akan dibuat di folder:

```text
frontend/dist
```

## Build Produksi Backend

Untuk Laravel, biasanya server produksi dipersiapkan dengan:

```bash
cd backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Troubleshooting

### 1. Error saat `composer install`
- pastikan PHP dan Composer sudah terinstall
- cek versi PHP dengan:

```bash
php -v
```

### 2. Error koneksi database
- cek `.env` database
- pastikan MySQL server aktif
- pastikan database sudah dibuat

### 3. Frontend tidak bisa terhubung ke backend
- periksa `VITE_API_BASE_URL` atau endpoint API yang dipakai frontend
- pastikan backend sedang running di port 8000

### 4. Error `APP_KEY` belum dibuat
- jalankan:

```bash
php artisan key:generate
```

### 5. Port sudah dipakai
- ganti port dengan:

```bash
php artisan serve --port=8001
```

## Catatan Pengembangan

- Semua perubahan pada fitur transaksi sebaiknya tetap konsisten antara satuan, harga, dan stok.
- Jika ada perubahan struktur API, dokumentasi ini perlu diperbarui.
- Sangat disarankan untuk menambahkan validasi server-side di backend agar data transaksi aman dan konsisten.

## Kontribusi

Untuk pengembangan lanjutan, fokus yang umum dilakukan adalah:

- validasi stok secara lebih ketat
- pengecekan autentikasi dan otorisasi
- ekspor data ke PDF/Excel
- perbaikan UI/UX
- perbaikan laporan profit dan inventory

## Lisensi

Project ini dapat dikembangkan dan dimodifikasi sesuai kebutuhan team atau organisasi.
