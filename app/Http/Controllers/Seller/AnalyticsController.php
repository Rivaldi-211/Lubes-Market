<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\RekomendasiStrategi;
use App\Models\Ulasan;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $umkm = $request->user()->umkm()->firstOrFail();

        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $dateFormatSql = $driver === 'sqlite' ? "strftime('%Y-%m', tanggal_pesan)" : "DATE_FORMAT(tanggal_pesan, '%Y-%m')";
        $createdAtSql = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $startDate6m = now()->subMonths(5)->startOfMonth();
        $rawTrend = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
            ->where('status', 'Selesai')
            ->where('tanggal_pesan', '>=', $startDate6m)
            ->selectRaw("{$dateFormatSql} as bulan,
                         SUM(total_harga) as omzet,
                         SUM(jumlah) as total_item,
                         COUNT(*) as jumlah_transaksi")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $trendOmzet = collect();
        for ($i = 5; $i >= 0; $i--) {
            $mKey = now()->subMonths($i)->format('Y-m');
            $row = $rawTrend->get($mKey);
            $trendOmzet->push((object)[
                'bulan' => $mKey,
                'omzet' => (float)($row?->omzet ?? 0),
                'total_item' => (int)($row?->total_item ?? 0),
                'jumlah_transaksi' => (int)($row?->jumlah_transaksi ?? 0),
            ]);
        }

        $omzetBulanIni  = (float) ($trendOmzet->last()?->omzet ?? 0);
        $omzetBulanLalu = (float) ($trendOmzet->slice(-2, 1)->first()?->omzet ?? 0);
        $pertumbuhanPct = $omzetBulanLalu > 0
            ? round((($omzetBulanIni - $omzetBulanLalu) / $omzetBulanLalu) * 100, 1)
            : null;

        $startDate3m = now()->subMonths(2)->startOfMonth();
        $rawTrendUlasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
            ->where('created_at', '>=', $startDate3m)
            ->selectRaw("{$createdAtSql} as bulan,
                         ROUND(AVG(rating), 2) as avg_rating,
                         COUNT(*) as jumlah_ulasan")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $trendUlasan = collect();
        for ($i = 2; $i >= 0; $i--) {
            $mKey = now()->subMonths($i)->format('Y-m');
            $row = $rawTrendUlasan->get($mKey);
            $trendUlasan->push((object)[
                'bulan' => $mKey,
                'avg_rating' => (float)($row?->avg_rating ?? 0),
                'jumlah_ulasan' => (int)($row?->jumlah_ulasan ?? 0),
            ]);
        }

        $produkTerbaik = Produk::where('umkm_id', $umkm->id)
            ->whereHas('ulasan')
            ->withAvg('ulasan as avg_rating', 'rating')
            ->withCount('ulasan')
            ->orderByDesc('avg_rating')
            ->take(5)
            ->get();

        $produkPerhatian = Produk::where('umkm_id', $umkm->id)
            ->whereHas('ulasan')
            ->withAvg('ulasan as avg_rating', 'rating')
            ->withCount('ulasan')
            ->get()
            ->filter(fn($p) => (float)$p->avg_rating < 3.5)
            ->sortBy('avg_rating')
            ->take(3)
            ->values();

        $rekomendasi = RekomendasiStrategi::where('umkm_id', $umkm->id)
            ->latest()
            ->take(5)
            ->get();

        RekomendasiStrategi::where('umkm_id', $umkm->id)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return view('seller.analytics', compact(
            'umkm',
            'trendOmzet',
            'omzetBulanIni',
            'omzetBulanLalu',
            'pertumbuhanPct',
            'trendUlasan',
            'produkTerbaik',
            'produkPerhatian',
            'rekomendasi'
        ));
    }
}
