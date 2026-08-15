@extends('layouts.dashboard')
@section('title', 'Rekening Bank Transfer')
@section('eyebrow', 'Administrator')
@section('page_title', 'Rekening Bank Platform')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Master Pembayaran</p>
        <h1>Daftar Rekening Bank Tujuan Transfer.</h1>
        <p>Kelola rekening bank platform yang dapat dipilih oleh pembeli saat memilih pembayaran Transfer Bank.</p>
    </div>
    <a class="button" href="{{ route('admin.rekening-bank.create') }}">
        <i class="bi bi-plus-lg"></i> Tambah Rekening
    </a>
</section>

<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small>MASTER DATA</small>
            <h2>Rekening Bank Aktif & Non-Aktif</h2>
        </div>
    </div>

    @if($accounts->count())
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Urutan</th>
                        <th>Bank</th>
                        <th>Nomor Rekening</th>
                        <th>Atas Nama</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                        <tr>
                            <td><strong>#{{ $account->urutan }}</strong></td>
                            <td>
                                <strong style="font-size: 1.05rem; color: #0f172a;">{{ $account->nama_bank }}</strong>
                            </td>
                            <td>
                                <code style="font-size: 1.05rem; font-weight: 700; color: #1e3a8a; background: #eff6ff; padding: 4px 8px; border-radius: 6px;">{{ $account->nomor_rekening }}</code>
                            </td>
                            <td>{{ $account->atas_nama }}</td>
                            <td>
                                @if($account->aktif)
                                    <span style="background: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-check-circle-fill"></i> Aktif
                                    </span>
                                @else
                                    <span style="background: #f1f5f9; color: #64748b; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-slash-circle"></i> Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="action-cluster">
                                    <form method="post" action="{{ route('admin.rekening-bank.status', $account) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn-secondary" title="Ubah status aktif">
                                            <i class="bi bi-power"></i> {{ $account->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <a class="btn-secondary" href="{{ route('admin.rekening-bank.edit', $account) }}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form method="post" action="{{ route('admin.rekening-bank.destroy', $account) }}" onsubmit="return confirm('Hapus rekening bank ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            {{ $accounts->links() }}
        </div>
    @else
        <x-empty-state title="Belum Ada Rekening Bank" text="Tambahkan data rekening bank platform agar pembeli dapat melakukan pembayaran via Transfer." />
    @endif
</section>
@endsection
