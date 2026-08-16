@extends('layouts.dashboard')
@section('title', 'Manajemen Saldo & Pencairan')
@section('eyebrow', 'Mitra Penjual UMKM')
@section('page_title', 'Saldo & Pencairan Dana')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Keuangan Toko Mitra</p>
        <h1>Saldo &amp; Pencairan Dana Hasil Penjualan</h1>
        <p>Pantau pendapatan bersih pesanan yang telah selesai, kelola rekening bank tujuan, dan ajukan pencairan saldo ke Admin.</p>
    </div>
    <a class="button button-outline" href="{{ route('seller.dashboard') }}">
        <i class="bi bi-shop"></i> Dashboard Toko
    </a>
</section>

{{-- Metric Grid --}}
<div class="metric-grid">
    <article>
        <small>SALDO SIAP DICAIRKAN</small>
        <strong style="color: #15803d;">Rp{{ number_format($saldoTersedia, 0, ',', '.') }}</strong>
        <span>Dari {{ $pesananSiapCair->count() }} Pesanan Selesai</span>
    </article>
    <article>
        <small>SEDANG DALAM PROSES</small>
        <strong style="color: #d97706;">Rp{{ number_format($saldoDiajukan, 0, ',', '.') }}</strong>
        <span>Menunggu Transfer Admin</span>
    </article>
    <article>
        <small>TOTAL TELAH DICAIRKAN</small>
        <strong>Rp{{ number_format($saldoDicairkan, 0, ',', '.') }}</strong>
        <span>Riwayat Sukses Transfer</span>
    </article>
    <article>
        <small>REKENING TERDAFTAR</small>
        <strong>{{ $rekeningBankList->count() }}</strong>
        <span>Rekening Bank Mitra</span>
    </article>
</div>

