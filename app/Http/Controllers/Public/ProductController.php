<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\View\View;
class ProductController extends Controller
{
    public function show(Produk $produk): View
    {
        abort_if($produk->umkm()->where('status','!=','aktif')->exists(),404);
        $produk->load(['umkm','kategori','ulasan'=>fn($q)=>$q->with('pembeli')->latest()])->loadAvg('ulasan','rating')->loadCount('ulasan');
        $related=Produk::where('kategori_id',$produk->kategori_id)->whereKeyNot($produk->id)->whereHas('umkm',fn($q)=>$q->where('status','aktif'))->with('umkm')->take(4)->get();
        return view('public.product',compact('produk','related'));
    }
}
