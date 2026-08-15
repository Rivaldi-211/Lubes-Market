@extends('layouts.public')

@section('title', 'Keroyokan — ' . $kelompok->nama_kelompok)

@section('content')
@push('head')
<style>
    .keroyokan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
    .keroyokan-confirm-row { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 2px solid var(--line); gap: 16px; flex-wrap: wrap; }
    .keroyokan-sim-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .keroyokan-sim-table { width: 100%; border-collapse: collapse; min-width: 400px; }
    @media (max-width: 700px) {
        .keroyokan-grid { grid-template-columns: 1fr; }
        .keroyokan-confirm-row { flex-direction: column; align-items: stretch; text-align: center; }
        .keroyokan-confirm-row .button { width: 100%; justify-content: center; }
        .keroyokan-confirm-row > div { text-align: center; }
    }
</style>
@endpush
<section class="public-hero" style="background:var(--green-950); color:var(--white); padding: 40px 0;">
    <div class="shell">
        <a href="{{ route('keroyokan.index') }}" style="color:var(--gold); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Keroyokan
        </a>
        <span class="eyebrow" style="color:var(--gold); display:block; font-weight:700; margin-bottom:6px;">{{ $kelompok->kategori->nama_kategori }}</span>
        <h1 style="font-size: 2.2rem; margin:0 0 10px; font-family:var(--display);">{{ $kelompok->nama_kelompok }}</h1>
        <p style="opacity:0.85; max-width:640px; margin:0;">
            {{ $kelompok->deskripsi ?: 'Kelompok produk setara gabungan UMKM Desa Moncongloe Lappara.' }}
        </p>
    </div>
</section>

