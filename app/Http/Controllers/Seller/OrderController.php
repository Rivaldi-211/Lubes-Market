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
        $statusPembayaran = $request->input('status_pembayaran');
        DB::transaction(function() use($pesanan,$new,$statusPembayaran,$umkmId){
            $order=Pesanan::whereKey($pesanan->id)->lockForUpdate()->firstOrFail();
            $order->loadMissing('produk');
            abort_unless($order->produk->umkm_id===$umkmId,403);
            if($order->status==='Dibatalkan' && $new!=='Dibatalkan') throw ValidationException::withMessages(['status'=>'Pesanan yang dibatalkan tidak dapat diaktifkan kembali.']);
            if($new==='Dibatalkan' && $order->status!=='Dibatalkan'){
                $p=Produk::whereKey($order->produk_id)->lockForUpdate()->firstOrFail();
                $p->increment('stok_jumlah',$order->jumlah);
                if($p->stok_status==='Habis')$p->update(['stok_status'=>'Ready']);
            }
            $updateData = ['status'=>$new];
            if (in_array($statusPembayaran, ['Belum Dibayar', 'Sudah Dibayar'], true)) {
                $updateData['status_pembayaran'] = $statusPembayaran;
            }
            $order->update($updateData);
        });
        $logger->log("Mengubah status pesanan #{$pesanan->id} menjadi {$new}",$request->user(),$request->ip()); return back()->with('success','Status pesanan & pembayaran diperbarui.');
    }

    public function updatePaymentStatus(Request $request, Pesanan $pesanan, ActivityLogger $logger)
    {
        $this->own($request, $pesanan);
        $request->validate([
            'status_pembayaran' => ['required', 'string', \Illuminate\Validation\Rule::in(['Belum Dibayar', 'Sudah Dibayar'])],
        ]);
        $newPayStatus = $request->input('status_pembayaran');
        $pesanan->update(['status_pembayaran' => $newPayStatus]);
        $logger->log("Mengubah status pembayaran pesanan #{$pesanan->id} menjadi {$newPayStatus}", $request->user(), $request->ip());
        return back()->with('success', "Status pembayaran pesanan #{$pesanan->id} diperbarui menjadi {$newPayStatus}.");
    }

    public function paymentNotifications(Request $request)
    {
        $umkm = $request->user()->umkm;
        if (!$umkm) {
            return response()->json(['success' => true, 'notifications' => []]);
        }

        $notifications = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('metode_pembayaran', 'QRIS')
                        ->whereIn('status', ['Diproses', 'Selesai']);
                })->orWhere(function ($sub) {
                    $sub->whereNotNull('bukti_pembayaran');
                });
            })
            ->with(['produk', 'pembeli'])
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'buyer_name' => $order->pembeli?->nama_lengkap ?? 'Pembeli',
                    'product_name' => $order->produk?->nama_produk ?? 'Produk UMKM',
                    'amount_formatted' => 'Rp' . number_format((float) $order->total_harga, 0, ',', '.'),
                    'payment_method' => $order->metode_pembayaran,
                    'status' => $order->status,
                    'has_proof' => !empty($order->bukti_pembayaran),
                    'updated_at' => optional($order->updated_at)->toISOString() ?? now()->toISOString(),
                    'time_ago' => optional($order->updated_at)->diffForHumans() ?? 'Baru saja',
                    'order_url' => route('seller.orders.index', ['status' => $order->status]),
                ];
            });

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ]);
    }
}
