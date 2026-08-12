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
            ->with(['produk.umkm','ulasan'])->latest('tanggal_pesan')->paginate(10);
        $stats=[
            'total'=>Pesanan::where('pembeli_id',$request->user()->id)->count(),
            'menunggu'=>Pesanan::where('pembeli_id',$request->user()->id)->where('status','Menunggu')->count(),
            'diproses'=>Pesanan::where('pembeli_id',$request->user()->id)->where('status','Diproses')->count(),
            'selesai'=>Pesanan::where('pembeli_id',$request->user()->id)->where('status','Selesai')->count(),
        ];
        return view('buyer.dashboard',compact('orders','stats'));
    }
}