<section class="section">
    <div class="shell" style="max-width:960px;">
        <x-flash/>

        @if($totalStock === 0)
            <div style="background:#f8d7da; color:#721c24; padding:20px; border-radius:12px; margin-bottom:24px; border:1px solid #f5c6cb;">
                <h4 style="margin:0 0 6px; font-weight:700;"><i class="bi bi-exclamation-triangle-fill"></i> Stok Keroyokan Sedang Habis</h4>
                <p style="margin:0;">Saat ini seluruh UMKM anggota untuk kelompok ini belum memiliki ketersediaan stok. Pemesanan Keroyokan belum dapat dilakukan.</p>
            </div>
        @endif

        <div class="keroyokan-grid">
            <div style="background:#fff; border:1px solid var(--line); border-radius:var(--radius); padding:24px;">
                <h3 style="margin:0 0 16px; font-size:1.1rem;"><i class="bi bi-shop" style="color:var(--gold)"></i> UMKM Anggota & Stok</h3>
                <ul style="list-style:none; padding:0; margin:0;">
                    @foreach($allProducts as $p)
                        @php
                            $pAvailable = ($p->stok_status !== 'Habis' && $p->stok_jumlah > 0);
                        @endphp
                        <li style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f0f0f0; {{ !$pAvailable ? 'opacity:0.65;' : '' }}">
                            <div>
                                <strong>{{ $p->nama_produk }}</strong>
                                <small style="display:block; color:var(--muted)">{{ $p->umkm->nama_umkm }}</small>
                            </div>
                            <div style="text-align:right;">
                                <strong>Rp{{ number_format((float)$p->harga,0,',','.') }}</strong>
                                @if($pAvailable)
                                    <small style="display:block; color:var(--green-700); font-weight:600;">Stok: {{ number_format($p->stok_jumlah) }}</small>
                                @else
                                    <small style="display:block; color:#dc2626; font-weight:700;"><i class="bi bi-exclamation-circle-fill"></i> Stok Habis</small>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div style="margin-top:16px; padding-top:12px; border-top:2px solid var(--line); display:flex; justify-content:space-between;">
                    <span>Total Stok Gabungan:</span>
                    <strong style="font-size:1.1rem; color:{{ $totalStock > 0 ? 'var(--green-900)' : '#d9534f' }};">
                        {{ number_format($totalStock) }} unit {{ $totalStock === 0 ? '(Habis)' : '' }}
                    </strong>
                </div>
            </div>

            <div style="background:#fff; border:1px solid var(--line); border-radius:var(--radius); padding:24px;">
                <h3 style="margin:0 0 16px; font-size:1.1rem;"><i class="bi bi-calculator" style="color:var(--gold)"></i> Cek Alokasi Pesanan</h3>
                <form method="post" action="{{ route('keroyokan.simulate', $kelompok) }}">
                    @csrf
                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-weight:600; margin-bottom:8px;">Jumlah Dibutuhkan (unit)</label>
                        <input type="number" name="target_jumlah" value="{{ old('target_jumlah', $inputQuantity ?? 250) }}" min="2" style="width:100%; padding:12px 16px; border:1px solid var(--line); border-radius:12px; font-size:1rem; font-weight:700;" {{ $totalStock === 0 ? 'disabled' : '' }} required>
                        <small style="color:var(--muted); display:block; margin-top:6px;">Minimal 2 unit dan melebihi kapasitas stok 1 UMKM tunggal.</small>
                    </div>

                    <button type="submit" class="button wide" {{ $totalStock === 0 ? 'disabled style=background:var(--muted);cursor:not-allowed;' : '' }}>
                        <i class="bi bi-search"></i> {{ $totalStock === 0 ? 'STOK KEROYOKAN HABIS' : 'CEK KEROYOKAN' }}
                    </button>
                </form>
                <small style="display:block; margin-top:12px; color:var(--muted); font-size:0.82rem; text-align:center;">
                    *Stok divalidasi secara real-time dari database.
                </small>
            </div>
        </div>

        @if(isset($simulation))
            <div style="background:#fff; border:2px solid var(--green-700); border-radius:var(--radius); padding:28px;">
                <h2 style="margin:0 0 16px; font-family:var(--display); font-size:1.4rem; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-people-fill" style="color:var(--green-700)"></i> RENCANA KEROYOKAN
                </h2>

                @if($simulation['status'] === 'success')
                    <div style="margin-bottom:20px;">
                        <p style="margin:0 0 8px; font-size:1rem;"><strong>Target:</strong> {{ number_format($simulation['target_quantity']) }} unit</p>
                        
                        <div style="background:#f8f9fa; border-radius:12px; padding:16px; margin:16px 0;">
                        <div class="keroyokan-sim-table-wrap">
                            <table class="keroyokan-sim-table">
                                <thead>
                                    <tr style="border-bottom:1px solid #ddd; text-align:left;">
                                        <th style="padding:8px 0;">UMKM / Produk</th>
                                        <th style="padding:8px 0; text-align:center;">Alokasi</th>
                                        <th style="padding:8px 0; text-align:right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($simulation['allocations'] as $alloc)
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:10px 0;">
                                                <strong>{{ $alloc['product']->umkm->nama_umkm }}</strong>
                                                <small style="display:block; color:var(--muted)">{{ $alloc['product']->nama_produk }} (Rp{{ number_format($alloc['unit_price'],0,',','.') }}/unit)</small>
                                            </td>
                                            <td style="padding:10px 0; text-align:center;">
                                                <strong>{{ number_format($alloc['quantity']) }} box</strong>
                                            </td>
                                            <td style="padding:10px 0; text-align:right;">
                                                <strong>Rp{{ number_format($alloc['line_total'],0,',','.') }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        </div>

                        <div style="margin:20px 0; padding:16px; background:#eef7f2; border-radius:12px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-weight:700;">
                                <span>{{ number_format($simulation['target_quantity']) }} / {{ number_format($simulation['target_quantity']) }} unit</span>
                                <span style="color:var(--green-700)">100% Terpenuhi</span>
                            </div>
                            <div style="background:#c3e6cb; height:12px; border-radius:6px; overflow:hidden;">
                                <div style="background:var(--green-700); width:100%; height:100%;"></div>
                            </div>
                            <p style="margin:8px 0 0; color:var(--green-900); font-weight:600; font-size:0.9rem;">
                                ✓ Kapasitas stok terpenuhi oleh {{ $simulation['distinct_umkms_count'] }} UMKM berbeda.
                            </p>
                        </div>

                        <div class="keroyokan-confirm-row">
                            <div>
                                <small style="display:block; color:var(--muted)">TOTAL PEMBAYARAN</small>
                                <strong style="font-size:1.6rem; color:var(--green-950);">Rp{{ number_format($simulation['grand_total'],0,',','.') }}</strong>
                            </div>

                            @auth
                                @if(auth()->user()->isBuyer())
                                    <form method="post" action="{{ route('keroyokan.confirm', $kelompok) }}">
                                        @csrf
                                        <input type="hidden" name="target_jumlah" value="{{ $simulation['target_quantity'] }}">
                                        <button type="submit" class="button" style="padding:14px 28px; font-size:1.05rem;">
                                            <i class="bi bi-cart-check-fill"></i> LANJUTKAN KE KERANJANG
                                        </button>
                                    </form>
                                @else
                                    <p style="color:var(--muted); margin:0;">Silakan login sebagai akun <strong>Pembeli</strong> untuk memesan.</p>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="button">
                                    <i class="bi bi-box-arrow-in-right"></i> Login Pembeli untuk Memesan
                                </a>
                            @endauth
                        </div>
                    </div>
                @elseif($simulation['status'] === 'single_umkm_sufficient')
                    <div style="background:#fff3cd; color:#856404; padding:16px; border-radius:12px; margin-bottom:16px;">
                        <i class="bi bi-info-circle-fill"></i> {{ $simulation['message'] }}
                    </div>
                @elseif($simulation['status'] === 'insufficient_stock')
                    <div style="background:#f8d7da; color:#721c24; padding:20px; border-radius:12px; margin-bottom:16px;">
                        <h4 style="margin:0 0 8px;"><i class="bi bi-exclamation-triangle-fill"></i> Keroyokan Belum Dapat Memenuhi Permintaan</h4>
                        <p style="margin:0 0 8px;">Tersedia: <strong>{{ number_format($simulation['available']) }}</strong> dari <strong>{{ number_format($inputQuantity) }}</strong> unit</p>
                        <p style="margin:0;">Kekurangan: <strong>{{ number_format($simulation['shortage']) }}</strong> unit</p>
                    </div>
                @else
                    <div style="background:#f8d7da; color:#721c24; padding:16px; border-radius:12px;">
                        {{ $simulation['message'] }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection
