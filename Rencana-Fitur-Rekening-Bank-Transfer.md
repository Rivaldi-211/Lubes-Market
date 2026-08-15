# Rencana Fitur: Daftar Rekening Bank untuk Metode Pembayaran Transfer
Proyek: Gemastik-BUMDes-Berkah-Laravel13

## 1. Masalah
Saat checkout, pembeli bisa memilih metode pembayaran **Transfer**, tetapi:
- Tidak ada pilihan bank tujuan.
- Tidak ada nomor rekening yang ditampilkan.
- Pembeli tidak tahu harus transfer ke mana sebelum mengunggah bukti pembayaran.

Ditemukan di kode:
- `app/Http/Requests/CheckoutRequest.php` — hanya memvalidasi `metode_pembayaran` sebagai salah satu dari `COD, Transfer, QRIS, Moncongloe`, tanpa detail rekening.
- `resources/views/checkout/create.blade.php` — kartu metode pembayaran Transfer hanya berupa label & ikon, tidak ada info bank/rekening.
- Tidak ditemukan tabel, model, atau kolom apa pun terkait rekening bank di `app/`, `database/`, `resources/`, `routes/`, `config/`.
- `app/Services/CheckoutService.php` menyimpan pesanan (`Pesanan::create`) tanpa referensi rekening tujuan.
- `app/Models/Pesanan.php` tidak punya kolom terkait rekening.

## 2. Solusi yang Diusulkan
Menambahkan data master **Rekening Bank BUMDes** yang dikelola admin, lalu ditampilkan ke pembeli saat memilih metode Transfer, dan disimpan sebagai snapshot pada pesanan.

### 2.1 Database
**Migration baru: `create_rekening_bank_table`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| nama_bank | string | contoh: BRI, BNI, Mandiri, BCA |
| nomor_rekening | string | |
| atas_nama | string | nama pemilik rekening (BUMDes) |
| aktif | boolean, default true | untuk tampil/tidak tampil ke pembeli |
| urutan | integer, default 0 | urutan tampil |
| timestamps | | |

**Migration tambahan: `add_rekening_bank_id_and_snapshot_to_pesanan_table`**
| Kolom baru di tabel `pesanan` | Tipe | Keterangan |
|---|---|---|
| rekening_bank_id | bigint, nullable, FK ke `rekening_bank.id` (nullOnDelete) | referensi opsional |
| rekening_bank_snapshot | string, nullable | contoh: "BRI - 1234567890 a.n. BUMDes Berkah" — disimpan agar riwayat pesanan lama tetap akurat walau data rekening admin diubah/dihapus |

### 2.2 Model
**`app/Models/RekeningBank.php`** (baru)
- `$fillable = ['nama_bank','nomor_rekening','atas_nama','aktif','urutan']`
- `casts`: `aktif` => boolean
- scope `aktif()` untuk query rekening yang tampil ke pembeli, urut berdasarkan `urutan`

**`app/Models/Pesanan.php`** (update)
- Tambah `rekening_bank_id`, `rekening_bank_snapshot` ke `$fillable`
- Tambah relasi `rekeningBank(): BelongsTo`

### 2.3 Admin — CRUD Rekening Bank
Mengikuti pola `AdminUmkmController` yang sudah ada di proyek.

**Route** (di `routes/web.php`, dalam grup `admin.` + middleware `role:admin`):
```php
Route::resource('rekening-bank', AdminRekeningBankController::class)->except('show')->names('rekening-bank');
Route::patch('/rekening-bank/{rekeningBank}/status', [AdminRekeningBankController::class,'status'])->name('rekening-bank.status');
```

**Controller baru:** `app/Http/Controllers/Admin/RekeningBankController.php`
- `index()` — daftar rekening (paginate)
- `create()` / `store()` — tambah rekening baru
- `edit()` / `update()` — ubah data rekening
- `destroy()` — hapus rekening (tolak jika masih direferensikan oleh pesanan aktif; sarankan nonaktifkan saja, mengikuti pola validasi di `UmkmController::destroy`)
- `status()` — toggle `aktif` cepat dari tabel index

**Form Request baru:** `app/Http/Requests/Admin/RekeningBankRequest.php`
```php
'nama_bank' => ['required','string','max:100'],
'nomor_rekening' => ['required','string','max:50'],
'atas_nama' => ['required','string','max:150'],
'aktif' => ['boolean'],
'urutan' => ['nullable','integer','min:0'],
```

