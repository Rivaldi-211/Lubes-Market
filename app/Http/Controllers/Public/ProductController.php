<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\View\View;
class ProductController extends Controller
{
    public function show(Produk $produk): View
    {
        abort_if($produk->umkm()->where('status','!=','aktif')->exists(), 404);
        $produk->load(['umkm','kategori','ulasan'=>fn($q)=>$q->with('pembeli')->latest()])
               ->loadAvg('ulasan','rating')
               ->loadCount('ulasan');

        $totalUlasan = $produk->ulasan_count ?? $produk->ulasan->count();
        $avgRating = (float) ($produk->ulasan_avg_rating ?? 0);
        $ratingDistribusiRaw = $produk->ulasan->groupBy('rating');

        $ratingDistribusi = [];
        for ($b = 5; $b >= 1; $b--) {
            $count = isset($ratingDistribusiRaw[$b]) ? $ratingDistribusiRaw[$b]->count() : 0;
            $pct = $totalUlasan > 0 ? round(($count / $totalUlasan) * 100, 1) : 0;
            $ratingDistribusi[$b] = [
                'count' => $count,
                'pct' => $pct,
            ];
        }

        $related = Produk::where('kategori_id', $produk->kategori_id)
            ->whereKeyNot($produk->id)
            ->whereHas('umkm', fn($q)=>$q->where('status','aktif'))
            ->with('umkm')
            ->take(4)
            ->get();

        return view('public.product', compact('produk', 'related', 'ratingDistribusi', 'totalUlasan', 'avgRating'));
    }
}
