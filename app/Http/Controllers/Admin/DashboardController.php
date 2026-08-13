<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'umkm' => Umkm::count(),
            'products' => Produk::count(),
            'users' => User::count(),
            'orders' => Pesanan::count(),
            'revenue' => (float)Pesanan::where('status', 'Selesai')->sum('total_harga')
        ];

        $recent = Pesanan::with(['produk.umkm', 'pembeli'])->latest('tanggal_pesan')->limit(10)->get();

        $topProducts = Produk::with(['umkm', 'kategori'])
            ->withSum(['pesanan as total_terjual' => function ($q) {
                $q->whereIn('status', ['Diproses', 'Selesai']);
            }], 'jumlah')
            ->withSum(['pesanan as total_omzet' => function ($q) {
                $q->whereIn('status', ['Diproses', 'Selesai']);
            }], 'total_harga')
            ->orderByDesc('total_terjual')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent', 'topProducts'));
    }
}
