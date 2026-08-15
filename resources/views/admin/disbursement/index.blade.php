@extends('layouts.dashboard')

@section('title', 'Pencairan Dana UMKM (Disbursement)')
@section('eyebrow', 'Administrator')
@section('page_title', 'Disbursement UMKM')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Keuangan Platform</p>
        <h1>Pencairan Saldo &amp; Komisi Platform (10%).</h1>
        <p>Dana pembayaran pembeli masuk terpusat ke Admin. Cairkan pendapatan bersih ke penjual setelah pesanan selesai dan penjual mengajukan pencairan.</p>
    </div>
</section>

<!-- 1. Permintaan Masuk dari Mitra UMKM -->
<section class="data-panel" style="margin-bottom: 32px; border-top: 4px solid #f59e0b;">
    <div class="panel-heading">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div>
                <small style="color: #d97706; font-weight: 700;">PERMINTAAN MASUK DARI PENJUAL</small>
                <h2 style="font-size: 1.2rem; font-weight: 700; margin-top: 2px;">Daftar Pengajuan Pencairan Dana</h2>
            </div>
            @if($permintaanMasuk->count() > 0)
                <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 800; font-size: 12px; padding: 4px 10px; border-radius: 12px;">
                    {{ $permintaanMasuk->count() }} Pengajuan Baru
                </span>
            @endif
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID &amp; Tanggal</th>
                    <th>Mitra UMKM</th>
                    <th>Rekening Tujuan Transfer</th>
                    <th>Nominal Pengajuan</th>
                    <th>Catatan Penjual</th>
                    <th>Aksi Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permintaanMasuk as $p)
                    <tr>
                        <td>
                            <strong><code>#DISB-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</code></strong>
                            <br>
                            <small style="color: #64748b;">
                                {{ optional($p->diajukan_at)->format('d/m/Y H:i') ?: $p->created_at->format('d/m/Y H:i') }}
                            </small>
                        </td>
                        <td>
                            <strong>{{ $p->umkm->nama_umkm ?? '-' }}</strong>
                            <br>
                            <small style="color: #64748b;">Pemilik: {{ $p->umkm->pemilik ?? '-' }}</small>
                        </td>
                        <td>
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; display: inline-flex; align-items: center; gap: 8px;">
                                <div>
                                    <strong style="color: #0f172a; font-size: 0.9rem; display: block;">
                                        {{ $p->formatted_rekening_snapshot }}
                                    </strong>
                                </div>
                                @if(is_array($p->rekening_bank_snapshot) && !empty($p->rekening_bank_snapshot['nomor_rekening']))
                                    <button type="button" class="btn-secondary" style="padding: 3px 6px; font-size: 11px;" title="Salin Nomor Rekening" onclick="navigator.clipboard.writeText('{{ $p->rekening_bank_snapshot['nomor_rekening'] }}'); this.innerText='Tersalin!'; setTimeout(()=>this.innerText='Salin', 1500)">
                                        Salin
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td>
                            <strong style="color: #059669; font-size: 1.15rem;">
                                Rp{{ number_format((float)$p->jumlah, 0, ',', '.') }}
                            </strong>
                            <br>
                            <small style="color: #64748b;">{{ $p->pesanan->count() }} transaksi pesanan</small>
                        </td>
                        <td>
                            <small style="color: #475569;">{{ $p->catatan ?: '-' }}</small>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <!-- Form Approve -->
                                <form method="post" action="{{ route('admin.disbursement.approve', $p) }}" onsubmit="return confirm('Pastikan Anda sudah mentransfer dana sebesar Rp{{ number_format((float)$p->jumlah, 0, ',', '.') }} ke {{ $p->formatted_rekening_snapshot }}. Lanjutkan tandai sudah ditransfer?')">
                                    @csrf
                                    <button class="button" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; background: #059669; border-color: #059669;">
                                        <i class="bi bi-check-lg"></i> Setujui (Ditransfer)
                                    </button>
                                </form>

                                <!-- Form Reject Button (Open Dialog) -->
                                <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #be123c; border-color: #fecdd3;" onclick="openRejectModal({{ $p->id }}, '{{ addslashes($p->umkm->nama_umkm ?? '') }}', '{{ number_format((float)$p->jumlah, 0, ',', '.') }}')">
                                    <i class="bi bi-x-lg"></i> Tolak
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af; padding: 24px;">
                            <i class="bi bi-inbox" style="font-size: 1.8rem; display: block; margin-bottom: 4px;"></i>
                            Tidak ada permintaan pencairan dana yang sedang menunggu.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- 2. Saldo Siap Dicairkan (Direct Pencairan) -->