**View baru:**
- `resources/views/admin/rekening-bank/index.blade.php` — tabel daftar rekening + tombol aktif/nonaktif, edit, hapus
- `resources/views/admin/rekening-bank/form.blade.php` — form tambah/edit, mengikuti gaya `admin/umkm/form.blade.php`

Tambahkan juga link menu "Rekening Bank" di sidebar admin (partial layout admin yang sudah ada).

### 2.4 Checkout — Sisi Pembeli
**`CheckoutController@create`**
- Kirim data `$rekeningBankList = RekeningBank::aktif()->get()` ke view `checkout.create`.

**`resources/views/checkout/create.blade.php`**
- Di bawah kartu metode pembayaran "Transfer", tampilkan daftar bank aktif sebagai radio pilihan tambahan (`name="rekening_bank_id"`), muncul/disembunyikan dengan sedikit JS saat metode "Transfer" dipilih.
- Tampilkan nomor rekening & atas nama di tiap opsi supaya pembeli bisa langsung menyalin.

**`CheckoutRequest.php`**
- Tambah validasi kondisional:
```php
'rekening_bank_id' => [
    Rule::requiredIf(fn () => $this->input('metode_pembayaran') === 'Transfer'),
    'nullable','integer',
    Rule::exists('rekening_bank','id')->where('aktif', true),
],
```

**`CheckoutService::checkout()`**
- Saat `metode_pembayaran === 'Transfer'`, ambil `RekeningBank` terpilih, lalu simpan `rekening_bank_id` dan `rekening_bank_snapshot` (format: `"{nama_bank} - {nomor_rekening} a.n. {atas_nama}"`) ke tiap `Pesanan::create(...)`.

### 2.5 Tampilan Setelah Checkout
**`resources/views/buyer/dashboard.blade.php`**
- Saat pesanan `metode_pembayaran === 'Transfer'` dan belum ada `bukti_pembayaran`, tampilkan `rekening_bank_snapshot` di atas tombol unggah bukti transfer, supaya pembeli tahu ke mana harus transfer.

**`resources/views/admin/orders/index.blade.php`**
- Tampilkan `rekening_bank_snapshot` pada detail pesanan agar admin/petugas tahu rekening tujuan yang dipakai pembeli.

### 2.6 Seeder (opsional, untuk data awal)
`database/seeders/RekeningBankSeeder.php` — isi 2–3 rekening bank BUMDes contoh (BRI, BNI, Mandiri) supaya saat demo tidak kosong.

## 3. Ringkasan File yang Akan Dibuat/Diubah
**Baru:**
- `database/migrations/xxxx_create_rekening_bank_table.php`
- `database/migrations/xxxx_add_rekening_bank_id_and_snapshot_to_pesanan_table.php`
- `app/Models/RekeningBank.php`
- `app/Http/Controllers/Admin/RekeningBankController.php`
- `app/Http/Requests/Admin/RekeningBankRequest.php`
- `resources/views/admin/rekening-bank/index.blade.php`
- `resources/views/admin/rekening-bank/form.blade.php`
- `database/seeders/RekeningBankSeeder.php` (opsional)

**Diubah:**
- `routes/web.php` (route resource admin)
- `app/Models/Pesanan.php` (fillable + relasi)
- `app/Http/Requests/CheckoutRequest.php` (validasi rekening_bank_id)
- `app/Http/Controllers/CheckoutController.php` (kirim daftar rekening ke view)
- `app/Services/CheckoutService.php` (simpan rekening_bank_id + snapshot)
- `resources/views/checkout/create.blade.php` (UI pilih bank)
- `resources/views/buyer/dashboard.blade.php` (tampilkan rekening tujuan)
- `resources/views/admin/orders/index.blade.php` (tampilkan rekening tujuan)

## 4. Langkah Eksekusi
1. Buat & jalankan migration.
2. Buat model `RekeningBank`, update model `Pesanan`.
3. Buat Form Request, Controller, View admin (CRUD).
4. Tambahkan route admin.
5. Isi seeder (opsional) lalu `php artisan db:seed --class=RekeningBankSeeder`.
6. Update `CheckoutRequest`, `CheckoutController`, `CheckoutService`.
7. Update view checkout, buyer dashboard, admin orders.
8. Uji alur: admin tambah rekening → pembeli checkout pilih Transfer → pilih bank → submit → cek data tersimpan di pesanan → cek tampil di dashboard pembeli & admin orders.

---
*Dokumen ini adalah rencana teknis. Implementasi kode dapat dikerjakan bertahap mengikuti urutan di atas.*
