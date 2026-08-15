# Rencana Revisi & Penambahan Fitur
## Proyek: LUDES-MARKET (Laravel 13)
**Tanggal:** Agustus 2026  
**Berdasarkan analisis:** `Gemastik-BUMDes-Berkah-Laravel13.zip`

---

## Daftar Isi
1. [Top Produk Terlaris per Kategori (Landing Page)](#1-top-produk-terlaris-per-kategori)
2. [Sistem Payment Terpusat ke Admin + Komisi 10%](#2-sistem-payment-terpusat-ke-admin)
3. [Ongkos Pengiriman Berbasis Zona (Pilihan Manual)](#3-ongkos-pengiriman-berbasis-zona-pilihan-manual)
4. [Opsi Packing](#4-opsi-packing)
5. [Perbaikan Rating Bintang (Non-Clickable di Detail Produk)](#5-perbaikan-rating-bintang)
6. [Hapus Semua Kata "BUMDes"](#6-hapus-semua-kata-bumdes)
7. [Bedakan Jumlah Pembeli vs Total Pengguna di Dashboard Admin](#7-bedakan-pembeli-vs-total-pengguna)
8. [Alur Verifikasi Penjual oleh Admin](#8-alur-verifikasi-penjual)
9. [Hapus Fitur Rekening Bank pada Penjual](#9-hapus-fitur-rekening-penjual)

---

## 1. Top Produk Terlaris per Kategori

### Kondisi Saat Ini
Di `HomeController.php`, hanya diambil **satu** produk terlaris global (`$topProduct`) dan ditampilkan di landing page dalam satu blok besar. Tidak ada pemisahan per kategori.

Kategori saat ini diambil dari tabel `kategori` via `Kategori::withCount('produk')`.

### Yang Diubah

#### A. `HomeController.php`
Tambahkan query top produk per kategori:

```php
// Ganti query topProduct menjadi top per kategori
$topPerKategori = Kategori::with([
    'produk' => function ($q) {
        $q->whereHas('umkm', fn($u) => $u->where('status', 'aktif'))
          ->withSum(['pesanan as total_terjual' => fn($p) => $p->whereIn('status', ['Diproses', 'Selesai'])], 'jumlah')
          ->withAvg('ulasan', 'rating')
          ->orderByDesc('total_terjual')
          ->limit(1);
    }
])->get()->map(function ($kategori) {
    $kategori->top_produk = $kategori->produk->first();
    return $kategori;
})->filter(fn($k) => $kategori->top_produk !== null);

// Tetap pertahankan $topProduct global (terlaris dari semua kategori) untuk hero section
```

Pass ke view:
```php
return view('public.home', [
    // ... data lama
    'topPerKategori' => $topPerKategori,
]);
```

#### B. `resources/views/public/home.blade.php`
Tambah section baru di bawah section hero top-seller yang sudah ada:

```blade
{{-- Section: Top Terlaris per Kategori --}}
<section class="section" style="padding: 64px 0; background: #fff;">
    <div class="shell">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span>Terfavorit di Tiap Kategori</div>
                <h2>Produk terlaris<br>dari setiap kelompok.</h2>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
            @foreach($topPerKategori as $kategori)
                @if($kategori->top_produk)
                    @php $p = $kategori->top_produk; @endphp
                    <div style="border: 1px solid #e6e1d6; border-radius: 16px; overflow: hidden; background: #faf8f5;">
                        {{-- Badge Kategori --}}
                        <div style="padding: 12px 16px; background: var(--green-950); color: #fff; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.5px;">
                            🏆 TERLARIS — {{ strtoupper($kategori->nama_kategori) }}
                        </div>
                        {{-- Foto --}}
                        @if($p->foto)
                            <img src="{{ asset('storage/' . $p->foto) }}" alt="{{ $p->nama_produk }}"
                                 style="width:100%; height:180px; object-fit:cover;">
                        @else
                            <div style="width:100%; height:180px; background:#f5f1e7; display:flex; align-items:center; justify-content:center; font-size:2.5rem; color:#205037;">
                                <i class="bi bi-bag"></i>
                            </div>
                        @endif
                        {{-- Info --}}
                        <div style="padding: 16px;">
                            <p style="font-size:0.78rem; color:#6e736c; margin:0 0 4px;">{{ $p->umkm->nama_umkm ?? '-' }}</p>
                            <h3 style="font-size:1rem; font-weight:700; color:#173d2b; margin:0 0 8px; line-height:1.3;">{{ $p->nama_produk }}</h3>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem;">
                                <span style="color:#205037; font-weight:700;">Rp{{ number_format($p->harga, 0, ',', '.') }}</span>
                                <span style="color:#9ca3af;">{{ number_format($p->total_terjual ?? 0, 0, ',', '.') }} terjual</span>
                            </div>
                            <a href="{{ route('products.show', $p->id) }}" class="button" style="width:100%; text-align:center; margin-top:12px; display:block;">
                                Lihat Produk <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>
```

---

## 2. Sistem Payment Terpusat ke Admin

### Kondisi Saat Ini
- Tabel `rekening_bank` memiliki relasi ke `umkm_id` — artinya penjual punya rekening masing-masing.
- `CheckoutController` menarik `rekeningBankList` berdasarkan `umkm_id` dari seller terkait.
- Model `Pesanan` memiliki kolom `rekening_bank_id` dan `rekening_bank_snapshot` yang mengarah ke rekening penjual.
- Tidak ada mekanisme komisi.

### Yang Diubah

#### A. Database — Migration Baru

**Migration 1: Kolom komisi di tabel pesanan**
```php
// File: database/migrations/2026_08_XX_add_komisi_to_pesanan_table.php
Schema::table('pesanan', function (Blueprint $table) {
    $table->decimal('ongkos_kirim', 10, 2)->default(0)->after('total_harga');
    $table->decimal('biaya_packing', 10, 2)->default(0)->after('ongkos_kirim');
    $table->decimal('komisi_admin', 10, 2)->default(0)->after('biaya_packing');
    $table->decimal('pendapatan_penjual', 10, 2)->default(0)->after('komisi_admin');
    // Hapus ketergantungan rekening penjual
    // rekening_bank_id & rekening_bank_snapshot dibiarkan nullable (sudah nullable)
});
```

**Migration 2: Tabel disbursement (pencairan ke penjual)**
```php
// File: database/migrations/2026_08_XX_create_disbursements_table.php
Schema::create('disbursements', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('umkm_id');
    $table->foreign('umkm_id')->references('id')->on('umkm')->cascadeOnDelete();
    $table->decimal('jumlah', 12, 2);
    $table->string('status', 20)->default('pending'); // pending, dibayar
    $table->text('catatan')->nullable();
    $table->timestamp('dibayar_at')->nullable();
    $table->unsignedBigInteger('admin_id')->nullable();
    $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
    $table->timestamps();
});

// Pivot: pesanan yang masuk ke disbursement ini
Schema::create('disbursement_pesanan', function (Blueprint $table) {
    $table->unsignedBigInteger('disbursement_id');
    $table->unsignedBigInteger('pesanan_id');
    $table->primary(['disbursement_id', 'pesanan_id']);
});
```

#### B. Model Baru: `Disbursement.php`
```php
// app/Models/Disbursement.php
class Disbursement extends Model
{
    protected $fillable = ['umkm_id', 'jumlah', 'status', 'catatan', 'dibayar_at', 'admin_id'];

    public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }
    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }
    public function pesanan(): BelongsToMany {
        return $this->belongsToMany(Pesanan::class, 'disbursement_pesanan');
    }
}
```

#### C. `CheckoutService.php` — Hitung Komisi Otomatis
Tambahkan kalkulasi saat pesanan dibuat:

```php
// Konstanta komisi admin
const KOMISI_ADMIN_PERSEN = 10;

// Di dalam method checkout(), setelah harga produk dihitung:
$komisiAdmin = round($totalHarga * (self::KOMISI_ADMIN_PERSEN / 100), 2);
$pendapatanPenjual = $totalHarga - $komisiAdmin;

Pesanan::create([
    // ... field lama
    'komisi_admin'      => $komisiAdmin,
    'pendapatan_penjual' => $pendapatanPenjual,
    // rekening_bank_id sekarang selalu null (pembayaran ke admin)
]);
```

#### D. `CheckoutController.php` — Hapus rekeningBankList per Penjual
```php
// HAPUS ini:
$rekeningBankList = RekeningBank::aktif()
    ->where(function ($q) use ($umkmIds) {
        $q->whereIn('umkm_id', $umkmIds)->orWhereNull('umkm_id');
    })->get();

// GANTI dengan rekening admin saja:
$rekeningAdmin = RekeningBank::aktif()
    ->whereNull('umkm_id')  // hanya rekening milik admin/platform
    ->orderBy('urutan')
    ->get();
```

#### E. Controller Admin Baru: `DisbursementController.php`
```php
// app/Http/Controllers/Admin/DisbursementController.php

// index() — tampilkan daftar UMKM beserta saldo yang belum dicairkan
public function index()
{
    $umkmList = Umkm::with(['pesanan' => function ($q) {
        $q->whereIn('status', ['Selesai'])
          ->where('status_pembayaran', 'Sudah Dibayar')
          ->whereDoesntHave('disbursements'); // belum dicairkan
    }])->get()->map(function ($umkm) {
        $umkm->saldo_pending = $umkm->pesanan->sum('pendapatan_penjual');
        return $umkm;
    });

    $riwayat = Disbursement::with(['umkm', 'admin'])->latest()->paginate(20);

    return view('admin.disbursement.index', compact('umkmList', 'riwayat'));
}

// store() — tandai pencairan ke penjual tertentu
public function store(Request $request, Umkm $umkm)
{
    // Ambil semua pesanan selesai & lunas yang belum dicairkan
    $pesanan = Pesanan::where('produk_id', /* produk umkm ini */)
        ->whereIn('status', ['Selesai'])
        ->where('status_pembayaran', 'Sudah Dibayar')
        ->whereDoesntHave('disbursements')
        ->get();

    $jumlah = $pesanan->sum('pendapatan_penjual');
    $disbursement = Disbursement::create([
        'umkm_id'   => $umkm->id,
        'jumlah'    => $jumlah,
        'status'    => 'dibayar',
        'dibayar_at'=> now(),
        'admin_id'  => auth()->id(),
        'catatan'   => $request->catatan,
    ]);
    $disbursement->pesanan()->attach($pesanan->pluck('id'));

    return back()->with('success', "Pencairan Rp".number_format($jumlah,0,',','.')." ke {$umkm->nama_umkm} berhasil dicatat.");
}
```

#### F. View Admin: `resources/views/admin/disbursement/index.blade.php`
Tampilkan tabel dengan kolom:
- Nama UMKM
- Total pesanan selesai belum dicairkan
- Saldo Pending (pendapatan_penjual)
- Komisi admin yang sudah masuk
- Tombol "Tandai Sudah Dicairkan"
- Riwayat pencairan

#### G. View Penjual: Dashboard — Tambah Ringkasan Komisi
Di `resources/views/seller/dashboard.blade.php`, tambahkan card:
```
💰 Pendapatan Bersih (setelah komisi 10%): Rp ...
📋 Menunggu Pencairan: Rp ...
✅ Sudah Dicairkan: Rp ...
```

#### H. Routes Baru
```php
// routes/web.php
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('disbursement', Admin\DisbursementController::class)
         ->only(['index', 'store']);
});
```

---

## 3. Ongkos Pengiriman Berbasis Zona (Pilihan Manual)

### Kondisi Saat Ini
Tidak ada perhitungan ongkos kirim. `total_harga` di tabel `pesanan` hanya berisi harga produk × jumlah. Field `alamat_pengiriman` sudah ada tapi hanya berupa teks bebas, tanpa zona pengiriman.

### Pendekatan
Pembeli **memilih sendiri zona pengirimannya** dari dropdown/radio saat checkout. Tidak perlu geolokasi atau GPS — cukup pilih zona yang sesuai dengan lokasi mereka. Admin bisa mengatur tarif tiap zona dari panel admin.

### Yang Diubah

#### A. Migration Baru

```php
// File: database/migrations/2026_08_XX_create_zona_pengiriman_table.php
Schema::create('zona_pengiriman', function (Blueprint $table) {
    $table->id();
    $table->string('nama_zona', 100);       // "Dalam Desa", "Luar Desa", dst.
    $table->text('keterangan')->nullable(); // Contoh wilayah yang termasuk zona ini
    $table->decimal('biaya', 10, 2);        // Tarif ongkir
    $table->boolean('aktif')->default(true);
    $table->integer('urutan')->default(0);  // Urutan tampil di checkout
    $table->timestamps();
});

// Tambah kolom zona ke tabel pesanan
Schema::table('pesanan', function (Blueprint $table) {
    $table->string('zona_pengiriman', 100)->nullable()->after('alamat_pengiriman');
    // kolom ongkos_kirim sudah ada dari migration #2
});
```

#### B. Seeder: Zona Pengiriman Default

```php
// database/seeders/ZonaPengirimanSeeder.php
ZonaPengiriman::insert([
    [
        'nama_zona'   => 'Dalam Desa',
        'keterangan'  => 'Moncongloe Lappara dan sekitarnya',
        'biaya'       => 2000,
        'urutan'      => 1,
    ],
    [
        'nama_zona'   => 'Luar Desa, Dalam Kecamatan',
        'keterangan'  => 'Moncongloe, Manuju, dan kecamatan sekitar',
        'biaya'       => 5000,
        'urutan'      => 2,
    ],
    [
        'nama_zona'   => 'Luar Kecamatan',
        'keterangan'  => 'Dalam Kabupaten Maros',
        'biaya'       => 15000,
        'urutan'      => 3,
    ],
    [
        'nama_zona'   => 'Luar Kabupaten',
        'keterangan'  => 'Makassar, Gowa, dan daerah lainnya',
        'biaya'       => 25000,
        'urutan'      => 4,
    ],
]);
```

Tampilan zona untuk pembeli:
| Zona | Keterangan | Biaya |
|------|------------|-------|
| Dalam Desa | Moncongloe Lappara dan sekitarnya | Rp 2.000 |
| Luar Desa, Dalam Kecamatan | Moncongloe, Manuju, sekitar | Rp 5.000 |
| Luar Kecamatan | Dalam Kabupaten Maros | Rp 15.000 |
| Luar Kabupaten | Makassar, Gowa, daerah lain | Rp 25.000 |

#### C. Model: `ZonaPengiriman.php`

```php
// app/Models/ZonaPengiriman.php
class ZonaPengiriman extends Model
{
    protected $table = 'zona_pengiriman';
    protected $fillable = ['nama_zona', 'keterangan', 'biaya', 'aktif', 'urutan'];

    protected function casts(): array
    {
        return ['biaya' => 'decimal:2', 'aktif' => 'boolean'];
    }

    public function scopeAktif($query) { return $query->where('aktif', true); }
}
```

#### D. `CheckoutController.php` — Pass Daftar Zona ke View

```php
public function create(Request $request, CartService $cart): View|RedirectResponse
{
    // ... kode existing ...

    $zonaPengiriman = ZonaPengiriman::aktif()->orderBy('urutan')->get();

    return view('checkout.create', [
        'items'          => $items,
        'subtotal'       => $cart->subtotal(),
        'user'           => $request->user(),
        'rekeningAdmin'  => $rekeningAdmin,
        'zonaPengiriman' => $zonaPengiriman,
    ]);
}
```

#### E. View Checkout — Pilihan Zona Pengiriman

Tambahkan di `resources/views/checkout/create.blade.php`, di bawah field alamat pengiriman:

```blade
{{-- Pilih Zona Pengiriman --}}
<label>
    Zona Pengiriman <span class="required">*</span>
    <select name="zona_pengiriman" id="zonaSelect" required onchange="updateOngkir(this)">
        <option value="" disabled selected>— Pilih zona sesuai lokasi Anda —</option>
        @foreach($zonaPengiriman as $zona)
            <option value="{{ $zona->nama_zona }}"
                    data-biaya="{{ $zona->biaya }}"
                    {{ old('zona_pengiriman') === $zona->nama_zona ? 'selected' : '' }}>
                {{ $zona->nama_zona }} — Rp{{ number_format($zona->biaya, 0, ',', '.') }}
            </option>
        @endforeach
    </select>
    <small style="color:#6b7280; display:block; margin-top:4px;">
        Pilih zona yang paling sesuai dengan alamat pengiriman Anda.
    </small>
</label>

{{-- Keterangan zona yang dipilih --}}
<div id="keteranganZona" style="display:none; margin-top:8px; padding:10px 14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; font-size:0.88rem; color:#14532d;">
    <i class="bi bi-info-circle"></i>
    <span id="teksKeteranganZona"></span>
</div>

<script>
const zonaData = {
    @foreach($zonaPengiriman as $zona)
    "{{ $zona->nama_zona }}": {
        biaya: {{ $zona->biaya }},
        keterangan: "{{ $zona->keterangan }}"
    },
    @endforeach
};

function updateOngkir(select) {
    const zona = zonaData[select.value];
    if (!zona) return;

    // Update preview ongkir di ringkasan
    document.getElementById('previewOngkir').style.display = 'block';
    document.getElementById('nilaiOngkir').textContent =
        'Rp' + zona.biaya.toLocaleString('id-ID');

    // Tampilkan keterangan zona
    document.getElementById('keteranganZona').style.display = 'block';
    document.getElementById('teksKeteranganZona').textContent = zona.keterangan;

    // Update total di ringkasan order (jika ada)
    updateTotal();
}
</script>
```

Preview ongkir di bagian ringkasan pesanan:
```blade
{{-- Ringkasan Biaya --}}
<div class="order-summary">
    <div class="summary-row">
        <span>Subtotal Produk</span>
        <span>Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
    </div>
    <div class="summary-row" id="previewOngkir" style="display:none;">
        <span>Ongkos Kirim</span>
        <span id="nilaiOngkir">Rp0</span>
    </div>
    <div class="summary-row" id="previewPacking" style="display:none;">
        <span>Biaya Packing</span>
        <span id="nilaiPacking">Rp0</span>
    </div>
    <div class="summary-row summary-total">
        <strong>Total</strong>
        <strong id="nilaiTotal">Rp{{ number_format($subtotal, 0, ',', '.') }}</strong>
    </div>
</div>
```

#### F. `CheckoutRequest.php` — Tambah Validasi

```php
public function rules(): array
{
    return [
        // ... rules existing ...
        'zona_pengiriman' => ['required', 'string', 'exists:zona_pengiriman,nama_zona'],
    ];
}
```

#### G. `CheckoutService.php` — Hitung Ongkir dari Zona

```php
public function checkout(User $user, array $data): Collection
{
    $zona = ZonaPengiriman::where('nama_zona', $data['zona_pengiriman'])
                          ->where('aktif', true)
                          ->firstOrFail();

    $ongkosKirim = $zona->biaya;

    // Ongkir dibagi rata ke semua item pesanan (atau bisa dibebankan ke satu pesanan)
    $jumlahItem = count($items); // sesuaikan dengan logic CartService
    $ongkirPerItem = $jumlahItem > 0 ? $ongkosKirim / $jumlahItem : $ongkosKirim;

    foreach ($items as $item) {
        Pesanan::create([
            // ... field existing ...
            'zona_pengiriman' => $data['zona_pengiriman'],
            'ongkos_kirim'    => $ongkirPerItem,
            'total_harga'     => ($item->harga * $item->jumlah) + $ongkirPerItem + $biayaPacking,
        ]);
    }
}
```

#### H. Admin — Kelola Zona Pengiriman

Tambah halaman `resources/views/admin/zona-pengiriman/index.blade.php` agar admin bisa:
- Melihat daftar zona dan tarifnya
- Edit biaya tiap zona
- Aktifkan/nonaktifkan zona
- Tambah zona baru jika diperlukan

```php
// app/Http/Controllers/Admin/ZonaPengirimanController.php
Route::resource('zona-pengiriman', Admin\ZonaPengirimanController::class)
     ->only(['index', 'edit', 'update']);
```

---

## 4. Opsi Packing

### Kondisi Saat Ini
Tidak ada pilihan packing sama sekali. Tabel `pesanan` tidak memiliki kolom terkait packing.

### Yang Diubah

#### A. Migration
```php
// Kolom sudah disiapkan di Migration #2 (biaya_packing)
// Tambah kolom jenis packing
Schema::table('pesanan', function (Blueprint $table) {
    $table->string('opsi_packing', 50)->nullable()->after('catatan');
    // biaya_packing sudah ada dari migration sebelumnya
});
```

#### B. Tabel Konfigurasi Packing (opsional, bisa hardcode dulu)
```php
Schema::create('opsi_packing', function (Blueprint $table) {
    $table->id();
    $table->string('nama', 100);        // "Standar", "Premium", "Hadiah"
    $table->text('deskripsi')->nullable();
    $table->decimal('biaya', 10, 2)->default(0);
    $table->boolean('aktif')->default(true);
    $table->integer('urutan')->default(0);
    $table->timestamps();
});
```

Seeder default:
| Nama | Deskripsi | Biaya |
|------|-----------|-------|
| Standar | Plastik biasa | Rp 0 |
| Aman | Bubble wrap + kardus kecil | Rp 3.000 |
| Premium | Box branded + pita | Rp 7.000 |
| Hadiah | Gift wrap + kartu ucapan | Rp 12.000 |

#### C. View Checkout — Tambah Pilihan Packing
```blade
<section class="form-panel">
    <div class="form-panel-head">
        <span>03</span>
        <div>
            <h2>Opsi Packing</h2>
            <p>Pilih jenis kemasan yang sesuai untuk pesanan Anda.</p>
        </div>
    </div>
    <div class="packing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
        @foreach($opsiPacking as $packing)
        <label style="cursor:pointer;">
            <input type="radio" name="opsi_packing" value="{{ $packing->nama }}"
                   data-biaya="{{ $packing->biaya }}"
                   {{ $loop->first ? 'checked' : '' }}
                   style="display:none;" class="packing-radio">
            <div class="packing-card" style="border: 2px solid #e6e1d6; border-radius: 12px; padding: 16px; text-align: center; transition: all 0.2s;">
                <strong style="display:block; color:#173d2b; margin-bottom: 4px;">{{ $packing->nama }}</strong>
                <small style="color:#6b7280; display:block; margin-bottom:8px;">{{ $packing->deskripsi }}</small>
                <span style="font-weight:700; color:#205037;">
                    {{ $packing->biaya > 0 ? '+Rp'.number_format($packing->biaya,0,',','.') : 'Gratis' }}
                </span>
            </div>
        </label>
        @endforeach
    </div>
</section>
```

#### D. `CheckoutRequest.php` — Tambah Validasi
```php
'opsi_packing' => ['nullable', 'string', 'max:100'],
```

#### E. `CheckoutService.php` — Hitung Biaya Packing
```php
$opsiPacking = OpsiPacking::where('nama', $data['opsi_packing'] ?? 'Standar')->first();
$biayaPacking = $opsiPacking?->biaya ?? 0;

// Masukkan ke total + kolom pesanan
$totalAkhir = $hargaProduk + $ongkosKirim + $biayaPacking;
```

#### F. Tampilkan di Ringkasan Pesanan
Update view ringkasan di checkout dan buyer dashboard untuk menampilkan:
```
Subtotal Produk:   Rp xx.xxx
Ongkos Kirim:     Rp xx.xxx
Biaya Packing:    Rp xx.xxx
─────────────────────────────
Total:            Rp xx.xxx
```

---

## 5. Perbaikan Rating Bintang

### Kondisi Saat Ini
Di `resources/views/buyer/dashboard.blade.php` baris 11, rating menggunakan `<select name="rating">` — sudah benar (dropdown), bukan bintang interaktif. **Masalah** ada di tampilan bintang pada halaman **detail produk** (`public/product.blade.php`) dan mungkin di card produk, yang saat ini menampilkan bintang dengan CSS/ikon statis.

**Yang dimaksud:** Bintang display (bukan form) tidak boleh bisa diklik/terlihat seperti bisa diklik.

### Yang Diubah

#### A. Pastikan Bintang Display Murni Static
Di semua tempat yang menampilkan rating (bukan form ulasan), pastikan:

```blade
{{-- BENAR: murni tampilan --}}
<div style="color: #f59e0b; pointer-events: none; user-select: none;">
    @for($i = 1; $i <= 5; $i++)
        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
    @endfor
</div>
```

Tambahkan CSS global di `public.blade.php`:
```css
/* Pastikan bintang display tidak bisa diklik */
.rating-display,
.star-display {
    pointer-events: none !important;
    cursor: default !important;
    user-select: none !important;
}
```

#### B. Form Ulasan di Buyer Dashboard
Form ulasan tetap menggunakan `<select>` dropdown (sudah benar), **bukan** bintang klik. Pastikan tidak ada JavaScript yang mengubah bintang menjadi input interaktif di luar form ulasan.

#### C. Cek Komponen `product-card.blade.php`
```blade
{{-- Tambahkan class rating-display dan pointer-events:none --}}
<span class="rating-display" aria-label="Rating {{ $avgRating }} dari 5">
    @for($i=1; $i<=5; $i++)
        <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }}" 
           style="color:#f59e0b; font-size:0.85rem;"></i>
    @endfor
</span>
```

---

## 6. Hapus Semua Kata "BUMDes"

### File yang Perlu Diubah

Berdasarkan pencarian, kata "BUMDes" muncul di file-file berikut:

#### Views (resources/views/)
| File | Perubahan |
|------|-----------|
| `admin/dashboard.blade.php` | "Ringkasan BUMDes" → "Ringkasan Platform", "Statistik BUMDes" → "Statistik Platform", "Laporan BUMDes" → "Laporan Platform", "Operasional BUMDes" → "Operasional Platform" |
| `admin/umkm/analytics.blade.php` | Ganti semua kata BUMDes dengan nama platform atau "Platform" |
| `admin/umkm/rekomendasi_form.blade.php` | Ganti BUMDes → Platform |
| `admin/rekening-bank/index.blade.php` | Ganti BUMDes → Platform / Admin |
| `admin/rekening-bank/form.blade.php` | Ganti BUMDes → Platform / Admin |
| `seller/dashboard.blade.php` | Ganti BUMDes → Platform / LUDES-MARKET |
| `seller/analytics.blade.php` | Ganti BUMDes → Platform |
| `seller/profile.blade.php` | Ganti BUMDes → Platform |
| `layouts/auth.blade.php` | Ganti BUMDes → LUDES-MARKET atau "Platform" |
| `layouts/public.blade.php` | Ganti BUMDes → LUDES-MARKET |
| `public/catalogue.blade.php` | Ganti BUMDes → LUDES-MARKET |
| `public/umkm/show.blade.php` | Ganti BUMDes → Platform / LUDES-MARKET |
| `buyer/dashboard.blade.php` | Ganti BUMDes → Platform |
| `buyer/profile.blade.php` | Ganti BUMDes → Platform |
| `checkout/create.blade.php` | Ganti BUMDes → Platform |

#### Controllers (app/)
| File | Perubahan |
|------|-----------|
| `Seller/AnalyticsController.php` | Ganti string/komentar BUMDes |
| `Admin/RekeningBankController.php` | Ganti komentar/string BUMDes |
| `Admin/ReportController.php` | Ganti komentar/string BUMDes |
| `Http/Requests/CheckoutRequest.php` | Ganti komentar/string BUMDes |

#### Cara Penggantian Massal (via Terminal)
```bash
# Di root project, jalankan:
grep -rn "BUMDes\|bumdes\|BUMDES" resources/ app/ --include="*.php" --include="*.blade.php" -l

# Ganti semua (Linux/Mac):
find resources/ app/ -type f \( -name "*.php" -o -name "*.blade.php" \) \
  -exec sed -i 's/BUMDes/LUDES-MARKET/g; s/bumdes/ludes-market/g; s/BUMDES/LUDES-MARKET/g' {} \;

# Lakukan review manual setelahnya untuk konteks yang perlu kata berbeda
# (misalnya "Platform", "Admin", dll.)
```

> **Catatan:** Lakukan review manual setelah replace massal. Beberapa konteks mungkin lebih tepat diganti "Platform LUDES-MARKET", "Admin", atau "Pengelola" — bukan selalu "LUDES-MARKET".

---

## 7. Bedakan Pembeli vs Total Pengguna di Dashboard Admin

### Kondisi Saat Ini
Di `DashboardController.php`:
```php
'users' => User::count(),  // semua role digabung
```

Di `admin/dashboard.blade.php`:
```blade
<article><small>Pengguna</small><strong>{{ $stats['users'] }}</strong><span>Semua role</span></article>
```

### Yang Diubah

#### A. `DashboardController.php`
```php
$stats = [
    'umkm'      => Umkm::count(),
    'products'  => Produk::count(),
    'users'     => User::count(),                           // total semua pengguna
    'penjual'   => User::where('role', 'penjual')->count(), // hanya penjual
    'pembeli'   => User::where('role', 'pembeli')->count(), // hanya pembeli
    'orders'    => (int) ($orderStats->orders ?? 0),
    'revenue'   => (float) ($orderStats->revenue ?? 0),
];
```

#### B. `admin/dashboard.blade.php` — Pecah Kartu Statistik
```blade
{{-- Ganti 1 kartu "Pengguna" menjadi 3 kartu --}}
<div class="metric-grid">
    <article>
        <small>Mitra UMKM</small>
        <strong>{{ $stats['umkm'] }}</strong>
        <span>Terdaftar</span>
    </article>
    <article>
        <small>Produk</small>
        <strong>{{ $stats['products'] }}</strong>
        <span>Katalog</span>
    </article>
    <article>
        <small>Total Pengguna</small>
        <strong>{{ $stats['users'] }}</strong>
        <span>Semua peran</span>
    </article>
    <article>
        <small>Pembeli Aktif</small>
        <strong>{{ $stats['pembeli'] }}</strong>
        <span>Akun pembeli</span>
    </article>
    <article>
        <small>Penjual Terdaftar</small>
        <strong>{{ $stats['penjual'] }}</strong>
        <span>Akun penjual</span>
    </article>
    <article>
        <small>Pesanan</small>
        <strong>{{ $stats['orders'] }}</strong>
        <span>Rp{{ number_format($stats['revenue'],0,',','.') }} selesai</span>
    </article>
</div>
```

#### C. `admin/users/index.blade.php` — Tambah Filter per Role
Tambah tab atau dropdown filter: "Semua | Pembeli | Penjual | Admin" agar admin bisa melihat masing-masing kelompok secara terpisah.

```blade
<div class="tab-group" style="margin-bottom: 16px;">
    <a href="?role=" class="tab {{ !request('role') ? 'active' : '' }}">
        Semua ({{ $totalUsers }})
    </a>
    <a href="?role=pembeli" class="tab {{ request('role') === 'pembeli' ? 'active' : '' }}">
        Pembeli ({{ $totalPembeli }})
    </a>
    <a href="?role=penjual" class="tab {{ request('role') === 'penjual' ? 'active' : '' }}">
        Penjual ({{ $totalPenjual }})
    </a>
</div>
```

Update `UserController@index` untuk menerima filter `?role=`:
```php
$query = User::query();
if ($request->role) {
    $query->where('role', $request->role);
}
$users = $query->paginate(20);
```

---

## 8. Alur Verifikasi Penjual oleh Admin

### Kondisi Saat Ini
Di `RegisterController.php`, setelah penjual mendaftar:
1. Akun langsung berstatus `aktif`
2. UMKM langsung dibuat dengan `status => 'aktif'`
3. Penjual langsung bisa login dan menambah produk

### Yang Diubah

#### A. Database — Migration Baru
```php
// Migration: tambah kolom verifikasi ke tabel umkm
Schema::table('umkm', function (Blueprint $table) {
    // Status verifikasi khusus untuk penjual baru
    $table->string('status_verifikasi', 20)->default('menunggu')
          ->after('status'); // menunggu, disetujui, ditolak
    $table->text('catatan_verifikasi')->nullable()->after('status_verifikasi');
    $table->timestamp('verified_at')->nullable()->after('catatan_verifikasi');
    $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
    $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
});

// Migration: tabel pertanyaan onboarding penjual
Schema::create('seller_onboarding', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('umkm_id');
    $table->foreign('umkm_id')->references('id')->on('umkm')->cascadeOnDelete();
    // Jawaban pertanyaan onboarding (disimpan sebagai JSON)
    $table->json('jawaban');
    $table->timestamps();
});
```

#### B. Pertanyaan Onboarding Penjual
Pertanyaan relevan yang harus dijawab setelah registrasi:

| No | Pertanyaan | Tipe |
|----|------------|------|
| 1 | Produk apa yang Anda jual? (deskripsikan singkat) | Textarea |
| 2 | Berapa rata-rata stok yang dapat Anda sediakan per minggu? | Number |
| 3 | Apakah Anda sudah memiliki izin usaha? (SIUP, NIB, dll.) | Radio (Ya/Tidak) |
| 4 | Nomor izin usaha Anda (jika ada) | Text |
| 5 | Bagaimana cara Anda mengemas produk saat ini? | Textarea |
| 6 | Apakah Anda bersedia memenuhi pesanan dalam 1×24 jam? | Radio (Ya/Tidak) |
| 7 | Foto KTP pemilik usaha | File upload |
| 8 | Foto produk Anda (minimal 1) | File upload |

#### C. `RegisterController.php` — Ubah Status Default
```php
// UBAH status penjual menjadi 'pending' bukan 'aktif'
$user = User::create([
    // ...
    'status' => $data['role'] === 'penjual' ? 'pending' : 'aktif',
]);

if ($user->isSeller()) {
    Umkm::create([
        'user_id'           => $user->id,
        'nama_umkm'         => $data['nama_umkm'],
        'pemilik'           => $user->nama_lengkap,
        'alamat'            => $data['alamat'] ?? 'Desa Moncongloe Lappara',
        'no_hp'             => $user->no_hp,
        'status'            => 'nonaktif',          // belum aktif
        'status_verifikasi' => 'menunggu',           // menunggu verifikasi
    ]);

    // Redirect ke halaman onboarding, bukan dashboard
    Auth::login($user);
    return redirect()->route('seller.onboarding')
                     ->with('info', 'Akun berhasil dibuat. Lengkapi informasi usaha Anda untuk diverifikasi admin.');
}
```

#### D. Controller Onboarding: `Seller/OnboardingController.php`
```php
// app/Http/Controllers/Seller/OnboardingController.php

public function create(): View
{
    // Pastikan penjual belum mengisi onboarding
    $umkm = auth()->user()->umkm;
    if ($umkm->status_verifikasi !== 'menunggu' || 
        $umkm->sellerOnboarding()->exists()) {
        return redirect()->route('penjual.dashboard');
    }
    return view('seller.onboarding');
}

public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'deskripsi_produk'    => ['required', 'string', 'min:20'],
        'kapasitas_mingguan'  => ['required', 'integer', 'min:1'],
        'punya_izin'          => ['required', 'in:ya,tidak'],
        'nomor_izin'          => ['nullable', 'string'],
        'cara_kemas'          => ['required', 'string'],
        'sanggup_24jam'       => ['required', 'in:ya,tidak'],
        'foto_ktp'            => ['required', 'image', 'max:2048'],
        'foto_produk'         => ['required', 'image', 'max:2048'],
    ]);

    $umkm = auth()->user()->umkm;

    // Upload foto
    $jawaban = collect($validated)->except(['foto_ktp', 'foto_produk'])->toArray();
    $jawaban['foto_ktp']    = $request->file('foto_ktp')->store('onboarding', 'public');
    $jawaban['foto_produk'] = $request->file('foto_produk')->store('onboarding', 'public');

    SellerOnboarding::create([
        'umkm_id' => $umkm->id,
        'jawaban' => $jawaban,
    ]);

    // Notifikasi admin (via log aktivitas atau notifikasi sistem)
    // ActivityLogger::log('Penjual baru menunggu verifikasi', auth()->user());

    return redirect()->route('seller.onboarding.waiting')
                     ->with('success', 'Informasi usaha berhasil dikirim. Tunggu verifikasi dari admin.');
}
```

#### E. Middleware — Blokir Penjual Belum Terverifikasi
```php
// app/Http/Middleware/EnsureSellerVerified.php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    if ($user?->isSeller()) {
        $umkm = $user->umkm;
        if ($umkm?->status_verifikasi === 'menunggu') {
            return redirect()->route('seller.onboarding.waiting');
        }
        if ($umkm?->status_verifikasi === 'ditolak') {
            return redirect()->route('seller.onboarding.rejected');
        }
    }
    return $next($request);
}
```

Daftarkan di `bootstrap/app.php` dan terapkan ke route penjual yang butuh akses penuh (tambah produk, dll.).

#### F. Admin — Verifikasi Penjual
Tambah halaman verifikasi penjual di panel admin:

```php
// app/Http/Controllers/Admin/VerifikasiPenjualController.php

public function index()
{
    $menunggu = Umkm::with(['user', 'sellerOnboarding'])
        ->where('status_verifikasi', 'menunggu')
        ->whereHas('sellerOnboarding')
        ->latest()
        ->get();

    return view('admin.verifikasi-penjual.index', compact('menunggu'));
}

public function approve(Umkm $umkm)
{
    $umkm->update([
        'status'              => 'aktif',
        'status_verifikasi'   => 'disetujui',
        'verified_at'         => now(),
        'verified_by'         => auth()->id(),
    ]);
    $umkm->user->update(['status' => 'aktif']);

    return back()->with('success', "Penjual {$umkm->nama_umkm} berhasil diverifikasi.");
}

public function reject(Request $request, Umkm $umkm)
{
    $umkm->update([
        'status_verifikasi'   => 'ditolak',
        'catatan_verifikasi'  => $request->catatan,
        'verified_at'         => now(),
        'verified_by'         => auth()->id(),
    ]);

    return back()->with('success', "Penjual {$umkm->nama_umkm} telah ditolak.");
}
```

#### G. View Admin — Halaman Verifikasi
`resources/views/admin/verifikasi-penjual/index.blade.php` menampilkan:
- Nama penjual & UMKM
- Semua jawaban onboarding
- Foto KTP & produk
- Tombol **Setujui** dan **Tolak** (dengan form alasan penolakan)

#### H. Routes Baru
```php
// Seller onboarding routes
Route::middleware('auth')->prefix('penjual')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('seller.onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('seller.onboarding.store');
    Route::get('/onboarding/menunggu', fn() => view('seller.onboarding-waiting'))->name('seller.onboarding.waiting');
    Route::get('/onboarding/ditolak', fn() => view('seller.onboarding-rejected'))->name('seller.onboarding.rejected');
});

// Admin verifikasi
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/verifikasi-penjual', [VerifikasiPenjualController::class, 'index'])->name('admin.verifikasi-penjual.index');
    Route::post('/verifikasi-penjual/{umkm}/approve', [VerifikasiPenjualController::class, 'approve'])->name('admin.verifikasi-penjual.approve');
    Route::post('/verifikasi-penjual/{umkm}/reject', [VerifikasiPenjualController::class, 'reject'])->name('admin.verifikasi-penjual.reject');
});
```

---

## 9. Hapus Fitur Rekening Bank pada Penjual

### Kondisi Saat Ini
- `Seller/RekeningBankController.php` — controller penuh untuk penjual kelola rekening
- `resources/views/seller/rekening-bank/` — folder view (form + index)
- Route seller rekening bank terdaftar di `routes/web.php`
- Sidebar penjual punya link "Rekening Bank"
- `Umkm` model punya relasi `rekeningBank()`

### Yang Diubah

#### A. Hapus File
```bash
# File yang dihapus:
rm app/Http/Controllers/Seller/RekeningBankController.php
rm app/Http/Requests/Seller/RekeningBankRequest.php
rm -rf resources/views/seller/rekening-bank/
```

#### B. Routes — Hapus Route Rekening Penjual
```php
// routes/web.php — HAPUS blok ini:
Route::prefix('penjual')->group(function () {
    // ...
    Route::resource('rekening-bank', Seller\RekeningBankController::class); // HAPUS
});
```

#### C. View Seller — Hapus Link Sidebar
Di `resources/views/layouts/dashboard.blade.php` atau sidebar penjual, hapus:
```blade
{{-- HAPUS baris ini dari menu navigasi penjual: --}}
<a href="{{ route('seller.rekening-bank.index') }}">
    <i class="bi bi-bank"></i> Rekening Bank
</a>
```

#### D. View Seller Profile — Hapus Bagian Rekening
Di `resources/views/seller/profile.blade.php`, hapus section yang menampilkan atau mengedit rekening bank penjual.

#### E. Model `Umkm.php` — Hapus Relasi (Opsional)
```php
// Hapus atau biarkan (tidak merusak, tapi bersihkan kode):
// public function rekeningBank(): HasMany { ... }  // HAPUS
```

#### F. Tabel `rekening_bank` — Pertahankan untuk Admin
Tabel `rekening_bank` **tidak dihapus** karena masih dipakai oleh rekening admin (yang `umkm_id`-nya NULL). Hanya akses penjual yang dicabut.

Pastikan di `Admin/RekeningBankController.php`, query selalu filter atau hanya izinkan rekening admin:
```php
// Rekening admin = yang umkm_id-nya NULL
RekeningBank::whereNull('umkm_id')->get(); // rekening platform/admin
```

---

## Ringkasan Urutan Pengerjaan yang Disarankan

Urutan pengerjaan agar tidak terjadi konflik:

```
1. [#6]  Hapus kata BUMDes (paling aman, tidak mengubah logika)
2. [#5]  Fix bintang rating (CSS/HTML sederhana)
3. [#7]  Bedakan pembeli vs penjual di dashboard admin
4. [#9]  Hapus rekening bank penjual
5. [#8]  Alur verifikasi penjual (butuh migration + controller baru)
6. [#2]  Sistem payment ke admin + komisi (migration + service)
7. [#4]  Opsi packing (migration + view checkout)
8. [#3]  Ongkos kirim berbasis jarak (migration + service + API)
9. [#1]  Top produk per kategori di landing page (query + view)
```

---

## Checklist Migration yang Perlu Dibuat

| # | Nama Migration | Tabel / Kolom |
|---|---------------|---------------|
| 1 | `add_shipping_and_packing_to_pesanan_table` | `pesanan`: `ongkos_kirim`, `biaya_packing`, `komisi_admin`, `pendapatan_penjual`, `opsi_packing`, `lat_pengiriman`, `lng_pengiriman`, `jarak_km` |
| 2 | `create_disbursements_table` | `disbursements`, `disbursement_pesanan` |
| 3 | `create_zona_pengiriman_table` | `zona_pengiriman` + kolom `zona_pengiriman` di tabel `pesanan` |
| 4 | `create_opsi_packing_table` | `opsi_packing` |
| 5 | `add_verifikasi_to_umkm_table` | `umkm`: `status_verifikasi`, `catatan_verifikasi`, `verified_at`, `verified_by` |
| 6 | `create_seller_onboarding_table` | `seller_onboarding` |

---

## Catatan Tambahan

- **Backup database** sebelum menjalankan migration baru.
- **Test alur checkout** setelah fitur ongkir + packing ditambahkan karena mengubah kalkulasi `total_harga`.
- **Komisi 10%** dihitung dari harga produk (sebelum ongkir & packing), bukan dari total pesanan — pastikan ini sesuai kesepakatan.
- **Geolokasi** membutuhkan HTTPS agar `navigator.geolocation` berfungsi di browser. Pastikan environment production sudah menggunakan SSL.
- **Verifikasi penjual** perlu disertai notifikasi email/WhatsApp ke admin agar tidak terlewat — pertimbangkan menggunakan Laravel Notifications.
