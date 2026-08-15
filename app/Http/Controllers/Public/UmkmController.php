<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Models\Ulasan;
use Illuminate\Http\Request;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('kategori');

        $query = Umkm::where('status', 'aktif')
            ->withCount('produk');

        if ($category) {
            $query->where('kategori_usaha', $category);
        }

        $umkms = $query->get()->map(function ($u) {
            $avg = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $u->id))->avg('rating');
            $u->avg_rating = $avg ? round((float)$avg, 1) : null;
            $u->total_ulasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $u->id))->count();
            return $u;
        });

        $categories = Umkm::where('status', 'aktif')
            ->whereNotNull('kategori_usaha')
            ->distinct()
            ->pluck('kategori_usaha');

        return view('public.umkm.index', compact('umkms', 'categories', 'category'));
    }

    public function show(Umkm $umkm)
    {
        if ($umkm->status !== 'aktif') {
            abort(404);
        }

        $produk = $umkm->produk()
            ->with('kategori')
            ->where('stok_jumlah', '>', 0)
            ->get();

        $ulasanQuery = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id));

        $ulasan = (clone $ulasanQuery)
            ->with(['pembeli', 'produk'])
            ->latest()
            ->take(10)
            ->get();

        $ratingDistribusiRaw = (clone $ulasanQuery)
            ->selectRaw('rating, COUNT(*) as jumlah')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get()
            ->keyBy('rating');

        $totalUlasan = (clone $ulasanQuery)->count();
        $avgRating = $totalUlasan > 0 ? (float) (clone $ulasanQuery)->avg('rating') : 0.0;

        $ratingDistribusi = [];
        for ($b = 5; $b >= 1; $b--) {
            $count = isset($ratingDistribusiRaw[$b]) ? (int) $ratingDistribusiRaw[$b]->jumlah : 0;
            $pct = $totalUlasan > 0 ? round(($count / $totalUlasan) * 100, 1) : 0;
            $ratingDistribusi[$b] = [
                'count' => $count,
                'pct' => $pct,
            ];
        }

        return view('public.umkm.show', compact(
            'umkm',
            'produk',
            'ulasan',
            'ratingDistribusi',
            'avgRating',
            'totalUlasan'
        ));
    }
}
