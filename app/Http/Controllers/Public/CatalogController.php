<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\View\View;
class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $query=Produk::query()->whereHas('umkm',fn($q)=>$q->where('status','aktif'))
            ->with(['umkm','kategori'])->withAvg('ulasan','rating')->withCount('ulasan');
        if ($request->filled('q')) {
            $keyword=trim((string)$request->input('q'));
            $query->where(function($q) use ($keyword) {
                $q->where('nama_produk','like','%'.$keyword.'%')->orWhere('deskripsi','like','%'.$keyword.'%')
                  ->orWhereHas('umkm',fn($u)=>$u->where('nama_umkm','like','%'.$keyword.'%'));
            });
        }
        if ($request->filled('kategori')) $query->where('kategori_id',(int)$request->input('kategori'));
        match($request->input('sort','terbaru')) {
            'harga_asc' => $query->orderBy('harga'),
            'harga_desc' => $query->orderByDesc('harga'),
            'rating' => $query->orderByDesc('ulasan_avg_rating'),
            default => $query->latest(),
        };
        return view('public.catalogue',[
            'products'=>$query->paginate(12)->withQueryString(),
            'categories'=>Kategori::orderBy('nama_kategori')->get(),
        ]);
    }
}