{{-- Alert Pengajuan Aktif --}}
@if($pengajuanAktif)
    <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 14px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.06);">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <strong style="color: #92400e; font-size: 0.98rem; display: block;">Pengajuan Pencairan Saldo Sedang Diproses</strong>
                <span style="color: #b45309; font-size: 0.85rem;">
                    Nominal <strong>Rp{{ number_format($pengajuanAktif->jumlah, 0, ',', '.') }}</strong> diajukan pada {{ $pengajuanAktif->diajukan_at?->format('d M Y H:i') ?? $pengajuanAktif->created_at->format('d M Y H:i') }} ke {{ $pengajuanAktif->rekening_bank_snapshot['nama_bank'] ?? $pengajuanAktif->rekeningBank?->nama_bank }} ({{ $pengajuanAktif->rekening_bank_snapshot['nomor_rekening'] ?? $pengajuanAktif->rekeningBank?->nomor_rekening }}).
                </span>
            </div>
        </div>
        <span style="background: #fef3c7; color: #92400e; font-weight: 800; font-size: 11px; padding: 6px 14px; border-radius: 999px; border: 1px solid #fde68a; text-transform: uppercase;">
            Status: {{ ucfirst($pengajuanAktif->status) }}
        </span>
    </div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 30px;">
    {{-- Form Ajukan Pencairan --}}
    <div class="form-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h2><i class="bi bi-cash-stack" style="color: #15803d;"></i> Ajukan Pencairan Dana</h2>
            <p style="font-size: 12.5px; color: #64748b; margin-top: 0; margin-bottom: 18px;">
                Cairkan saldo pendapatan dari pesanan yang sudah berstatus selesai langsung ke rekening bank usaha Anda.
            </p>

            <form method="post" action="{{ route('seller.saldo.ajukan') }}">
                @csrf
                <div class="field-grid">
                    <label class="full">Pilih Rekening Bank Tujuan <span style="color: #b91c1c;">*</span>
                        <select name="rekening_bank_id" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-weight: 600;">
                            @if($rekeningBankList->isEmpty())
                                <option value="">-- Belum ada rekening bank, tambahkan di panel samping --</option>
                            @else
                                <option value="">-- Pilih Rekening Tujuan Transfer --</option>
                                @foreach($rekeningBankList as $bank)
                                    <option value="{{ $bank->id }}">
                                        {{ $bank->nama_bank }} — {{ $bank->nomor_rekening }} (a.n. {{ $bank->atas_nama }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </label>

                    <label class="full">Catatan untuk Admin (Opsional)
                        <input type="text" name="catatan" placeholder="Contoh: Mohon diproses untuk restock bahan baku">
                    </label>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin: 16px 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 4px;">
                        <span>Saldo Bersih yang Dapat Dicairkan:</span>
                        <strong style="color: #0f172a; font-size: 14px;">Rp{{ number_format($saldoTersedia, 0, ',', '.') }}</strong>
                    </div>
                    <small style="color: #94a3b8; font-size: 11px; display: block;">*Total pencairan otomatis dihitung dari seluruh pesanan selesai yang belum pernah dicairkan.</small>
                </div>

                @if($pengajuanAktif)
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #92400e; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-hourglass-split" style="font-size: 15px; flex-shrink: 0;"></i>
                        <span>Pengajuan pencairan Anda sedang diproses oleh admin. Tombol akan aktif kembali setelah transfer selesai.</span>
                    </div>
                @elseif($saldoTersedia <= 0)
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #64748b; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-info-circle-fill" style="color: #64748b; font-size: 15px; flex-shrink: 0;"></i>
                        <span>Saldo siap cair saat ini <strong>Rp0</strong>. Saldo bertambah otomatis saat pesanan pembeli berstatus <strong>Selesai</strong>.</span>
                    </div>
                @elseif($rekeningBankList->isEmpty())
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #1e40af; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-bank" style="font-size: 15px; flex-shrink: 0;"></i>
                        <span>Silakan daftarkan rekening bank tujuan Anda pada panel sebelah kanan terlebih dahulu.</span>
                    </div>
                @endif

                @php
                    $isBlocked = ($saldoTersedia <= 0 || $pengajuanAktif || $rekeningBankList->isEmpty());
                @endphp

                <button class="button" type="submit" style="width: 100%; justify-content: center; padding: 12px 20px; font-weight: 800; {{ $isBlocked ? 'opacity: 0.6; cursor: not-allowed; filter: grayscale(0.5);' : 'cursor: pointer;' }}" {{ $isBlocked ? 'disabled' : '' }}>
                    <i class="bi bi-send-check"></i> {{ $pengajuanAktif ? 'Sedang Diproses Admin...' : ($saldoTersedia <= 0 ? 'Saldo Belum Mencukupi (Rp0)' : 'Ajukan Pencairan Sekarang') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Kelola Rekening Bank UMKM --}}
    <div class="form-card">
        <h2><i class="bi bi-bank" style="color: #2563eb;"></i> Rekening Bank Mitra UMKM</h2>
        <p style="font-size: 12.5px; color: #64748b; margin-top: 0; margin-bottom: 18px;">
            Daftar rekening bank milik toko Anda untuk menerima transfer hasil penjualan dari pengelola platform.
        </p>

        @if($rekeningBankList->isNotEmpty())
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                @foreach($rekeningBankList as $bank)
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 13.5px; color: #0f172a; display: block;">{{ $bank->nama_bank }}</strong>
                            <span style="font-size: 12px; color: #64748b;">a.n. {{ $bank->atas_nama }}</span>
                        </div>
                        <div>
                            <code style="background: #eff6ff; color: #1e40af; font-size: 13px; font-weight: 700; padding: 4px 8px; border-radius: 6px;">{{ $bank->nomor_rekening }}</code>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Form Tambah Rekening Baru --}}
        <details style="background: #fafafa; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px;">
            <summary style="cursor: pointer; font-weight: 700; font-size: 13px; color: #1e293b; outline: none;">
                <i class="bi bi-plus-circle-fill" style="color: #15803d;"></i> Tambah Rekening Bank Baru
            </summary>
            <form method="post" action="{{ route('seller.saldo.rekening.store') }}" style="margin-top: 14px;">
                @csrf
                <div class="field-grid">
                    <label>Nama Bank <span style="color: #b91c1c;">*</span>
                        <input name="nama_bank" required placeholder="Contoh: Bank BRI, Bank BCA, Bank Sulselbar">
                    </label>
                    <label>Nomor Rekening <span style="color: #b91c1c;">*</span>
                        <input name="nomor_rekening" required placeholder="Nomor rekening">
                    </label>
                    <label class="full">Nama Pemilik Rekening <span style="color: #b91c1c;">*</span>
                        <input name="atas_nama" required value="{{ old('atas_nama', $umkm->pemilik) }}" placeholder="Nama sesuai buku tabungan">
                    </label>
                </div>
                <button class="button button-outline" type="submit" style="margin-top: 12px; padding: 8px 16px; font-size: 12.5px; font-weight: 700;">
                    <i class="bi bi-save"></i> Simpan Rekening Bank
                </button>
            </form>
        </details>
    </div>
