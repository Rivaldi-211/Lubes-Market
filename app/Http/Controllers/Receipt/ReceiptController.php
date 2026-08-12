<?php
namespace App\Http\Controllers\Receipt;
use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
class ReceiptController extends Controller
{
    public function show(Request $request, Pesanan $pesanan)
    {
        $pesanan->load(['pembeli','produk.umkm']); $user=$request->user();
        $allowed=$user->isAdmin() || ($user->isBuyer() && $pesanan->pembeli_id===$user->id) || ($user->isSeller() && $pesanan->produk->umkm->user_id===$user->id);
        abort_unless($allowed,403);
        return view('receipt.show',['order'=>$pesanan]);
    }
}
