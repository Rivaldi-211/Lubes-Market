<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Umkm;
use Illuminate\View\View;
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $base=Produk::query()->whereHas('umkm',fn($q)=>$q->where('status','aktif'));
        return view('public.home',[
            'totalProducts'=>(clone $base)->count(),
            'totalUmkm'=>Umkm::where('status','aktif')->count(),
            'featured'=>(clone $base)->with(['umkm','kategori'])->withAvg('ulasan','rating')->withCount('ulasan')->latest()->take(8)->get(),
            'categories'=>Kategori::withCount('produk')->orderBy('nama_kategori')->get(),
            'producers'=>Umkm::where('status','aktif')->withCount('produk')->orderByDesc('produk_count')->take(4)->get(),
        ]);
    }
}
