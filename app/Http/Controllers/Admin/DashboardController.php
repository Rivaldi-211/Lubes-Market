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
        $orderStats = Pesanan::selectRaw("
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status = 'Selesai' THEN total_harga ELSE 0 END), 0) as revenue
        ")->first();

        $stats = [
            'umkm' => Umkm::count(),
            'products' => Produk::count(),
            'users' => User::count(),
            'orders' => (int) ($orderStats->orders ?? 0),
            'revenue' => (float) ($orderStats->revenue ?? 0)
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
