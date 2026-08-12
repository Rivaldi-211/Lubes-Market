# LUDES-MARKET — Laravel

Platform Pemasaran Digital Produk Lokal BUMDes untuk Memperluas Akses Pasar dan Mendorong Kemandirian Ekonomi Desa Moncongloe Lappara. Versi ini merupakan migrasi dari aplikasi PHP Native ke Laravel dengan struktur fitur lama tetap dipertahankan dan antarmuka dirombak menjadi storefront editorial berbasis foto serta dashboard operasional untuk Admin, Penjual, dan Pembeli.

## Teknologi

- PHP 8.3 atau lebih baru
- Laravel 13.x
- MySQL 8+ atau MariaDB 10.6+
- Blade + Eloquent ORM
- Session cart
- Laravel Filesystem (`public` disk) untuk foto produk, foto UMKM, dan bukti pembayaran
- Custom CSS/JavaScript tanpa SPA framework

> Tampilan menggunakan Google Fonts, Bootstrap Icons, serta foto hero eksternal dari Unsplash. Aplikasi dan fitur tetap dapat dijalankan tanpa Node/Vite karena CSS/JS utama sudah berupa file statis di `public/assets`.

## Modul dan Fitur

### Publik

- Beranda BUMDes dengan hero foto besar, profil singkat, kategori, mitra UMKM, dan lokasi
- Katalog produk
- Pencarian berdasarkan nama produk/deskripsi/UMKM
- Filter kategori
- Pengurutan produk terbaru, harga, dan rating
- Detail produk, stok, profil UMKM, rating, dan ulasan
- Keranjang berbasis session
- Registrasi dan login

### Pembeli

- Checkout dari keranjang
- Alamat pengiriman, nomor HP, dan catatan pesanan
- Metode pembayaran: COD, Transfer, QRIS, Moncongloe
- Riwayat dan status pesanan
- Pembatalan hanya ketika status `Menunggu`
- Stok dikembalikan tepat satu kali ketika pesanan dibatalkan
- Upload bukti pembayaran untuk Transfer/QRIS
- Nota/cetak transaksi
- Ulasan 1–5 bintang untuk pesanan `Selesai`, maksimal satu ulasan per pesanan

### Penjual

- Dashboard statistik UMKM
- Profil UMKM dan foto usaha
- CRUD produk milik UMKM sendiri
- Foto produk dan manajemen stok
- Daftar pesanan khusus produk UMKM sendiri
- Perubahan status pesanan
- Bukti pembayaran dan nota
- Laporan penjualan dengan filter tanggal
- Ekspor laporan CSV

### Admin

- Dashboard statistik global
- CRUD UMKM
- Membuat akun penjual baru ketika membuat UMKM atau menghubungkan akun penjual yang belum memiliki UMKM
- CRUD semua produk
- Aktivasi/nonaktivasi akun pengguna
- Kelola semua pesanan dan status transaksi
- Melihat bukti pembayaran
- Laporan penjualan global + filter tanggal/status
- Ekspor CSV
- Log aktivitas sistem

## Instalasi dengan Migration + Seeder

### 1. Siapkan requirement PHP

Pastikan PHP menyediakan extension yang dibutuhkan Laravel dan aplikasi ini, terutama:

- `ctype`
- `filter`
- `hash`
- `mbstring`
- `openssl`
- `session`
- `tokenizer`
- `pdo_mysql`
- `fileinfo`

Pada Laragon/XAMPP, aktifkan extension tersebut dari `php.ini` bila belum aktif.

### 2. Install dependency

```bash
composer install
```

### 3. Buat file environment

Windows:

```bat
copy .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

Lalu sesuaikan konfigurasi database pada `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_bumdes_berkah
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Buat database

```sql
CREATE DATABASE db_bumdes_berkah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Jalankan migration dan demo seeder

```bash
php artisan migrate --seed
```

### 7. Buat symbolic link storage

```bash
php artisan storage:link
```

### 8. Jalankan aplikasi

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Alternatif Instalasi dengan SQL Dump

Dump siap impor tersedia di:

```text
database/dump/db_bumdes_berkah_laravel.sql
```

Contoh CLI MySQL:

```bash
mysql -u root -p db_bumdes_berkah < database/dump/db_bumdes_berkah_laravel.sql
```

Setelah dump diimpor, tetap jalankan:

```bash
composer install
php artisan key:generate
php artisan storage:link
php artisan serve
```

Tabel `migrations` sudah ada di dump sehingga `php artisan migrate` tidak akan mencoba membuat ulang tabel yang sudah diimpor.

## Akun Demo

Semua akun demo memakai password:

```text
password123
```

| Role | Username |
|---|---|
| Admin | `admin` |
| Penjual | `umkm_jalangkote` |
| Penjual | `umkm_pisangepe` |
| Penjual | `umkm_kripik` |
| Penjual | `umkm_anyaman` |
| Pembeli | `budi_pembeli` |

## Struktur Data Utama

- `users`
- `kategori`
- `umkm`
- `produk`
- `pesanan`
- `ulasan`
- `log_aktivitas`

Status bisnis yang digunakan:

- User: `aktif`, `nonaktif`
- Produk: `Ready`, `Pre-Order`, `Habis`
- Pesanan: `Menunggu`, `Diproses`, `Selesai`, `Dibatalkan`
- Pembayaran: `COD`, `Transfer`, `QRIS`, `Moncongloe`

## Keamanan dan Integritas Transaksi

- Semua aksi perubahan data menggunakan request POST/PATCH/DELETE dengan CSRF Laravel.
- Role dipisahkan oleh middleware `role` dan akun nonaktif diblok oleh middleware `active`.
- Penjual hanya dapat memodifikasi data UMKM, produk, dan pesanan miliknya.
- Checkout membaca ulang produk di dalam transaksi database dan menggunakan `lockForUpdate()` sebelum order dibuat dan stok dikurangi.
- Jika salah satu item di keranjang kehabisan stok, transaksi checkout dibatalkan seluruhnya sehingga tidak terbentuk pesanan parsial.
- Pembatalan pesanan mengembalikan stok sekali saja dan pesanan yang sudah `Dibatalkan` tidak dapat diaktifkan kembali.
- Upload dibatasi ke JPG/JPEG/PNG/WebP maksimal 2 MB dan disimpan melalui Laravel Filesystem.
- Satu pesanan hanya dapat memiliki satu ulasan melalui unique constraint `ulasan.pesanan_id`.

## Testing

Feature tests tersedia di `tests/Feature` untuk:

- schema database
- autentikasi dan role access
- katalog publik
- keranjang
- checkout transaksi/stok
- lifecycle pembeli
- operasi penjual
- operasi admin
- keamanan upload dan navigasi role

Jalankan:

```bash
php artisan test
```

Untuk environment testing berbasis SQLite, pastikan extension `pdo_sqlite` / `sqlite3` aktif. Jika tidak tersedia, gunakan environment CI/dev yang menyediakan extension tersebut.

## Deployment Catatan

- Document root web server harus diarahkan ke folder `public/`.
- Jangan upload `.env` ke repository publik.
- Jalankan `php artisan storage:link` setelah deploy.
- Untuk production gunakan `APP_ENV=production` dan `APP_DEBUG=false`.
- Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh user web server.
- Setelah konfigurasi stabil dapat menjalankan `php artisan optimize`.
