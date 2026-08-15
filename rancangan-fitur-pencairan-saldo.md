# Rancangan Fitur Pencairan Saldo Penjual
**Project:** Gemastik BUMDes Berkah (Laravel 13)
**Tipe pencairan:** Manual — penjual mengajukan, admin transfer manual & menandai selesai
**Aturan saldo cair:** Setelah pesanan berstatus **Selesai** (diterima pembeli)

---

## 1. Kondisi Existing (hasil audit kode)

Project ini **sudah punya fondasi sebagian** dari fitur ini:

| Komponen | Status |
|---|---|
| Tabel `disbursements` + pivot `disbursement_pesanan` | ✅ Sudah ada |
| Model `Disbursement` | ✅ Sudah ada |
| `Admin\DisbursementController` (admin mencairkan saldo semua UMKM) | ✅ Sudah ada |
| Kolom `pendapatan_penjual`, `komisi_admin` di tabel `pesanan` | ✅ Sudah ada |
| Sisi **penjual** untuk mengajukan permintaan pencairan | ❌ Belum ada |
| Row locking / anti double-disbursement | ❌ Belum ada |
| Validasi jumlah dihitung ulang di server saat approve | ⚠️ Sebagian (dihitung server, tapi tanpa lock) |

**Kesimpulan:** ini bukan fitur dari nol — tinggal menambahkan alur "request oleh penjual" di atas fondasi admin yang sudah ada, sambil menutup celah keamanan pada logika pencairan yang sudah ada.

---

## 2. Alur Fitur (Flow)

1. **Penjual** buka halaman **"Saldo Saya"**
   - Sistem hitung saldo tersedia = `SUM(pendapatan_penjual)` dari pesanan berstatus `Selesai` milik UMKM tsb yang **belum** pernah masuk ke disbursement manapun.
2. Penjual pilih rekening bank tujuan (harus rekening miliknya sendiri & berstatus aktif) → klik **"Ajukan Pencairan"**.
3. Sistem membuat record `disbursements` baru dengan status `diajukan`, mengunci (attach) pesanan-pesanan yang jadi dasar perhitungan saat itu juga (di dalam transaksi + row lock) supaya tidak bisa dipakai dua kali oleh pengajuan lain.
4. **Admin** melihat daftar permintaan masuk (status `diajukan`).
5. Admin transfer dana **secara manual** di luar sistem (mis. lewat m-banking).
6. Admin kembali ke sistem → klik **"Tandai Dibayar"** → status berubah `dibayar`, `dibayar_at` terisi, `admin_id` tercatat.
   - Atau klik **"Tolak"** → status `ditolak`, pesanan yang tadi terkunci dilepas kembali (bisa diajukan ulang lain waktu).
7. Semua aksi (ajukan, approve, tolak) tercatat lewat `ActivityLogger` yang sudah dipakai di seluruh project.

---

## 3. Perubahan Skema Database

### Migration baru: `add_request_fields_to_disbursements_table`

Tambahan kolom pada tabel `disbursements` yang sudah ada:

```php
$table->unsignedBigInteger('rekening_bank_id')->nullable()->after('umkm_id');
$table->foreign('rekening_bank_id')->references('id')->on('rekening_bank')->nullOnDelete();
$table->json('rekening_bank_snapshot')->nullable()->after('rekening_bank_id'); // simpan salinan no rekening & nama saat diajukan, antisipasi rekening diubah/dihapus setelah pengajuan
$table->unsignedBigInteger('requested_by')->nullable()->after('admin_id');
$table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
$table->timestamp('diajukan_at')->nullable()->after('catatan');
$table->timestamp('ditolak_at')->nullable()->after('dibayar_at');
```

### Perubahan nilai `status`

Sebelumnya hanya dipakai admin dengan default `'dibayar'`. Perluas jadi state machine:

- `diajukan` → permintaan baru dari penjual, menunggu admin
- `diproses` *(opsional)* → admin sedang mengurus transfer
- `dibayar` → transfer selesai, dana sudah dikirim
- `ditolak` → ditolak admin, pesanan dilepas kembali agar bisa diajukan ulang

### Constraint penting

- **Satu pesanan hanya boleh terkait ke satu disbursement yang masih aktif** (status `diajukan`/`diproses`/`dibayar`). Saat `ditolak`, pivot row dihapus supaya pesanan itu "bebas" lagi.
- **Satu UMKM hanya boleh punya maksimal satu disbursement berstatus `diajukan`/`diproses` dalam satu waktu** — dicegah di level aplikasi (cek sebelum insert, di dalam transaksi + lock) agar tidak dobel ajuan.

---

## 4. Perubahan Kode (Backend)

### 4.1 `app/Models/Disbursement.php`
Tambahkan relasi `rekeningBank()`, cast `rekening_bank_snapshot` ke `array`, dan helper `isFinal()` / scope `pending()`.

### 4.2 Controller baru: `app/Http/Controllers/Seller/SaldoController.php`

```
index()  -> GET  /penjual/saldo         : tampilkan saldo tersedia + riwayat pencairan UMKM sendiri
store()  -> POST /penjual/saldo/ajukan  : buat pengajuan baru
```

Logika inti `store()` — **wajib** dalam `DB::transaction()`:

1. Ambil `umkm` dari `$request->user()->umkm`. Kalau tidak ada → tolak.
2. **Cek tidak ada pengajuan aktif** untuk umkm ini (`diajukan`/`diproses`) — kalau ada, tolak dengan pesan jelas.
3. Query pesanan eligible **dengan `lockForUpdate()`**:
   ```php
   $pesanan = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
       ->where('status', 'Selesai')
       ->whereDoesntHave('disbursements', fn($q) =>
           $q->whereIn('status', ['diajukan', 'diproses', 'dibayar']))
       ->lockForUpdate()
       ->get();
   ```
