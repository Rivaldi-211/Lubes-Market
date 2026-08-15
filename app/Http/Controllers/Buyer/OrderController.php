<?php
namespace App\Http\Controllers\Buyer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\PaymentProofRequest;
use App\Http\Requests\Buyer\ReviewRequest;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Ulasan;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class OrderController extends Controller
{
    public function __construct(private ActivityLogger $logger) {}
    private function own(Request $request, Pesanan $pesanan): void { if($pesanan->pembeli_id!==$request->user()->id) abort(403); }
    public function cancel(Request $request, Pesanan $pesanan)
    {
        $this->own($request,$pesanan);
        DB::transaction(function() use($request,$pesanan){
            $order=Pesanan::whereKey($pesanan->id)->lockForUpdate()->firstOrFail();
            if($order->pembeli_id!==$request->user()->id) abort(403);
            if($order->status!=='Menunggu') throw ValidationException::withMessages(['pesanan'=>'Pesanan hanya dapat dibatalkan saat status Menunggu.']);
            $product=Produk::whereKey($order->produk_id)->lockForUpdate()->firstOrFail();
            $product->increment('stok_jumlah',$order->jumlah);
            if($product->stok_status==='Habis') $product->update(['stok_status'=>'Ready']);
            $order->update(['status'=>'Dibatalkan']);
        });
        $this->logger->log("Membatalkan pesanan #{$pesanan->id}",$request->user(),$request->ip());
        return redirect()->route('buyer.dashboard')->with('success','Pesanan dibatalkan dan stok dikembalikan.');
    }
    public function confirmReceived(Request $request, Pesanan $pesanan)
    {
        $this->own($request, $pesanan);
        if ($pesanan->status === 'Selesai') {
            return redirect()->route('buyer.dashboard')->with('info', 'Pesanan ini sudah berstatus selesai.');
        }
        if ($pesanan->status !== 'Diproses') {
            throw ValidationException::withMessages(['pesanan' => 'Konfirmasi penerimaan barang hanya dapat dilakukan untuk pesanan yang sedang diproses/dikirim.']);
        }
        DB::transaction(function () use ($pesanan) {
            $order = Pesanan::whereKey($pesanan->id)->lockForUpdate()->firstOrFail();
            $updateData = ['status' => 'Selesai'];
            if ($order->metode_pembayaran === 'COD' && $order->status_pembayaran !== 'Sudah Dibayar') {
                $updateData['status_pembayaran'] = 'Sudah Dibayar';
            }
            $order->update($updateData);
        });
        $this->logger->log("Mengonfirmasi penerimaan pesanan #{$pesanan->id}", $request->user(), $request->ip());
        return redirect()->route('buyer.dashboard')->with('success', 'Pesanan #' . str_pad($pesanan->id, 5, '0', STR_PAD_LEFT) . ' berhasil dikonfirmasi selesai diterima. Silakan berikan ulasan produk Anda!');
    }
    public function uploadProof(PaymentProofRequest $request, Pesanan $pesanan)
    {
        $this->own($request,$pesanan);
        if(!in_array($pesanan->metode_pembayaran,['Transfer','QRIS'],true)) throw ValidationException::withMessages(['bukti_pembayaran'=>'Bukti pembayaran hanya untuk Transfer atau QRIS.']);
        if($pesanan->status==='Dibatalkan') throw ValidationException::withMessages(['bukti_pembayaran'=>'Pesanan yang dibatalkan tidak dapat menerima bukti pembayaran.']);
        if($pesanan->bukti_pembayaran) Storage::disk('public')->delete($pesanan->bukti_pembayaran);
        $path=$request->file('bukti_pembayaran')->store('payment-proofs','public');
        $pesanan->update(['bukti_pembayaran'=>$path]);
        $this->logger->log("Mengunggah bukti pembayaran pesanan #{$pesanan->id}",$request->user(),$request->ip());
        return redirect()->route('buyer.dashboard')->with('success','Bukti pembayaran berhasil diunggah.');
    }
    public function review(ReviewRequest $request, Pesanan $pesanan)
    {
        $this->own($request,$pesanan);
        if($pesanan->status!=='Selesai') throw ValidationException::withMessages(['rating'=>'Ulasan hanya dapat diberikan pada pesanan yang selesai.']);
        if($pesanan->ulasan()->exists()) throw ValidationException::withMessages(['rating'=>'Pesanan ini sudah pernah diulas.']);
        Ulasan::create(['pesanan_id'=>$pesanan->id,'produk_id'=>$pesanan->produk_id,'pembeli_id'=>$request->user()->id]+$request->validated());
        $this->logger->log("Memberi ulasan pada pesanan #{$pesanan->id}",$request->user(),$request->ip());
        return redirect()->route('buyer.dashboard')->with('success','Terima kasih, ulasan Anda sudah disimpan.');
    }
}
