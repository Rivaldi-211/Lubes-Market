@extends('layouts.dashboard')

@section('title', 'Verifikasi Penjual Baru')
@section('eyebrow', 'Administrator')
@section('page_title', 'Verifikasi Penjual')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Pendaftaran Mitra</p>
        <h1>Peninjauan & Verifikasi Penjual Baru.</h1>
        <p>Periksa profil usaha, berkas KTP, dan kesiapan produk sebelum mengaktifkan akun seller.</p>
    </div>
</section>

<section class="data-panel" style="margin-bottom: 32px;">
    <div class="panel-heading">
        <div>
            <small style="color: #d97706; font-weight: 700;">MENUNGGU KEPUTUSAN ({{ $menunggu->count() }})</small>
            <h2 style="font-size: 1.2rem; font-weight: 700; margin-top: 2px;">Daftar Penjual Menunggu Verifikasi</h2>
        </div>
    </div>

    @if($menunggu->isEmpty())
        <x-empty-state title="Tidak Ada Penjual Menunggu" text="Semua pendaftaran mitra penjual baru telah diverifikasi." />
    @else
        <div style="display: flex; flex-direction: column; gap: 24px; padding: 20px;">
            @foreach($menunggu as $u)
                @php $ans = $u->sellerOnboarding?->jawaban ?? []; @endphp
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 18px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 14px;">
                        <div>
                            <span style="background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 999px; text-transform: uppercase;">
                                ⏳ Menunggu Verifikasi
                            </span>
                            <h3 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 6px 0 2px 0;">{{ $u->nama_umkm }}</h3>
                            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Pemilik: <strong>{{ $u->pemilik }}</strong> ({{ $u->user->email ?? $u->no_hp }}) · Alamat: {{ $u->alamat }}</p>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <form method="post" action="{{ route('admin.verifikasi-penjual.approve', $u) }}" onsubmit="return confirm('Setujui dan aktifkan penjual {{ $u->nama_umkm }}?')">
                                @csrf
                                <button type="submit" class="button" style="background: #166534; border-color: #166534; font-size: 13px; padding: 8px 16px;">
                                    <i class="bi bi-check-circle-fill"></i> Setujui Penjual
                                </button>
                            </form>

                            <button type="button" class="btn-danger" style="font-size: 13px; padding: 8px 16px;" onclick="const f = document.getElementById('reject-form-{{ $u->id }}'); f.style.display = (f.style.display==='none'?'block':'none');">
                                <i class="bi bi-x-circle-fill"></i> Tolak
                            </button>
                        </div>
                    </div>

                    <!-- Reject inline form -->
                    <form id="reject-form-{{ $u->id }}" method="post" action="{{ route('admin.verifikasi-penjual.reject', $u) }}" style="display: none; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 18px;">
                        @csrf
                        <label style="display: block; font-weight: 700; font-size: 13px; color: #991b1b; margin-bottom: 6px;">Alasan Penolakan:</label>
                        <textarea name="catatan" rows="2" required placeholder="Tuliskan catatan alasan penolakan agar penjual dapat memperbaiki data..." style="width: 100%; padding: 8px 12px; border: 1px solid #f87171; border-radius: 6px; margin-bottom: 10px;"></textarea>
                        <button type="submit" class="btn-danger">Konfirmasi Tolak Penjual</button>
                    </form>

                    <!-- Details Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; font-size: 13px; color: #334155;">
                        <div style="background: #f8fafc; padding: 14px; border-radius: 10px;">
                            <strong style="display: block; color: #0f172a; margin-bottom: 4px;">1. Deskripsi Produk:</strong>
                            <p style="margin: 0; color: #475569;">{{ $ans['deskripsi_produk'] ?? '-' }}</p>
                        </div>

                        <div style="background: #f8fafc; padding: 14px; border-radius: 10px;">
                            <strong style="display: block; color: #0f172a; margin-bottom: 4px;">2. Kapasitas & 24 Jam:</strong>
                            <p style="margin: 0; color: #475569;">Kapasitas: {{ number_format($ans['kapasitas_mingguan'] ?? 0) }}/minggu · Sangggup 24j: {{ strtoupper($ans['sanggup_24jam'] ?? '-') }}</p>
                        </div>

                        <div style="background: #f8fafc; padding: 14px; border-radius: 10px;">
                            <strong style="display: block; color: #0f172a; margin-bottom: 4px;">3. Legalitas Usaha:</strong>
                            <p style="margin: 0; color: #475569;">Izin: {{ strtoupper($ans['punya_izin'] ?? 'TIDAK') }} · No: {{ $ans['nomor_izin'] ?? '-' }}</p>
                        </div>

                        <div style="background: #f8fafc; padding: 14px; border-radius: 10px;">
                            <strong style="display: block; color: #0f172a; margin-bottom: 4px;">4. Metode Packing:</strong>
                            <p style="margin: 0; color: #475569;">{{ $ans['cara_kemas'] ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Images Preview -->
                    <div style="display: flex; gap: 16px; margin-top: 16px; flex-wrap: wrap;">
                        @if(isset($ans['foto_ktp']))
                            <div>
                                <small style="display: block; font-weight: 700; color: #475569; margin-bottom: 4px;">Foto KTP:</small>
                                <a href="{{ asset('storage/' . $ans['foto_ktp']) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $ans['foto_ktp']) }}" alt="Foto KTP" style="width: 140px; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                                </a>
                            </div>
                        @endif
                        @if(isset($ans['foto_produk']))
                            <div>
                                <small style="display: block; font-weight: 700; color: #475569; margin-bottom: 4px;">Foto Sampel Produk:</small>
                                <a href="{{ asset('storage/' . $ans['foto_produk']) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $ans['foto_produk']) }}" alt="Foto Produk" style="width: 140px; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

<!-- History Table -->
<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small style="color: #64748b;">RIWAYAT VERIFIKASI</small>
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-top: 2px;">Riwayat Keputusan Verifikasi</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>UMKM / Pemilik</th>
                    <th>Status</th>
                    <th>Tanggal Verifikasi</th>
                    <th>Diverifikasi Oleh</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $r)
                    <tr>
                        <td>
                            <strong>{{ $r->nama_umkm }}</strong>
                            <br><small style="color: #64748b;">Pemilik: {{ $r->pemilik }}</small>
                        </td>
                        <td>
                            @if($r->status_verifikasi === 'disetujui')
                                <span style="background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px;">✓ Disetujui</span>
                            @else
                                <span style="background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px;">✕ Ditolak</span>
                            @endif
                        </td>
                        <td>{{ optional($r->verified_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $r->verifier->nama_lengkap ?? '-' }}</td>
                        <td><small style="color: #64748b;">{{ $r->catatan_verifikasi ?: '-' }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #9ca3af; padding: 16px;">Belum ada riwayat verifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $riwayat->links() }}</div>
</section>
@endsection
