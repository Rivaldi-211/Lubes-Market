<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $umkm = $request->user()->umkm()->withCount('produk')->firstOrFail();
        $base = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id));
        $orderStats = (clone $base)
            ->selectRaw("
                COUNT(*) as orders,
                COUNT(CASE WHEN status = 'Menunggu' THEN 1 END) as waiting,
                COALESCE(SUM(CASE WHEN status = 'Selesai' THEN total_harga ELSE 0 END), 0) as revenue
            ")
            ->first();
        $stats = [
            'products' => $umkm->produk_count,
            'orders' => (int) ($orderStats->orders ?? 0),
            'waiting' => (int) ($orderStats->waiting ?? 0),
            'revenue' => (float) ($orderStats->revenue ?? 0),
        ];
        $recent = (clone $base)->with(['produk', 'pembeli'])->latest('tanggal_pesan')->limit(8)->get();

        $topProducts = Produk::where('umkm_id', $umkm->id)
            ->withSum(['pesanan as total_terjual' => function ($q) {
                $q->whereIn('status', ['Diproses', 'Selesai']);
            }], 'jumlah')
            ->withSum(['pesanan as total_omzet' => function ($q) {
                $q->whereIn('status', ['Diproses', 'Selesai']);
            }], 'total_harga')
            ->orderByDesc('total_terjual')
            ->take(5)
            ->get();

        return view('seller.dashboard', compact('umkm', 'stats', 'recent', 'topProducts'));
    }
}
