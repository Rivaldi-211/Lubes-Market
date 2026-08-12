<?php
namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
class ReportController extends Controller
{
    private function query(Request $request){ $q=Pesanan::whereHas('produk',fn($p)=>$p->where('umkm_id',$request->user()->umkm->id))->with(['produk','pembeli']); if($request->filled('tgl_mulai')) $q->whereDate('tanggal_pesan','>=',$request->input('tgl_mulai')); if($request->filled('tgl_selesai')) $q->whereDate('tanggal_pesan','<=',$request->input('tgl_selesai')); return $q->latest('tanggal_pesan'); }
    public function index(Request $request){ $orders=$this->query($request)->paginate(25)->withQueryString(); $total=(float)$this->query($request)->where('status','Selesai')->sum('total_harga'); return view('seller.reports.index',compact('orders','total')); }
    public function csv(Request $request): StreamedResponse { $orders=$this->query($request)->get(); return response()->streamDownload(function() use($orders){ $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,['No Pesanan','Tanggal','Produk','Pembeli','Jumlah','Total','Metode','Status']); foreach($orders as $o) fputcsv($out,[$o->id,optional($o->tanggal_pesan)->format('Y-m-d H:i'),$o->produk->nama_produk,$o->pembeli->nama_lengkap,$o->jumlah,(float)$o->total_harga,$o->metode_pembayaran,$o->status]); fclose($out); },'laporan-penjualan-'.date('Ymd-His').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']); }
}
