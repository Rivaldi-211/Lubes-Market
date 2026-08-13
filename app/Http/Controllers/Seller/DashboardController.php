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
        $stats = [
            'products' => $umkm->produk_count,
            'orders' => (clone $base)->count(),
            'waiting' => (clone $base)->where('status', 'Menunggu')->count(),
            'revenue' => (float)(clone $base)->where('status', 'Selesai')->sum('total_harga')
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
