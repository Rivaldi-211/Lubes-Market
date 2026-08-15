@extends('layouts.dashboard') @section('title','Pengguna') @section('eyebrow','Administrator') @section('page_title','Pengguna')
@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Akses sistem</p>
        <h1>Kelola akun pengguna terdaftar.</h1>
        <p>Gunakan status nonaktif untuk menghentikan akses akun.</p>
    </div>
</section>

<div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
    <a href="{{ route('admin.users.index', array_filter(['q' => request('q')])) }}" class="button button-small {{ !request('role') ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Semua ({{ $totalUsers }})
    </a>
    <a href="{{ route('admin.users.index', array_filter(['role' => 'pembeli', 'q' => request('q')])) }}" class="button button-small {{ request('role') === 'pembeli' ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Pembeli ({{ $totalPembeli }})
    </a>
    <a href="{{ route('admin.users.index', array_filter(['role' => 'penjual', 'q' => request('q')])) }}" class="button button-small {{ request('role') === 'penjual' ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Penjual ({{ $totalPenjual }})
    </a>
    <a href="{{ route('admin.users.index', array_filter(['role' => 'admin', 'q' => request('q')])) }}" class="button button-small {{ request('role') === 'admin' ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Admin ({{ $totalAdmin }})
    </a>
</div>

<section class="data-panel">
    <div class="panel-heading">
        <form class="filter-bar">
            @if(request('role'))
                <input type="hidden" name="role" value="{{ request('role') }}">
            @endif
            <label>Cari
                <input name="q" value="{{ request('q') }}" placeholder="Nama / Username...">
            </label>
            <button class="btn-primary">Terapkan</button>
        </form>
    </div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>UMKM</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td><b>{{ $user->nama_lengkap }}</b><br><small>{{ $user->email }}</small></td>
                        <td>{{ $user->username }}</td>
                        <td>
                            <span style="font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; {{ $user->role==='admin' ? 'background:#fef3c7;color:#92400e;' : ($user->role==='penjual' ? 'background:#dbeafe;color:#1e40af;' : 'background:#f3f4f6;color:#374151;') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>{{ $user->umkm?->nama_umkm ?? '-' }}</td>
                        <td><x-status-badge :status="$user->status"/></td>
                        <td>
                            @if($user->id!==auth()->id())
                                <form method="post" action="{{ route('admin.users.status',$user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $user->status==='aktif'?'nonaktif':'aktif' }}">
                                    <button class="{{ $user->status==='aktif'?'btn-danger':'btn-primary' }}">
                                        {{ $user->status==='aktif'?'Nonaktifkan':'Aktifkan' }}
                                    </button>
                                </form>
                            @else
                                <small style="color:#6b7280; font-weight:700;">Akun Anda</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $users->links() }}</div>
</section>
@endsection
