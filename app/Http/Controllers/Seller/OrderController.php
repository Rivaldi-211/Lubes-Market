<?php
namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\OrderStatusRequest;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OrderController extends Controller
{
    private function own(Request $request, Pesanan $pesanan): void { $pesanan->loadMissing('produk'); abort_unless($pesanan->produk->umkm_id===$request->user()->umkm?->id,403); }
    public function index(Request $request){ $query=Pesanan::whereHas('produk',fn($q)=>$q->where('umkm_id',$request->user()->umkm->id))->with(['produk','pembeli']); if($request->filled('status')) $query->where('status',$request->input('status')); $orders=$query->latest('tanggal_pesan')->paginate(15)->withQueryString(); return view('seller.orders.index',compact('orders')); }
    public function update(OrderStatusRequest $request, Pesanan $pesanan, ActivityLogger $logger){
        $this->own($request,$pesanan); $new=$request->validated('status'); $umkmId=$request->user()->umkm->id;
        DB::transaction(function() use($pesanan,$new,$umkmId){ $order=Pesanan::whereKey($pesanan->id)->lockForUpdate()->firstOrFail(); $order->loadMissing('produk'); abort_unless($order->produk->umkm_id===$umkmId,403); if($order->status==='Dibatalkan' && $new!=='Dibatalkan') throw ValidationException::withMessages(['status'=>'Pesanan yang dibatalkan tidak dapat diaktifkan kembali.']); if($new==='Dibatalkan' && $order->status!=='Dibatalkan'){ $p=Produk::whereKey($order->produk_id)->lockForUpdate()->firstOrFail(); $p->increment('stok_jumlah',$order->jumlah); if($p->stok_status==='Habis')$p->update(['stok_status'=>'Ready']); } $order->update(['status'=>$new]); });
        $logger->log("Mengubah status pesanan #{$pesanan->id} menjadi {$new}",$request->user(),$request->ip()); return back()->with('success','Status pesanan diperbarui.');
    }
}
