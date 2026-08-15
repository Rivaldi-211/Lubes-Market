@extends('layouts.dashboard')

@section('title', 'Rekening Bank Pembayaran UMKM')
@section('eyebrow', 'Mitra UMKM')
@section('page_title', 'Rekening Bank Pembayaran')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Rekening Pembayaran Mandiri</p>
        <h1>Atur Rekening Bank Pembayaran UMKM Anda</h1>
        <p>Pembeli akan melihat dan mentransfer pembayaran ke rekening bank yang Anda cantumkan di bawah ini.</p>
    </div>
    <a class="button" href="{{ route('seller.rekening-bank.create') }}">
        <i class="bi bi-plus-lg"></i> Tambah Rekening
    </a>
</section>

<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small>REKENING AKTIF</small>
            <h2>Daftar Rekening Bank ({{ $accounts->total() }})</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Urutan</th>
                    <th>Nama Bank</th>
                    <th>Nomor Rekening</th>
                    <th>Atas Nama (Pemilik)</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                    <tr>
                        <td><b>#{{ $acc->urutan }}</b></td>
                        <td>
                            <strong style="color: #1e3a8a; font-size: 1rem;"><i class="bi bi-bank me-1"></i> {{ $acc->nama_bank }}</strong>
                        </td>
                        <td>
                            <code style="font-size: 1rem; font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 4px 10px; border-radius: 6px;">{{ $acc->nomor_rekening }}</code>
                        </td>
                        <td><b>{{ $acc->atas_nama }}</b></td>
                        <td>
                            <form method="post" action="{{ route('seller.rekening-bank.status', $acc) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-secondary" style="padding: 4px 10px; font-size: 12px; border-radius: 20px; color: {{ $acc->aktif ? '#15803d' : '#b91c1c' }}; background: {{ $acc->aktif ? '#f0fdf4' : '#fef2f2' }}; border-color: {{ $acc->aktif ? '#bbf7d0' : '#fecaca' }};">
                                    <i class="bi {{ $acc->aktif ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                    {{ $acc->aktif ? 'Aktif (Ditampilkan)' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="action-cluster">
                                <a class="btn-secondary" href="{{ route('seller.rekening-bank.edit', $acc) }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form method="post" action="{{ route('seller.rekening-bank.destroy', $acc) }}" onsubmit="return confirm('Hapus rekening bank ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state title="Belum Ada Rekening Bank" text="Tambahkan minimal 1 rekening bank agar pembeli dapat melakukan pembayaran via Transfer bank ke toko UMKM Anda." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $accounts->links() }}
    </div>
</section>
@endsection
