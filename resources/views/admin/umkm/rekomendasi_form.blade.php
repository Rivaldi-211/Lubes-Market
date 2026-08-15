@extends('layouts.dashboard')
@section('title', 'Kirim Rekomendasi — ' . $umkm->nama_umkm)
@section('eyebrow', 'Pengelolaan Platform')
@section('page_title', 'Form Rekomendasi Strategi')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Akselerasi Usaha Mitra</p>
        <h1>Bimbingan Strategi untuk {{ $umkm->nama_umkm }}</h1>
        <p>Kirim masukan konkret dari Admin untuk membantu penjual mengoptimalkan produk, harga, dan omzet.</p>
    </div>
    <a class="outline-link" href="{{ route('admin.umkm.analytics') }}">← Kembali ke Analitik</a>
</section>

<!-- Summary target UMKM -->
<div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; margin-bottom: 24px; display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #123825, #2d6a4f); color: #fff; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 800;">
        {{ strtoupper(substr($umkm->nama_umkm, 0, 1)) }}
    </div>
    <div style="flex: 1;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 4px 0; color: #111827;">{{ $umkm->nama_umkm }}</h3>
        <p style="color: #6b7280; font-size: 0.88rem; margin: 0;">Pemilik: {{ $umkm->pemilik }} · Kategori: {{ $umkm->kategori_usaha ?? 'Usaha Desa' }}</p>
    </div>
    <div style="display: flex; gap: 20px; font-size: 13px;">
        <div>
            <small style="color: #6b7280; display: block;">Omzet Bulan Ini</small>
            <strong style="color: #059669; font-size: 1rem;">Rp{{ number_format($omzetBulanIni, 0, ',', '.') }}</strong>
        </div>
        <div>
            <small style="color: #6b7280; display: block;">Rating & Feedback</small>
            <strong style="color: #d97706; font-size: 1rem;"><i class="bi bi-star-fill"></i> {{ number_format($avgRating, 1) }} <span style="font-size:11px; color:#6b7280;">({{ $totalUlasan }} ulasan)</span></strong>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Form Create -->
    <section class="data-panel" style="padding: 28px;">
        <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 20px 0;">Form Rekomendasi Baru</h2>

        <form action="{{ route('admin.umkm.rekomendasi.store', $umkm) }}" method="post" style="display: flex; flex-direction: column; gap: 18px;">
            @csrf

            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151;">Judul Rekomendasi</label>
                <input type="text" name="judul" class="form-control" placeholder="Contoh: Buat Paket Promo Bundling Akhir Bulan" value="{{ old('judul') }}" required style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151;">Fokus Strategi (Tipe)</label>
                    <select name="tipe" class="form-control" required style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px;">
                        <option value="promosi" @selected(old('tipe') === 'promosi')>Promosi &amp; Diskon</option>
                        <option value="produk" @selected(old('tipe') === 'produk')>Inovasi &amp; Kemasan Produk</option>
                        <option value="harga" @selected(old('tipe') === 'harga')>Penyesuaian Harga</option>
                        <option value="distribusi" @selected(old('tipe') === 'distribusi')>Jangkauan &amp; Distribusi</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151;">Periode (YYYY-MM)</label>
                    <input type="text" name="periode" class="form-control" value="{{ old('periode', date('Y-m')) }}" required style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151;">Isi Rekomendasi / Saran Akselerasi</label>
                <textarea name="isi" rows="6" class="form-control" placeholder="Tuliskan arahan strategi yang jelas dan dapat diterapkan oleh mitra UMKM..." required style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-family: inherit;">{{ old('isi') }}</textarea>
            </div>

            <div style="margin-top: 10px; display: flex; gap: 12px;">
                <button type="submit" class="btn-primary" style="padding: 10px 20px; border-radius: 8px; font-weight: 700;">
                    <i class="bi bi-send-fill"></i> Kirim Rekomendasi
                </button>
            </div>
        </form>
    </section>

    <!-- History list -->
    <section class="data-panel" style="padding: 28px;">
        <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 20px 0;">Riwayat Rekomendasi Terkirim</h2>

        @if($history->isEmpty())
            <p style="color: #9ca3af; font-style: italic;">Belum ada rekomendasi yang dikirimkan ke UMKM ini.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 16px;">
                @foreach($history as $r)
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <strong style="font-size: 0.95rem; color: #111827;">{{ $r->judul }}</strong>
                            <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; background: #e5e7eb; color: #374151;">{{ strtoupper($r->tipe) }}</span>
                        </div>
                        <small style="color: #6b7280; display: block; margin-bottom: 8px;">Periode: {{ $r->periode }} · Status: {{ $r->dibaca ? '✓ Dibaca' : '• Belum Dibaca' }}</small>
                        <p style="color: #4b5563; font-size: 0.88rem; margin: 0; line-height: 1.5;">{{ $r->isi }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
