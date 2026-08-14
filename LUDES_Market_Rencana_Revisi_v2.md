# 🚀 LUDES Market — Rencana Revisi v2
**Berdasarkan ZIP terbaru: Gemastik-BUMDes-Berkah-Laravel13 (14 Agustus 2026)**

---

## ✅ Kondisi Terkini — Apa yang Sudah Ada

### Seller Dashboard (sudah bagus, jangan diubah)
- Doughnut chart interaktif — toggle **Porsi Terjual / Omzet** ✅
- Hover legend → highlight slice di chart ✅
- Metric cards: Produk aktif, Total pesanan, Sudah Dibayar, Belum Dibayar/COD ✅
- Tabel pesanan terbaru + badge status pembayaran (Paid/COD/Unpaid) ✅
- Notifikasi pembayaran real-time (`seller-notifications.js`) ✅

### Admin Dashboard (sudah bagus, jangan diubah)
- Doughnut chart Top 10 produk terlaris desa — toggle terjual/omzet ✅
- Medal 🥇🥈🥉 di legend ✅
- Tabel pesanan lintas UMKM ✅

### Halaman Publik
- Hero + section UMKM mitra + peta lokasi ✅
- **Produk Terlaris No. 1 Desa** (section `top-seller-section`) ✅
- Section Keroyokan CTA ✅
- Katalog produk + detail produk ✅

### Sistem
- QRIS via Xendit + COD + Transfer ✅
- 3 role: admin / penjual / pembeli ✅
- Laporan CSV (seller & admin) ✅
- Sistem ulasan (rating + komentar, terikat pesanan selesai) ✅
- Keroyokan (group buying) ✅
- Log aktivitas ✅

---

## ❌ Gap — Yang Belum Ada vs Visi "Akselerasi Usaha"

| # | Fitur | Kenapa Penting |
|---|---|---|
| 1 | **15 UMKM** di seeder (sekarang hanya 5 aktif, Moammar di-comment) | Target eksplisit revisi |
| 2 | **Tren omzet bulanan** (grafik bar/line per bulan) | Doughnut hanya distribusi, bukan tren waktu |
| 3 | **Halaman profil toko publik** `/toko/{umkm}` | UMKM perlu "etalase" sendiri |
| 4 | **Trend permintaan dari ulasan** (rating & komentar dievaluasi) | Sinyal pasar dari pelanggan |
| 5 | **Rekomendasi strategi** dari admin BUMDes ke tiap UMKM | Akselerasi usaha konkret |
| 6 | **Perbandingan growth antar UMKM** di admin | Admin perlu tahu siapa perlu didorong |
| 7 | **Produk promo/diskon** dengan tanggal berlaku | Mendukung fitur promosi |
| 8 | **Data penjualan historis** di seeder (minimal 6 bulan) | Grafik tren tidak bisa tampil tanpa data |

---

## 🗄️ A. Perubahan Database (3 Migration Baru)

### Migration 1 — Kolom Promo di Tabel `produk`
```php
// 2026_08_XX_000017_add_promo_to_produk_table.php
Schema::table('produk', function (Blueprint $table) {
    $table->boolean('is_promo')->default(false)->index()->after('foto');
    $table->decimal('harga_promo', 12, 2)->nullable()->after('is_promo');
    $table->timestamp('promo_mulai')->nullable()->after('harga_promo');
    $table->timestamp('promo_selesai')->nullable()->after('promo_mulai');
    $table->string('label_promo', 100)->nullable()->after('promo_selesai'); // "Diskon 20%", "Flash Sale", dll
});
```

### Migration 2 — Kolom Tambahan di Tabel `umkm`
```php
// 2026_08_XX_000018_add_extras_to_umkm_table.php
Schema::table('umkm', function (Blueprint $table) {
    $table->string('kategori_usaha', 100)->nullable()->after('deskripsi');
    $table->year('tahun_berdiri')->nullable()->after('kategori_usaha');
    $table->unsignedSmallInteger('jumlah_karyawan')->default(1)->after('tahun_berdiri');
    $table->string('instagram')->nullable()->after('jumlah_karyawan');
});
```

