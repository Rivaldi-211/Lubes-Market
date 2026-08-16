<?php

namespace App\Http\Controllers\Receipt;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show(Request $request, Pesanan $pesanan)
    {
        $pesanan->load(['pembeli', 'produk.umkm']);
        $user = $request->user();

        $batchOrders = collect([$pesanan]);
        if ($pesanan->batch_keroyokan_id) {
            $batchOrders = Pesanan::where('batch_keroyokan_id', $pesanan->batch_keroyokan_id)
                ->with(['produk.umkm', 'pembeli'])
                ->orderBy('id', 'asc')
                ->get();
        }

        $allowed = $user->isAdmin()
            || ($user->isBuyer() && $pesanan->pembeli_id === $user->id)
            || ($user->isSeller() && (
                $pesanan->produk?->umkm?->user_id === $user->id
                || $batchOrders->contains(fn($o) => $o->produk?->umkm?->user_id === $user->id)
            ));

        abort_unless($allowed, 403);

        return view('receipt.show', [
            'order' => $pesanan,
            'batchOrders' => $batchOrders,
            'isKeroyokanBatch' => (bool)($pesanan->batch_keroyokan_id && $batchOrders->count() > 1),
        ]);
    }
}

