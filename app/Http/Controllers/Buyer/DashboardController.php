<?php
namespace App\Http\Controllers\Buyer;
use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $orders=Pesanan::query()->where('pembeli_id',$request->user()->id)
            ->with(['produk.umkm.user','ulasan'])->latest('tanggal_pesan')->paginate(10);
        $statsRaw=Pesanan::query()
            ->where('pembeli_id',$request->user()->id)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'Menunggu' THEN 1 END) as menunggu,
                COUNT(CASE WHEN status = 'Diproses' THEN 1 END) as diproses,
                COUNT(CASE WHEN status = 'Selesai' THEN 1 END) as selesai
            ")
            ->first();
        $stats=[
            'total'=>(int)($statsRaw->total??0),
            'menunggu'=>(int)($statsRaw->menunggu??0),
            'diproses'=>(int)($statsRaw->diproses??0),
            'selesai'=>(int)($statsRaw->selesai??0),
        ];
        return view('buyer.dashboard',compact('orders','stats'));
    }
}
