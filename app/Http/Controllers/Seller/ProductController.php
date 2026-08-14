<?php
namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ProductRequest;
use App\Models\Kategori;
use App\Models\Produk;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class ProductController extends Controller
{
    private function own(Request $request, Produk $produk): void { abort_unless($produk->umkm_id===$request->user()->umkm?->id,403); }
    public function index(Request $request){ $products=$request->user()->umkm->produk()->with('kategori')->latest()->paginate(12); return view('seller.products.index',compact('products')); }
    public function create(){ return view('seller.products.form',['product'=>new Produk,'categories'=>Kategori::orderBy('nama_kategori')->get()]); }
    public function store(ProductRequest $request, ActivityLogger $logger){ $data=$request->safe()->except('foto'); $data['umkm_id']=$request->user()->umkm->id; if($request->hasFile('foto')) $data['foto']=$request->file('foto')->store('products','public'); if((int)$data['stok_jumlah']===0) $data['stok_status']='Habis'; if(($data['stok_status'] ?? '') !== 'Pre-Order') $data['estimasi_po_hari'] = null; $product=Produk::create($data); $logger->log("Menambahkan produk {$product->nama_produk}",$request->user(),$request->ip()); return redirect()->route('seller.products.index')->with('success','Produk berhasil ditambahkan.'); }
    public function edit(Request $request, Produk $produk){ $this->own($request,$produk); return view('seller.products.form',['product'=>$produk,'categories'=>Kategori::orderBy('nama_kategori')->get()]); }
    public function update(ProductRequest $request, Produk $produk, ActivityLogger $logger){ $this->own($request,$produk); $data=$request->safe()->except('foto'); if($request->hasFile('foto')){ if($produk->foto) Storage::disk('public')->delete($produk->foto); $data['foto']=$request->file('foto')->store('products','public'); } if((int)$data['stok_jumlah']===0) $data['stok_status']='Habis'; if(($data['stok_status'] ?? '') !== 'Pre-Order') $data['estimasi_po_hari'] = null; $produk->update($data); $logger->log("Memperbarui produk {$produk->nama_produk}",$request->user(),$request->ip()); return redirect()->route('seller.products.index')->with('success','Produk berhasil diperbarui.'); }
    public function destroy(Request $request, Produk $produk, ActivityLogger $logger){ $this->own($request,$produk); if($produk->pesanan()->exists()) throw ValidationException::withMessages(['produk'=>'Produk yang sudah memiliki transaksi tidak dapat dihapus. Ubah stok menjadi Habis.']); if($produk->foto) Storage::disk('public')->delete($produk->foto); $name=$produk->nama_produk; $produk->delete(); $logger->log("Menghapus produk {$name}",$request->user(),$request->ip()); return redirect()->route('seller.products.index')->with('success','Produk berhasil dihapus.'); }
}