4. Kalau kosong → tolak ("tidak ada saldo yang bisa dicairkan").
5. **Jumlah dihitung dari data server**, bukan dari input form: `$jumlah = $pesanan->sum('pendapatan_penjual')`.
6. Validasi rekening bank tujuan: `$rekeningBank->umkm_id === $umkm->id && $rekeningBank->aktif === true` — kalau tidak, tolak (403/validation error), **jangan** percaya `rekening_bank_id` mentah dari request tanpa cek kepemilikan.
7. Buat `Disbursement` status `diajukan`, simpan `rekening_bank_snapshot` (copy nama bank + no rekening saat ini, agar histori tidak berubah walau rekening diedit belakangan).
8. `attach()` semua pesanan ke disbursement ini — di dalam transaksi yang sama, sehingga row lock di langkah 3 mencegah pesanan yang sama dipakai request lain yang jalan bersamaan.
9. Log via `ActivityLogger`.

### 4.3 Request class baru: `app/Http/Requests/Seller/AjukanPencairanRequest.php`
Validasi minimal: `rekening_bank_id` wajib ada & `exists:rekening_bank,id`. **Tidak menerima field `jumlah` dari user sama sekali** — dihitung 100% di server.

### 4.4 Update `app/Http/Controllers/Admin/DisbursementController.php`

Ubah `store()` (alur admin cairkan langsung) dan tambah 2 aksi baru:

```
approve(Disbursement $disbursement)  -> POST /admin/disbursement/{id}/setujui
                                          -> tandai status 'dibayar', isi dibayar_at & admin_id
reject(Disbursement $disbursement)   -> POST /admin/disbursement/{id}/tolak
                                          -> detach semua pesanan, status 'ditolak', ditolak_at
```

Pastikan `approve()`/`reject()` juga dibungkus `DB::transaction()` + cek `status === 'diajukan'` sebelum diubah (mencegah klik ganda / approve dua kali dari 2 tab admin).

### 4.5 Routes (`routes/web.php`)

Tambahkan di dalam grup `prefix('penjual')->name('seller.')->middleware(['role:penjual', EnsureSellerVerified::class])` yang sudah ada:

```php
Route::get('/saldo', [Seller\SaldoController::class, 'index'])->name('saldo.index');
Route::post('/saldo/ajukan', [Seller\SaldoController::class, 'store'])->name('saldo.ajukan')
    ->middleware('throttle:5,1'); // rate limit anti-spam klik
```

Di grup admin, tambahkan:

```php
Route::post('/disbursement/{disbursement}/setujui', [Admin\DisbursementController::class, 'approve'])->name('disbursement.approve');
Route::post('/disbursement/{disbursement}/tolak', [Admin\DisbursementController::class, 'reject'])->name('disbursement.reject');
```

### 4.6 View baru (Blade)

- `resources/views/seller/saldo/index.blade.php` — kartu saldo tersedia, tombol ajukan (disabled kalau sudah ada pengajuan pending), tabel riwayat pencairan UMKM sendiri.
- Update `resources/views/admin/disbursement/index.blade.php` — tambah tab/section "Permintaan Masuk" (status `diajukan`) dengan tombol Setujui/Tolak, terpisah dari histori yang sudah selesai.

---

## 5. Checklist Keamanan (wajib dicek sebelum go-live)

- [ ] Semua perhitungan **jumlah saldo** dilakukan di server, tidak pernah menerima `jumlah` dari input form.
- [ ] `DB::transaction()` + `lockForUpdate()` dipakai di **setiap** titik yang mengklaim/melepas pesanan (ajukan, approve, reject) — mencegah *double disbursement*.
- [ ] Ownership check: penjual hanya bisa ajukan/lihat pencairan miliknya sendiri (pola `own()` seperti di `RekeningBankController`).
- [ ] Rekening bank tujuan divalidasi milik UMKM yang sama & `aktif = true`, bukan sekadar ID yang valid.
- [ ] Maksimal 1 pengajuan aktif (`diajukan`/`diproses`) per UMKM pada satu waktu.
- [ ] Rate limiting (`throttle`) pada endpoint pengajuan untuk cegah spam/double-submit.
- [ ] Tombol submit di-disable setelah diklik (UX layer, bukan pengganti validasi server).
- [ ] Semua aksi (ajukan, setujui, tolak) tercatat di `ActivityLogger` dengan user & IP.
- [ ] Middleware `role:penjual` + `EnsureSellerVerified` diterapkan di route saldo, sama seperti fitur seller lain.
- [ ] Route model binding `Disbursement`/`Umkm` di controller admin & seller tetap diikuti pengecekan kepemilikan eksplisit (jangan andalkan binding saja).
- [ ] Status transisi divalidasi (`diajukan → dibayar/ditolak` saja; tidak bisa loncat status sembarangan).
- [ ] Snapshot data rekening bank disimpan saat pengajuan, supaya histori tidak berubah kalau rekening diedit/dihapus setelahnya.

---

## 6. Urutan Implementasi yang Disarankan

1. Migration tambahan kolom di `disbursements`.
2. Update `Disbursement` model (relasi, cast, scope).
3. `AjukanPencairanRequest`.
4. `Seller\SaldoController` (index + store, dengan locking).
5. Update `Admin\DisbursementController` (approve/reject dengan locking, sesuaikan `store()` lama bila masih dipakai atau nonaktifkan agar semua pencairan lewat alur request).
6. Routes seller + admin.
7. View seller "Saldo Saya" + update view admin.
8. Testing: coba submit ganda cepat (double-click / 2 tab) untuk pastikan tidak ada saldo yang tercairkan dua kali.