### Migration 3 — Tabel `rekomendasi_strategi` (Baru)
```php
// 2026_08_XX_000019_create_rekomendasi_strategi_table.php
Schema::create('rekomendasi_strategi', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('umkm_id');
    $table->foreign('umkm_id')->references('id')->on('umkm')->cascadeOnDelete();
    $table->index('umkm_id');
    $table->string('judul', 200);
    $table->text('isi');
    $table->string('tipe', 50)->default('promosi'); // promosi | produk | harga | distribusi
    $table->string('periode', 7); // format YYYY-MM, misal 2026-08
    $table->boolean('dibaca')->default(false);
    $table->timestamps();
    $table->index(['umkm_id', 'periode']);
});
```

---

## 🧩 B. Models

### Model Baru: `app/Models/RekomendasiStrategi.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekomendasiStrategi extends Model
{
    protected $table = 'rekomendasi_strategi';
    protected $fillable = ['umkm_id', 'judul', 'isi', 'tipe', 'periode', 'dibaca'];
    protected function casts(): array {
        return ['dibaca' => 'boolean'];
    }
    public function umkm(): BelongsTo {
        return $this->belongsTo(Umkm::class, 'umkm_id');
    }
}
```

### Update `app/Models/Umkm.php` — Tambah relasi & method
```php
// Tambahkan di dalam class Umkm:

public function rekomendasiStrategi(): HasMany {
    return $this->hasMany(RekomendasiStrategi::class, 'umkm_id');
}

public function rekomendasiBelumDibaca(): int {
    return $this->rekomendasiStrategi()->where('dibaca', false)->count();
}
```

### Update `app/Models/Produk.php` — Tambah method promo
```php
// Tambahkan di dalam class Produk:

public function isPromoAktif(): bool {
    if (!$this->is_promo || $this->harga_promo === null) return false;
    if ($this->promo_mulai && $this->promo_mulai > now()) return false;
    if ($this->promo_selesai && $this->promo_selesai < now()) return false;
    return true;
}

public function hargaEfektif(): float {
    return $this->isPromoAktif() ? (float) $this->harga_promo : (float) $this->harga;
}

public function diskonPersen(): int {
    if (!$this->isPromoAktif() || $this->harga == 0) return 0;
    return (int) round((($this->harga - $this->harga_promo) / $this->harga) * 100);
}
```

---

## 🎮 C. Controllers Baru

### 1. `app/Http/Controllers/Public/UmkmController.php`
**Dua method:**

**`index()`** — Daftar semua toko UMKM publik:
```php
$umkms = Umkm::where('status', 'aktif')
    ->withCount('produk')
    ->withAvg(['produk.ulasan as avg_rating'], 'rating')
    ->withCount(['produk.ulasan as total_ulasan'])
    ->with(['produk' => fn($q) => $q->where('is_promo', true)
        ->where('promo_selesai', '>=', now())->limit(1)])
    ->get();
return view('public.umkm.index', compact('umkms'));
```

**`show(Umkm $umkm)`** — Profil lengkap 1 toko:
```php
// Produk aktif toko
$produk = $umkm->produk()->with('kategori')->where('stok_jumlah', '>', 0)->get();

// Produk promo aktif
$produkPromo = $umkm->produk()
    ->where('is_promo', true)
    ->where('promo_selesai', '>=', now())->get();

// Ulasan terbaru
$ulasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
    ->with(['pembeli', 'produk'])->latest()->take(8)->get();

// Distribusi rating (bintang 1–5)
$ratingDistribusi = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
    ->selectRaw('rating, COUNT(*) as jumlah')
    ->groupBy('rating')->orderBy('rating', 'desc')
    ->get()->keyBy('rating');

$avgRating = $ulasan->avg('rating');
$totalUlasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))->count();

