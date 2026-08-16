@extends('layouts.dashboard')

@section('title', 'Saldo & Pencairan Dana')
@section('eyebrow', 'Mitra UMKM')
@section('page_title', 'Saldo & Pencairan Usaha')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Keuangan Usaha — {{ $umkm->nama_umkm }}</p>
        <h1>Kelola Saldo &amp; Permintaan Pencairan Dana.</h1>
        <p>Pendapatan bersih dari pesanan berstatus Selesai masuk ke saldo Anda dan dapat dicairkan langsung ke rekening bank usaha.</p>
    </div>
    <button type="button" class="button" onclick="document.getElementById('modalTambahRekening').style.display='flex'">
        <i class="bi bi-bank"></i> Tambah Rekening Bank
    </button>
</section>

<!-- Active Request Banner if any -->
@if($pengajuanAktif)
<div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 42px; height: 42px; background: #f59e0b; color: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div>
            <strong style="color: #92400e; font-size: 1rem; display: block;">
                Pengajuan Pencairan Sedang Diproses (<code>#DISB-{{ str_pad($pengajuanAktif->id, 5, '0', STR_PAD_LEFT) }}</code>)
            </strong>
            <span style="color: #b45309; font-size: 0.88rem;">
                Nominal: <b>Rp{{ number_format((float)$pengajuanAktif->jumlah, 0, ',', '.') }}</b> ke {{ $pengajuanAktif->formatted_rekening_snapshot }} · Diajukan pada {{ optional($pengajuanAktif->diajukan_at)->format('d/m/Y H:i') ?: $pengajuanAktif->created_at->format('d/m/Y H:i') }}.
            </span>
        </div>
    </div>
    <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 12px; padding: 6px 12px; border-radius: 20px; font-weight: 700;">
        <i class="bi bi-clock-history"></i> Menunggu Transfer Admin
    </span>
</div>
@endif

<!-- Metric Grid -->
<div class="metric-grid" style="margin-bottom: 28px;">
    <article style="border-left: 4px solid #059669; background: #ffffff;">
        <small style="color: #059669; font-weight: 700;">SALDO SIAP DICAIRKAN</small>
        <strong style="color: #065f46; font-size: 1.55rem;">Rp{{ number_format($saldoTersedia, 0, ',', '.') }}</strong>
        <span style="color: #64748b; font-size: 12px;">Dari {{ $pesananSiapCair->count() }} pesanan berstatus Selesai</span>
    </article>

    <article style="border-left: 4px solid #f59e0b; background: #ffffff;">
        <small style="color: #d97706; font-weight: 700;">SEDANG DALAM PENGAJUAN</small>
        <strong style="color: #b45309; font-size: 1.55rem;">Rp{{ number_format($saldoDiajukan, 0, ',', '.') }}</strong>
        <span style="color: #64748b; font-size: 12px;">Menunggu verifikasi transfer oleh Admin</span>
    </article>

    <article style="border-left: 4px solid #2563eb; background: #ffffff;">
        <small style="color: #2563eb; font-weight: 700;">TOTAL TELAH DICAIRKAN</small>
        <strong style="color: #1e40af; font-size: 1.55rem;">Rp{{ number_format($saldoDicairkan, 0, ',', '.') }}</strong>
        <span style="color: #64748b; font-size: 12px;">Telah berhasil ditransfer ke rekening usaha</span>
    </article>
</div>

<!-- Main Action Grid -->
<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; margin-bottom: 32px;" class="saldo-action-grid">
    <!-- Form Pengajuan -->
    <section class="data-panel" style="margin-bottom: 0;">
        <div class="panel-heading">
            <div>
                <small style="color: #059669; font-weight: 700;">PENGAJUAN DANA</small>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin-top: 2px;">Ajukan Pencairan Saldo</h2>
            </div>
        </div>

        <div style="padding: 20px;">
            @if($pengajuanAktif)
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center;">
                    <i class="bi bi-clock-history" style="font-size: 2rem; color: #94a3b8; display: block; margin-bottom: 8px;"></i>
                    <h4 style="font-size: 1rem; color: #334155; margin-bottom: 4px;">Pengajuan Anda Masih Aktif</h4>
                    <p style="font-size: 0.88rem; color: #64748b; margin: 0;">Anda dapat mengajukan pencairan baru setelah admin menyelesaikan atau memproses pengajuan sebelumnya.</p>
                </div>
            @elseif($saldoTersedia <= 0)
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center;">
                    <i class="bi bi-wallet-fill" style="font-size: 2rem; color: #94a3b8; display: block; margin-bottom: 8px;"></i>
                    <h4 style="font-size: 1rem; color: #334155; margin-bottom: 4px;">Tidak Ada Saldo yang Siap Dicairkan</h4>
                    <p style="font-size: 0.88rem; color: #64748b; margin: 0;">Saldo akan otomatis terkumpul saat ada pesanan masuk yang sudah berstatus <b>Selesai</b> (diterima pembeli).</p>
                </div>
            @elseif($rekeningBankList->isEmpty())
                <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.8rem; color: #e11d48; display: block; margin-bottom: 6px;"></i>
                    <h4 style="font-size: 0.98rem; color: #9f1239; margin-bottom: 4px;">Belum Ada Rekening Bank</h4>
                    <p style="font-size: 0.85rem; color: #be123c; margin-bottom: 14px;">Silakan tambahkan rekening bank usaha Anda terlebih dahulu agar dana dapat ditransfer.</p>
                    <button type="button" class="button" style="font-size: 12px; padding: 6px 14px;" onclick="document.getElementById('modalTambahRekening').style.display='flex'">
                        <i class="bi bi-plus-lg"></i> Tambah Rekening Sekarang
                    </button>
                </div>
            @else
                <form method="post" action="{{ route('seller.saldo.ajukan') }}" id="formAjukanPencairan">
                    @csrf
                    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 14px; margin-bottom: 18px;">
                        <small style="color: #065f46; font-weight: 700; text-transform: uppercase;">Total Dana yang Akan Dicairkan</small>
                        <div style="font-size: 1.45rem; font-weight: 800; color: #047857; margin-top: 2px;">
                            Rp{{ number_format($saldoTersedia, 0, ',', '.') }}
                        </div>
                        <small style="color: #047857; font-size: 11px;">Mencakup {{ $pesananSiapCair->count() }} transaksi pesanan selesai</small>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.88rem; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
                            Pilih Rekening Bank Tujuan <span style="color: #e11d48;">*</span>
                        </label>
                        <select name="rekening_bank_id" class="form-control" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                            @foreach($rekeningBankList as $bank)
                                <option value="{{ $bank->id }}" @selected(old('rekening_bank_id') == $bank->id || $loop->first)>
                                    {{ $bank->nama_bank }} — {{ $bank->nomor_rekening }} (a.n. {{ $bank->atas_nama }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.88rem; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
                            Catatan Pengajuan (Opsional)
                        </label>
                        <textarea name="catatan" rows="2" class="form-control" placeholder="Contoh: Mohon diproses sebelum jam 16.00 WITA" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit;">{{ old('catatan') }}</textarea>
                    </div>

                    <button type="submit" class="button" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700; font-size: 0.95rem; border-radius: 8px;" id="btnSubmitPencairan">
                        <i class="bi bi-send-check"></i> Ajukan Pencairan Sekarang
                    </button>
                </form>
            @endif
        </div>
    </section>

    <!-- Daftar Rekening Bank UMKM -->
    <section class="data-panel" style="margin-bottom: 0;">
        <div class="panel-heading">
            <div>
                <small style="color: #64748b; font-weight: 700;">REKENING PENERIMA</small>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin-top: 2px;">Rekening Bank Terdaftar</h2>
            </div>
            <button type="button" class="btn-secondary" style="font-size: 11px; padding: 5px 10px;" onclick="document.getElementById('modalTambahRekening').style.display='flex'">
                <i class="bi bi-plus-lg"></i> Tambah
            </button>
        </div>

        <div style="padding: 16px;">
            @forelse($rekeningBankList as $acc)
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 36px; height: 36px; background: #e0f2fe; color: #0369a1; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                            <i class="bi bi-bank"></i>
                        </div>
                        <div>
                            <strong style="color: #0f172a; font-size: 0.92rem; display: block;">{{ $acc->nama_bank }}</strong>
                            <span style="color: #475569; font-size: 0.85rem; font-family: monospace; letter-spacing: 0.5px;">{{ $acc->nomor_rekening }}</span>
                            <small style="color: #64748b; display: block; font-size: 11px;">a.n. {{ $acc->atas_nama }}</small>
                        </div>
                    </div>
                    <div>
                        <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 10px; padding: 3px 8px; border-radius: 6px; font-weight: 600;">Aktif</span>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: #94a3b8; padding: 24px;">
                    <i class="bi bi-credit-card-2-front" style="font-size: 2rem; display: block; margin-bottom: 6px;"></i>
                    <p style="font-size: 0.88rem; margin: 0;">Belum ada rekening bank yang didaftarkan.</p>
                </div>
            @endforelse

            <div style="margin-top: 14px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 8px; padding: 10px 12px; display: flex; gap: 8px; font-size: 11px; color: #166534;">
                <i class="bi bi-info-circle-fill" style="flex-shrink: 0; color: #15803d; font-size: 13px;"></i>
                <span>Transfer dilakukan manual oleh Admin LUDES-MARKET ke rekening terdaftar di atas setelah pengajuan disetujui.</span>
            </div>
        </div>
    </section>
</div>

<!-- History Table -->
<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small style="color: #64748b; font-weight: 700;">RIWAYAT PENCAIRAN</small>
            <h2 style="font-size: 1.15rem; font-weight: 700; margin-top: 2px;">Riwayat Pengajuan &amp; Transfer Saldo</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID &amp; Tanggal</th>
                    <th>Rekening Tujuan</th>
                    <th>Jumlah Dicairkan</th>
                    <th>Status</th>
                    <th>Diproses Oleh</th>
                    <th>Catatan / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $r)
                    <tr>
                        <td>
                            <strong><code>#DISB-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }}</code></strong>
                            <br>
                            <small style="color: #64748b;">
                                Diajukan: {{ optional($r->diajukan_at)->format('d/m/Y H:i') ?: $r->created_at->format('d/m/Y H:i') }}
                            </small>
                        </td>
                        <td>
                            <strong>{{ $r->formatted_rekening_snapshot }}</strong>
                        </td>
                        <td>
                            <strong style="color: #059669; font-size: 1.05rem;">Rp{{ number_format((float)$r->jumlah, 0, ',', '.') }}</strong>
                            <br>
                            <small style="color: #64748b;">{{ $r->pesanan->count() }} pesanan</small>
                        </td>
                        <td>
                            @if($r->status === 'dibayar')
                                <span class="badge" style="background: #dcfce7; color: #15803d; padding: 5px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="bi bi-check-circle-fill"></i> Sudah Ditransfer
                                </span>
                                @if($r->dibayar_at)
                                    <br><small style="color: #64748b; font-size: 10px;">{{ $r->dibayar_at->format('d/m/Y H:i') }}</small>
                                @endif
                            @elseif($r->status === 'ditolak')
                                <span class="badge" style="background: #ffe4e6; color: #be123c; padding: 5px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="bi bi-x-circle-fill"></i> Ditolak
                                </span>
                                @if($r->ditolak_at)
                                    <br><small style="color: #64748b; font-size: 10px;">{{ $r->ditolak_at->format('d/m/Y H:i') }}</small>
                                @endif
                            @else
                                <span class="badge" style="background: #fef3c7; color: #92400e; padding: 5px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="bi bi-clock-history"></i> Menunggu Admin
                                </span>
                            @endif
                        </td>
                        <td>
                            {{ $r->admin->nama_lengkap ?? '-' }}
                        </td>
                        <td>
                            <small style="color: #475569;">{{ $r->catatan ?: '-' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">Belum ada riwayat pengajuan pencairan dana.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $riwayat->links() }}</div>
</section>

<!-- Modal Tambah Rekening Bank -->
<div id="modalTambahRekening" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(2px);">
    <div style="background: #ffffff; border-radius: 16px; max-width: 460px; width: 100%; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); animation: fadeInScale 0.2s ease;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-bank" style="color: #059669;"></i> Tambah Rekening Bank
            </h3>
            <button type="button" onclick="document.getElementById('modalTambahRekening').style.display='none'" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="post" action="{{ route('seller.saldo.rekening.store') }}">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Nama Bank <span style="color: #e11d48;">*</span></label>
                <input type="text" name="nama_bank" class="form-control" placeholder="Contoh: Bank BRI, Bank BCA, Bank BNI" required style="width: 100%; padding: 9px; border-radius: 8px; border: 1px solid #cbd5e1;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Nomor Rekening <span style="color: #e11d48;">*</span></label>
                <input type="text" name="nomor_rekening" class="form-control" placeholder="Contoh: 0234-01-001892-53-4" required style="width: 100%; padding: 9px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: monospace;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Nama Pemilik Rekening (a.n.) <span style="color: #e11d48;">*</span></label>
                <input type="text" name="atas_nama" class="form-control" placeholder="Contoh: {{ $umkm->pemilik }}" required style="width: 100%; padding: 9px; border-radius: 8px; border: 1px solid #cbd5e1;">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalTambahRekening').style.display='none'" style="padding: 8px 16px; border-radius: 8px;">
                    Batal
                </button>
                <button type="submit" class="button" style="padding: 8px 18px; border-radius: 8px;">
                    <i class="bi bi-save"></i> Simpan Rekening
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .saldo-action-grid {
        grid-template-columns: 1fr !important;
    }
}
@keyframes fadeInScale {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formAjukanPencairan');
    const btn = document.getElementById('btnSubmitPencairan');
    if (form && btn) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Apakah Anda yakin ingin mengajukan pencairan saldo sebesar Rp{{ number_format($saldoTersedia, 0, ',', '.') }} ke rekening bank yang dipilih?')) {
                e.preventDefault();
                return false;
            }
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Memproses...';
        });
    }
});
</script>
@endsection
