@extends('layouts.public')

@section('title', 'Toko UMKM Desa — LUDES-MARKET')

@section('content')
<div class="shell" style="padding-top: 48px; padding-bottom: 72px;">
    <div class="section-heading" style="margin-bottom: 32px;">
        <div>
            <div class="eyebrow"><span></span>Mitra UMKM Desa</div>
            <h1 style="font-size: 2.2rem; font-weight: 800; color: #111827;">Toko &amp; Produsen Lokal Desa</h1>
            <p style="color: #6b7280; font-size: 1.05rem; margin-top: 6px;">Jelajahi karya, camilan, dan usaha warga Moncongloe Lappara langsung dari pembuatnya.</p>
        </div>
    </div>

    @if($categories->isNotEmpty())
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 36px;">
        <a href="{{ route('umkm.index') }}" class="button {{ !$category ? '' : 'button-secondary' }}" style="padding: 8px 20px; font-size: 13px; border-radius: 999px; font-weight: 600;">Semua Toko</a>
        @foreach($categories as $cat)
            <a href="{{ route('umkm.index', ['kategori' => $cat]) }}" class="button {{ $category === $cat ? '' : 'button-secondary' }}" style="padding: 8px 20px; font-size: 13px; border-radius: 999px; font-weight: 600;">{{ $cat }}</a>
        @endforeach
    </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 28px;">
        @forelse($umkms as $u)
            @php
                $avgRate = $u->avg_rating ? number_format((float)$u->avg_rating, 1) : null;
            @endphp
            <a href="{{ route('umkm.show', $u) }}" class="umkm-interactive-card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative;">
                
                <!-- Accent Bar -->
                <div style="height: 4px; background: linear-gradient(90deg, #123825 0%, #2d6a4f 100%);"></div>

                <div style="padding: 24px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; gap: 20px;">
                    <div>
                        <!-- Header row: Avatar & Category -->
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div style="width: 50px; height: 50px; background: #f4efe6; border: 1.5px solid #d4af37; color: #123825; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; box-shadow: inset 0 0 6px rgba(0,0,0,0.03); flex-shrink: 0;">
                                    {{ strtoupper(substr($u->nama_umkm, 0, 1)) }}
                                </div>
                                <div>
                                    <span style="font-size: 11px; font-weight: 700; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-bottom: 4px;">
                                        {{ $u->kategori_usaha ?? 'Usaha Desa' }}
                                    </span>
                                    <small style="display: block; color: #6b7280; font-size: 0.8rem; font-weight: 500;">
                                        Pemilik: {{ $u->pemilik }} @if($u->tahun_berdiri) · Est. {{ $u->tahun_berdiri }} @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Store Title -->
                        <h3 class="umkm-card-title" style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0; line-height: 1.35; transition: color 0.2s;">
                            {{ $u->nama_umkm }}
                        </h3>

                        <!-- Description -->
                        <p style="color: #475569; font-size: 0.88rem; line-height: 1.55; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $u->deskripsi }}
                        </p>
                    </div>

                    <!-- Footer Row: Rating & Products Count & CTA Link -->
                    <div style="padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            @if($avgRate)
                                <span style="font-weight: 700; color: #d97706; display: flex; align-items: center; gap: 4px;">
                                    <i class="bi bi-star-fill" style="font-size: 12px;"></i> {{ $avgRate }}
                                    <small style="color: #94a3b8; font-weight: 500;">({{ $u->total_ulasan }})</small>
                                </span>
                            @else
                                <small style="color: #94a3b8; font-style: italic;">Baru</small>
                            @endif

                            <span style="color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                <i class="bi bi-box-seam" style="color: #10b981;"></i> {{ $u->produk_count }} Produk
                            </span>
                        </div>

                        <span class="umkm-cta-btn" style="font-weight: 700; color: #123825; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                            Toko <i class="bi bi-arrow-right umkm-arrow" style="transition: transform 0.2s;"></i>
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div style="grid-column: 1 / -1;">
                <x-empty-state title="Belum Ada UMKM" text="Belum ada toko UMKM yang terdaftar di kategori ini." />
            </div>
        @endforelse
    </div>
</div>

<style>
.umkm-interactive-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px -8px rgba(18, 56, 37, 0.12) !important;
    border-color: #cbd5e1 !important;
}
.umkm-interactive-card:hover .umkm-card-title {
    color: #123825 !important;
}
.umkm-interactive-card:hover .umkm-cta-btn {
    color: #059669 !important;
}
.umkm-interactive-card:hover .umkm-arrow {
    transform: translateX(4px);
}
</style>
@endsection