return view('public.umkm.show', compact(
    'umkm', 'produk', 'produkPromo', 'ulasan',
    'ratingDistribusi', 'avgRating', 'totalUlasan'
));
```

---

### 2. `app/Http/Controllers/Seller/AnalyticsController.php`
```php
public function index(Request $request)
{
    $umkm = $request->user()->umkm()->firstOrFail();

    // Tren omzet 6 bulan terakhir
    $trendOmzet = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
        ->where('status', 'Selesai')
        ->where('tanggal_pesan', '>=', now()->subMonths(5)->startOfMonth())
        ->selectRaw("DATE_FORMAT(tanggal_pesan, '%Y-%m') as bulan,
                     SUM(total_harga) as omzet,
                     SUM(jumlah) as total_item,
                     COUNT(*) as jumlah_transaksi")
        ->groupBy('bulan')->orderBy('bulan')->get();

    // Hitung pertumbuhan bulan ini vs bulan lalu
    $omzetBulanIni   = (float) ($trendOmzet->last()?->omzet ?? 0);
    $omzetBulanLalu  = (float) ($trendOmzet->slice(-2, 1)->first()?->omzet ?? 0);
    $pertumbuhanPct  = $omzetBulanLalu > 0
        ? round((($omzetBulanIni - $omzetBulanLalu) / $omzetBulanLalu) * 100, 1)
        : null;

    // Tren rating ulasan 3 bulan terakhir
    $trendUlasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
        ->where('created_at', '>=', now()->subMonths(2)->startOfMonth())
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan,
                     ROUND(AVG(rating), 2) as avg_rating,
                     COUNT(*) as jumlah_ulasan")
        ->groupBy('bulan')->orderBy('bulan')->get();

    // Produk terbaik (rating tinggi)
    $produkTerbaik = Produk::where('umkm_id', $umkm->id)
        ->withAvg('ulasan as avg_rating', 'rating')
        ->withCount('ulasan')
        ->having('ulasan_count', '>', 0)
        ->orderByDesc('avg_rating')->take(5)->get();

    // Produk perlu perhatian (rating < 3.5)
    $produkPerhatian = Produk::where('umkm_id', $umkm->id)
        ->withAvg('ulasan as avg_rating', 'rating')
        ->withCount('ulasan')
        ->having('ulasan_count', '>', 0)
        ->having('avg_rating', '<', 3.5)
        ->orderBy('avg_rating')->take(3)->get();

    // Rekomendasi dari admin BUMDes
    $rekomendasi = RekomendasiStrategi::where('umkm_id', $umkm->id)
        ->latest()->take(5)->get();

    // Tandai sudah dibaca
    RekomendasiStrategi::where('umkm_id', $umkm->id)
        ->where('dibaca', false)->update(['dibaca' => true]);

    return view('seller.analytics', compact(
        'umkm', 'trendOmzet', 'omzetBulanIni', 'pertumbuhanPct',
        'trendUlasan', 'produkTerbaik', 'produkPerhatian', 'rekomendasi'
    ));
}
```

---

### 3. `app/Http/Controllers/Admin/UmkmAnalyticsController.php`
```php
// index() — Tabel perbandingan growth semua UMKM
public function index() {
    $bulanIni  = now()->format('Y-m');
    $bulanLalu = now()->subMonth()->format('Y-m');

    $umkms = Umkm::all()->map(function ($umkm) use ($bulanIni, $bulanLalu) {
        $base = Pesanan::whereHas('produk', fn($q) =>
            $q->where('umkm_id', $umkm->id))->where('status', 'Selesai');

        $omzetIni  = (clone $base)->whereRaw("DATE_FORMAT(tanggal_pesan,'%Y-%m')=?",[$bulanIni])->sum('total_harga');
        $omzetLalu = (clone $base)->whereRaw("DATE_FORMAT(tanggal_pesan,'%Y-%m')=?",[$bulanLalu])->sum('total_harga');

        $umkm->omzet_ini   = (float) $omzetIni;
        $umkm->omzet_lalu  = (float) $omzetLalu;
        $umkm->growth      = $omzetLalu > 0 ? round((($omzetIni - $omzetLalu) / $omzetLalu) * 100, 1) : null;
        $umkm->avg_rating  = round(Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id',$umkm->id))->avg('rating') ?? 0, 1);
        $umkm->total_ulasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id',$umkm->id))->count();
        return $umkm;
    })->sortByDesc('omzet_ini');

    return view('admin.umkm.analytics', compact('umkms'));
}

// Kirim rekomendasi ke UMKM
public function rekomendasiStore(Request $request, Umkm $umkm) {
    $validated = $request->validate([
        'judul'   => 'required|string|max:200',
        'isi'     => 'required|string',
        'tipe'    => 'required|in:promosi,produk,harga,distribusi',
        'periode' => 'required|date_format:Y-m',
    ]);
    RekomendasiStrategi::create($validated + ['umkm_id' => $umkm->id]);
    return back()->with('success', 'Rekomendasi berhasil dikirim ke ' . $umkm->nama_umkm);
}
```

---

## 🛣️ D. Routes Baru

Tambahkan ke `routes/web.php`:

```php
// === PUBLIC (tanpa auth) ===
Route::get('/toko', [\App\Http\Controllers\Public\UmkmController::class, 'index'])->name('umkm.index');
Route::get('/toko/{umkm}', [\App\Http\Controllers\Public\UmkmController::class, 'show'])->name('umkm.show');

// === SELLER (dalam grup role:penjual) ===
Route::get('/analitik', [\App\Http\Controllers\Seller\AnalyticsController::class, 'index'])->name('seller.analytics');

// === ADMIN (dalam grup role:admin) ===
Route::get('/analitik-umkm', [\App\Http\Controllers\Admin\UmkmAnalyticsController::class, 'index'])->name('admin.umkm.analytics');
Route::get('/analitik-umkm/{umkm}/rekomendasi', [\App\Http\Controllers\Admin\UmkmAnalyticsController::class, 'rekomendasiCreate'])->name('admin.umkm.rekomendasi.create');
Route::post('/analitik-umkm/{umkm}/rekomendasi', [\App\Http\Controllers\Admin\UmkmAnalyticsController::class, 'rekomendasiStore'])->name('admin.umkm.rekomendasi.store');
```

---

## 🖼️ E. Views Baru

### 1. `resources/views/public/umkm/index.blade.php` — Daftar 15 Toko
Konten:
- Grid card per UMKM: foto toko, nama, pemilik, kategori usaha
- Rating bintang rata-rata + jumlah ulasan
- Badge **🔥 Promo Aktif** kalau ada produk diskon
- Jumlah produk aktif
- Tombol "Kunjungi Toko →"
- Filter sederhana berdasarkan kategori usaha

### 2. `resources/views/public/umkm/show.blade.php` — Profil Toko
Konten:
- Header: foto, nama UMKM, pemilik, kategori, berdiri sejak, deskripsi
- **Section Promo Aktif** (jika ada) — highlight dengan badge diskon %
- **Section Semua Produk** — grid produk toko ini
- **Section Ulasan Pelanggan:**
  - Rata-rata rating (angka besar) + distribusi bintang 1–5 (progress bar)
  - Card ulasan terbaru: nama pembeli, produk, bintang, komentar
- Info kontak & lokasi

### 3. `resources/views/seller/analytics.blade.php` — Analitik UMKM
Konten:
- Header: "📈 Analitik & Akselerasi Usaha — [Nama UMKM]"
- **4 Metric cards:**
  - Omzet bulan ini + badge ↑/↓ % vs bulan lalu
  - Total transaksi selesai bulan ini
  - Rata-rata rating ulasan
  - Jumlah ulasan masuk bulan ini
- **Grafik Bar** — Tren Omzet 6 Bulan (Chart.js, warna hijau `#173d2b`)
  - Label di atas bar bulan ini: "+X%" atau "-X%"
- **Grafik Line** — Tren Rating Ulasan 3 Bulan (Chart.js)
- **Tabel Produk Terbaik** — nama, avg rating, jumlah ulasan, total terjual
- **Tabel Produk Perlu Perhatian** — nama, avg rating rendah, saran
- **Section Rekomendasi Strategi dari BUMDes:**
  - Card per rekomendasi, badge tipe (Promosi/Produk/Harga/Distribusi)
  - Periode berlaku, isi rekomendasi lengkap

### 4. `resources/views/admin/umkm/analytics.blade.php` — Overview Semua UMKM
Konten:
- Header: "Analitik Akselerasi — 15 UMKM Moncongloe"
- **Tabel ranking** semua UMKM:
  - Nama UMKM, Omzet bulan ini, Omzet bulan lalu
  - Growth (%) — warna **hijau** jika positif, **merah** jika negatif, **abu** jika belum ada data
  - Avg Rating, Jumlah Ulasan
  - Tombol "Kirim Rekomendasi"
- **Grafik Bar Horizontal** — ranking omzet bulan ini semua UMKM (Chart.js)

### 5. `resources/views/admin/umkm/rekomendasi_form.blade.php` — Form Rekomendasi
Konten:
- Info singkat UMKM target (nama, pemilik, omzet bulan ini, avg rating)
- Form: Judul, Tipe (select), Periode (YYYY-MM), Isi (textarea)
- Riwayat rekomendasi yang pernah dikirim ke UMKM ini

---

## ✏️ F. Update File yang Sudah Ada

### `resources/views/layouts/dashboard.blade.php`
Tambahkan menu sidebar (posisi: setelah menu "Laporan"):

**Untuk penjual:**
```html
<a class="{{ request()->routeIs('seller.analytics')?'active':'' }}"
   href="{{ route('seller.analytics') }}">
    <i class="bi bi-graph-up-arrow"></i>Analitik Usaha
</a>
```

**Untuk admin:**
```html
<a class="{{ request()->routeIs('admin.umkm.analytics*')?'active':'' }}"
   href="{{ route('admin.umkm.analytics') }}">
    <i class="bi bi-bar-chart-line"></i>Analitik UMKM
</a>
```

---

### `resources/views/seller/dashboard.blade.php`
Tambahkan **notifikasi rekomendasi baru** di atas metric-grid (jika ada yang belum dibaca):

```blade
@if(isset($rekomendasiBelumDibaca) && $rekomendasiBelumDibaca > 0)
<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px;
            padding:14px 18px; margin-bottom:20px; display:flex;
            align-items:center; gap:12px;">
    <i class="bi bi-lightbulb-fill" style="color:#059669; font-size:1.3rem;"></i>
    <div>
        <strong style="color:#065f46;">
            Ada {{ $rekomendasiBelumDibaca }} rekomendasi strategi baru dari BUMDes!
        </strong>
        <span style="color:#047857; font-size:0.9rem; margin-left:8px;">
            <a href="{{ route('seller.analytics') }}" style="color:#059669; font-weight:600;">
                Lihat sekarang →
            </a>
        </span>
    </div>
</div>
@endif
```

Juga update `DashboardController.php` untuk pass variabel ini:
```php
$rekomendasiBelumDibaca = RekomendasiStrategi::where('umkm_id', $umkm->id)
    ->where('dibaca', false)->count();

return view('seller.dashboard', compact(
    'umkm', 'stats', 'recent', 'topProducts', 'rekomendasiBelumDibaca'
));
```

---

### `resources/views/layouts/public.blade.php`
Tambahkan link navigasi "Toko UMKM":
```html
<a href="{{ route('umkm.index') }}">Toko UMKM</a>
```

---

### `resources/views/public/home.blade.php`
Tambahkan **1 section baru** setelah section `producers-section`:

```blade
{{-- Section: Ulasan Pelanggan Terbaru --}}
<section class="section" style="background:#faf8f5;">
    <div class="shell">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span>Suara Pelanggan</div>
                <h2>Apa kata mereka yang<br>sudah mencoba.</h2>
            </div>
            <a class="outline-link" href="{{ route('umkm.index') }}">
                Lihat semua toko <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px;">
            @foreach($ulasanTerbaru as $ulasan)
            <article style="background:#fff; border:1px solid #e6e1d6; border-radius:14px; padding:24px;">
                <div style="display:flex; gap:4px; margin-bottom:10px;">
                    @for($i=1;$i<=5;$i++)
                        <i class="bi bi-star-fill" style="color:{{ $i<=$ulasan->rating?'#f59e0b':'#e5e7eb' }};"></i>
                    @endfor
                </div>
                <p style="color:#374151; font-size:0.92rem; line-height:1.6; margin-bottom:14px;">
                    "{{ Str::limit($ulasan->komentar, 120) }}"
                </p>
                <div style="font-size:0.82rem; color:#6b7280;">
                    <strong>{{ $ulasan->pembeli->nama_lengkap }}</strong> ·
                    {{ $ulasan->produk->nama_produk }}
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
```

Dan update `HomeController` untuk pass `$ulasanTerbaru`:
```php
$ulasanTerbaru = Ulasan::with(['pembeli', 'produk.umkm'])
    ->where('rating', '>=', 4)
    ->whereNotNull('komentar')
    ->where('komentar', '!=', '')
    ->latest()->take(3)->get();
```

---

## 🌱 G. Update Seeder — 15 UMKM + Data Historis

### Tambahkan 10 UMKM baru (total jadi 15):
```php
// User penjual baru:
['username'=>'umkm_madu',    'nama_lengkap'=>'Pak Rudi',       'role'=>'penjual'],
['username'=>'umkm_batik',   'nama_lengkap'=>'Ibu Aminah',     'role'=>'penjual'],
['username'=>'umkm_kopi',    'nama_lengkap'=>'Pak Andi',       'role'=>'penjual'],
['username'=>'umkm_tempe',   'nama_lengkap'=>'Ibu Ramlah',     'role'=>'penjual'],
['username'=>'umkm_sambal',  'nama_lengkap'=>'Ibu Hasna',      'role'=>'penjual'],
['username'=>'umkm_telur',   'nama_lengkap'=>'Pak Dg. Nai',    'role'=>'penjual'],
['username'=>'umkm_kerupuk', 'nama_lengkap'=>'Ibu Marlina',    'role'=>'penjual'],
['username'=>'umkm_tepung',  'nama_lengkap'=>'Pak Halim',      'role'=>'penjual'],
['username'=>'umkm_sabun',   'nama_lengkap'=>'Ibu Dg. Ti',     'role'=>'penjual'],
['username'=>'umkm_sulam',   'nama_lengkap'=>'Ibu Jumriah',    'role'=>'penjual'],

// UMKM baru (umkm_id 6–15):
['nama_umkm'=>'Madu Hutan Pak Rudi',           'kategori_usaha'=>'Produk Alam'],
['nama_umkm'=>'Batik Tulis Aminah',             'kategori_usaha'=>'Kerajinan'],
['nama_umkm'=>'Kopi Robusta Moncongloe',        'kategori_usaha'=>'Minuman'],
['nama_umkm'=>'Tempe & Tahu Ramlah',            'kategori_usaha'=>'Kuliner Basah'],
['nama_umkm'=>'Sambal Kemasan Hasna',           'kategori_usaha'=>'Kuliner Kering'],
['nama_umkm'=>'Telur Ayam Kampung Dg. Nai',    'kategori_usaha'=>'Produk Segar'],
['nama_umkm'=>'Kerupuk Ikan Marlina',           'kategori_usaha'=>'Kuliner Kering'],
['nama_umkm'=>'Tepung Mocaf Pak Halim',         'kategori_usaha'=>'Bahan Pangan'],
['nama_umkm'=>'Sabun Herbal Dg. Ti',           'kategori_usaha'=>'Produk Rumah Tangga'],
['nama_umkm'=>'Sulam & Bordir Jumriah',         'kategori_usaha'=>'Kerajinan'],
```

### Tambahkan 5 pembeli demo + data pesanan historis 6 bulan:
```php
// Buat pesanan dengan tanggal mundur 6 bulan
// tiap UMKM minimal 5–15 pesanan Selesai agar grafik tren terlihat naik/turun
// Contoh pola:
$tanggalList = [
    now()->subMonths(5)->startOfMonth()->addDays(rand(1,28)),
    now()->subMonths(4)->startOfMonth()->addDays(rand(1,28)),
    now()->subMonths(3)->startOfMonth()->addDays(rand(1,28)),
    now()->subMonths(2)->startOfMonth()->addDays(rand(1,28)),
    now()->subMonths(1)->startOfMonth()->addDays(rand(1,28)),
    now()->startOfMonth()->addDays(rand(1,14)),
];

// Buat 3–5 pesanan per bulan per UMKM besar (umkm 1, 2, 3, 5)
// Buat 1–3 pesanan per bulan untuk UMKM baru (6–15)
// Ini memastikan grafik tren 6 bulan punya data real di setiap UMKM
```

### Tambahkan ulasan demo lebih banyak:
```php
// Minimal 3–5 ulasan per UMKM besar
// Rating variatif: 4–5 untuk produk unggulan, 2–3 untuk produk yang "perlu perhatian"
// Komentar berbahasa Indonesia yang natural
$ulasanDemo = [
    ['rating'=>5, 'komentar'=>'Pisang epennya enak dan masih hangat saat diterima.'],
    ['rating'=>4, 'komentar'=>'Jalangkotenya renyah, isian sayurnya pas.'],
    ['rating'=>5, 'komentar'=>'Kripik singkongnya renyah banget, cocok buat oleh-oleh.'],
    ['rating'=>3, 'komentar'=>'Rasanya enak tapi packaging perlu diperbaiki.'],
    ['rating'=>5, 'komentar'=>'Donatnya lembut dan enak, porsinya besar.'],
    // dst per UMKM...
];
```

---

## 📋 H. Spesifikasi Grafik di `seller/analytics.blade.php`

### Grafik 1 — Bar Chart Tren Omzet 6 Bulan
```javascript
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($trendOmzet->pluck('bulan')), // ['2026-03','2026-04',...]
        datasets: [{
            label: 'Omzet (Rp)',
            data: @json($trendOmzet->pluck('omzet')),
            backgroundColor: (ctx) => ctx.dataIndex === lastIndex ? '#10b981' : '#d1fae5',
            borderColor: '#059669',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        plugins: {
            tooltip: {
                callbacks: {
                    label: (ctx) => 'Rp' + ctx.raw.toLocaleString('id-ID')
                }
            }
        },
        scales: { y: { ticks: { callback: v => 'Rp'+v.toLocaleString('id-ID') } } }
    }
});
```

### Grafik 2 — Line Chart Tren Rating 3 Bulan
```javascript
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: @json($trendUlasan->pluck('bulan')),
        datasets: [{
            label: 'Rata-rata Rating',
            data: @json($trendUlasan->pluck('avg_rating')),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245,158,11,0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#f59e0b',
        }]
    },
    options: {
        scales: { y: { min: 0, max: 5 } }
    }
});
```

---

## 🗓️ I. Urutan Pengerjaan yang Disarankan

| # | Task | File yang Diubah/Dibuat | Est. |
|---|---|---|---|
| 1 | 3 Migration baru | `database/migrations/` (3 file baru) | 1 jam |
| 2 | Model RekomendasiStrategi + update Umkm & Produk | `app/Models/` (3 file) | 45 mnt |
| 3 | Update seeder 15 UMKM + data historis 6 bulan | `BumdesDemoSeeder.php` | 2–3 jam |
| 4 | Controller Public/UmkmController | 1 file baru | 45 mnt |
| 5 | View: `public/umkm/index.blade.php` | 1 file baru | 1.5 jam |
| 6 | View: `public/umkm/show.blade.php` | 1 file baru | 2 jam |
| 7 | Controller Seller/AnalyticsController | 1 file baru | 45 mnt |
| 8 | View: `seller/analytics.blade.php` + grafik | 1 file baru | 2.5 jam |
| 9 | Controller Admin/UmkmAnalyticsController | 1 file baru | 45 mnt |
| 10 | View: `admin/umkm/analytics.blade.php` + grafik | 1 file baru | 2 jam |
| 11 | View: `admin/umkm/rekomendasi_form.blade.php` | 1 file baru | 1 jam |
| 12 | Update routes/web.php | 1 file | 15 mnt |
| 13 | Update layouts/dashboard.blade.php (menu baru) | 1 file | 15 mnt |
| 14 | Update layouts/public.blade.php (nav Toko) | 1 file | 10 mnt |
| 15 | Update seller/dashboard.blade.php (notif rekomendasi) | 1 file | 30 mnt |
| 16 | Update Seller/DashboardController (pass $rekomendasiBelumDibaca) | 1 file | 15 mnt |
| 17 | Update public/home.blade.php (section ulasan) + HomeController | 2 file | 45 mnt |
| **Total** | | **~18 file** | **~17–19 jam** |

---

## ✅ Checklist Lengkap

**Database & Model**
- [ ] Migration: `add_promo_to_produk_table`
- [ ] Migration: `add_extras_to_umkm_table`
- [ ] Migration: `create_rekomendasi_strategi_table`
- [ ] Model baru: `RekomendasiStrategi`
- [ ] Update Model: `Umkm` (relasi rekomendasi + method belumDibaca)
- [ ] Update Model: `Produk` (method isPromoAktif, hargaEfektif, diskonPersen)

**Seeder**
- [ ] 10 user penjual baru (total 15 UMKM)
- [ ] 10 UMKM baru dengan kategori_usaha
- [ ] Produk per UMKM baru (2–3 produk masing-masing)
- [ ] Data pesanan historis 6 bulan (per UMKM)
- [ ] Data ulasan demo yang variatif (rating 2–5)
- [ ] Aktifkan kembali produk Moammar yang di-comment

**Controllers**
- [ ] `Public/UmkmController` (index + show)
- [ ] `Seller/AnalyticsController` (index)
- [ ] `Admin/UmkmAnalyticsController` (index + rekomendasiCreate + rekomendasiStore)
- [ ] Update `Seller/DashboardController` (tambah rekomendasiBelumDibaca)
- [ ] Update `Public/HomeController` (tambah ulasanTerbaru)

**Routes**
- [ ] `/toko` dan `/toko/{umkm}` (public)
- [ ] `/penjual/analitik` (seller)
- [ ] `/admin/analitik-umkm` + `/admin/analitik-umkm/{umkm}/rekomendasi` (admin)

**Views Baru**
- [ ] `public/umkm/index.blade.php`
- [ ] `public/umkm/show.blade.php`
- [ ] `seller/analytics.blade.php` (bar chart + line chart + tabel + rekomendasi)
- [ ] `admin/umkm/analytics.blade.php` (tabel growth + bar chart horizontal)
- [ ] `admin/umkm/rekomendasi_form.blade.php`

**Update Views Existing**
- [ ] `layouts/dashboard.blade.php` — menu Analitik Usaha (seller) + Analitik UMKM (admin)
- [ ] `layouts/public.blade.php` — link "Toko UMKM" di navbar
- [ ] `seller/dashboard.blade.php` — notifikasi rekomendasi baru
- [ ] `public/home.blade.php` — section ulasan pelanggan terbaru