</div>

{{-- Riwayat Pencairan Dana --}}
<div class="card" style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 1.15rem; margin: 0;">Riwayat Pengajuan &amp; Pencairan Dana</h2>
        <span style="font-size: 12px; color: #64748b;">Total {{ $riwayat->total() }} Transaksi</span>
    </div>

    @if($riwayat->isEmpty())
        <div style="text-align: center; padding: 36px 20px; color: #94a3b8;">
            <i class="bi bi-clock-history" style="font-size: 38px; display: block; margin-bottom: 8px; opacity: 0.6;"></i>
            Belum ada riwayat pencairan dana untuk toko Anda.
        </div>
    @else
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; color: #64748b; font-size: 11.5px; text-transform: uppercase;">
                        <th style="padding: 10px 14px;">Tanggal</th>
                        <th style="padding: 10px 14px;">Nominal</th>
                        <th style="padding: 10px 14px;">Rekening Tujuan</th>
                        <th style="padding: 10px 14px;">Status</th>
                        <th style="padding: 10px 14px;">Keterangan / Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riwayat as $row)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px; white-space: nowrap;">
                                <strong>{{ $row->created_at->format('d M Y') }}</strong>
                                <small style="display: block; color: #94a3b8; font-size: 11px;">{{ $row->created_at->format('H:i') }} WITA</small>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 800; color: #0f172a; white-space: nowrap;">
                                Rp{{ number_format($row->jumlah, 0, ',', '.') }}
                            </td>
                            <td style="padding: 12px 14px;">
                                <strong style="color: #1e293b; display: block;">{{ $row->rekening_bank_snapshot['nama_bank'] ?? $row->rekeningBank?->nama_bank ?? 'Rekening Bank' }}</strong>
                                <small style="color: #64748b;">{{ $row->rekening_bank_snapshot['nomor_rekening'] ?? $row->rekeningBank?->nomor_rekening }} (a.n. {{ $row->rekening_bank_snapshot['atas_nama'] ?? $row->rekeningBank?->atas_nama }})</small>
                            </td>
                            <td style="padding: 12px 14px;">
                                @if($row->status === 'dibayar')
                                    <span style="background: #dcfce7; color: #15803d; font-weight: 800; font-size: 10.5px; padding: 4px 10px; border-radius: 999px;">DIBAYAR</span>
                                @elseif($row->status === 'ditolak')
                                    <span style="background: #fee2e2; color: #b91c1c; font-weight: 800; font-size: 10.5px; padding: 4px 10px; border-radius: 999px;">DITOLAK</span>
                                @elseif($row->status === 'diproses')
                                    <span style="background: #eff6ff; color: #1d4ed8; font-weight: 800; font-size: 10.5px; padding: 4px 10px; border-radius: 999px;">DIPROSES</span>
                                @else
                                    <span style="background: #fef3c7; color: #92400e; font-weight: 800; font-size: 10.5px; padding: 4px 10px; border-radius: 999px;">DIAJUKAN</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px; color: #64748b; font-size: 12px;">
                                {{ $row->catatan ?: ($row->catatan_admin ?: '-') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $riwayat->links() }}
        </div>
    @endif
</div>
@endsection