<section class="data-panel" style="margin-bottom: 32px;">
    <div class="panel-heading">
        <div>
            <small style="color: #059669; font-weight: 700;">SALDO UNPAID PENJUAL</small>
            <h2 style="font-size: 1.2rem; font-weight: 700; margin-top: 2px;">Daftar Saldo Penjual Siap Dicairkan (Belum Diajukan)</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mitra UMKM</th>
                    <th>Pemilik</th>
                    <th>Pesanan Selesai Belum Dicairkan</th>
                    <th>Pendapatan Bersih Seller</th>
                    <th>Komisi Platform (10%)</th>
                    <th>Aksi Pencairan Langsung</th>
                </tr>
            </thead>
            <tbody>
                @forelse($umkmList as $u)
                    <tr>
                        <td><strong>{{ $u->nama_umkm }}</strong></td>
                        <td>{{ $u->pemilik }}</td>
                        <td>
                            <span style="font-weight: 700; color: #1e293b;">{{ $u->total_pesanan_pending }} pesanan</span>
                        </td>
                        <td>
                            <strong style="color: #059669; font-size: 1.05rem;">Rp{{ number_format($u->saldo_pending, 0, ',', '.') }}</strong>
                        </td>
                        <td>
                            <span style="color: #64748b; font-weight: 600;">Rp{{ number_format($u->komisi_admin_pending, 0, ',', '.') }}</span>
                        </td>
                        <td>
                            @if($u->saldo_pending > 0)
                                <form method="post" action="{{ route('admin.disbursement.store', $u) }}" onsubmit="return confirm('Tandai pencairan langsung sebesar Rp{{ number_format($u->saldo_pending, 0, ',', '.') }} ke {{ $u->nama_umkm }} sudah ditransfer?')">
                                    @csrf
                                    <button class="button" style="padding: 6px 14px; font-size: 12px; border-radius: 8px;">
                                        <i class="bi bi-cash-stack"></i> Cairkan Langsung
                                    </button>
                                </form>
                            @else
                                <small style="color: #9ca3af; font-style: italic;">Tidak ada saldo pending</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af; padding: 16px;">Belum ada data UMKM.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- 3. Riwayat Pencairan -->
<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small style="color: #64748b;">RIWAYAT PENCAIRAN</small>
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-top: 2px;">Riwayat Transfer &amp; Penolakan Pencairan Dana</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Disbursement</th>
                    <th>UMKM</th>
                    <th>Rekening Tujuan</th>
                    <th>Jumlah Dicairkan</th>
                    <th>Status</th>
                    <th>Tanggal Proses</th>
                    <th>Diproses Oleh</th>
                    <th>Catatan / Alasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $r)
                    <tr>
                        <td><code>#DISB-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }}</code></td>
                        <td><strong>{{ $r->umkm->nama_umkm ?? '-' }}</strong></td>
                        <td>
                            <small>{{ $r->formatted_rekening_snapshot }}</small>
                        </td>
                        <td><strong style="color: #059669;">Rp{{ number_format((float)$r->jumlah, 0, ',', '.') }}</strong></td>
                        <td>
                            @if($r->status === 'dibayar')
                                <span class="badge" style="background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                                    Dibayar
                                </span>
                            @elseif($r->status === 'ditolak')
                                <span class="badge" style="background: #ffe4e6; color: #be123c; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                                    Ditolak
                                </span>
                            @else
                                <span class="badge" style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                                    {{ ucfirst($r->status) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            {{ optional($r->dibayar_at ?: $r->ditolak_at)->format('d/m/Y H:i') ?? optional($r->created_at)->format('d/m/Y H:i') }}
                        </td>
                        <td>{{ $r->admin->nama_lengkap ?? '-' }}</td>
                        <td><small style="color: #64748b;">{{ $r->catatan ?: '-' }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #9ca3af; padding: 16px;">Belum ada riwayat pencairan dana.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $riwayat->links() }}</div>
</section>

<!-- Modal Tolak Pencairan -->
<div id="modalTolak" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(2px);">
    <div style="background: #ffffff; border-radius: 16px; max-width: 480px; width: 100%; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #9f1239; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-x-circle-fill"></i> Tolak Pengajuan Pencairan
            </h3>
            <button type="button" onclick="document.getElementById('modalTolak').style.display='none'" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <p style="font-size: 0.9rem; color: #475569; margin-bottom: 16px;" id="textTolakInfo">
            Apakah Anda yakin ingin menolak pengajuan pencairan ini? Pesanan terkait akan dilepaskan kembali ke saldo penjual.
        </p>

        <form method="post" id="formTolakDisbursement" action="">
            @csrf
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
                    Alasan Penolakan (Opsional)
                </label>
                <textarea name="alasan_penolakan" rows="3" class="form-control" placeholder="Contoh: Nomor rekening tidak cocok dengan nama pemilik, silakan perbarui rekening bank Anda." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit;"></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalTolak').style.display='none'" style="padding: 8px 16px; border-radius: 8px;">
                    Batal
                </button>
                <button type="submit" class="button" style="padding: 8px 18px; border-radius: 8px; background: #e11d48; border-color: #e11d48;">
                    <i class="bi bi-x-lg"></i> Konfirmasi Tolak
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(disbId, umkmName, nominal) {
    const modal = document.getElementById('modalTolak');
    const form = document.getElementById('formTolakDisbursement');
    const textInfo = document.getElementById('textTolakInfo');

    form.action = `/admin/disbursement/${disbId}/tolak`;
    textInfo.innerHTML = `Anda akan menolak pengajuan pencairan <b>#DISB-${String(disbId).padStart(5, '0')}</b> sebesar <b>Rp${nominal}</b> untuk <b>${umkmName}</b>. Pesanan terkait akan dilepaskan sehingga penjual dapat mengajukan kembali.`;

    modal.style.display = 'flex';
}
</script>
@endsection
