<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Pesanan; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\StreamedResponse;
class ReportController extends Controller
{
    private function query(Request $r){ $q=Pesanan::with(['produk.umkm','pembeli']); if($r->filled('tgl_mulai'))$q->whereDate('tanggal_pesan','>=',$r->input('tgl_mulai')); if($r->filled('tgl_selesai'))$q->whereDate('tanggal_pesan','<=',$r->input('tgl_selesai')); if($r->filled('status'))$q->where('status',$r->input('status')); return $q->latest('tanggal_pesan'); }
    public function index(Request $r){ $orders=$this->query($r)->paginate(30)->withQueryString(); $total=(float)$this->query($r)->where('status','Selesai')->sum('total_harga'); return view('admin.reports.index',compact('orders','total')); }
    public function csv(Request $r): StreamedResponse { $orders=$this->query($r)->get(); return response()->streamDownload(function()use($orders){$o=fopen('php://output','w');fwrite($o,"\xEF\xBB\xBF");fputcsv($o,['No Pesanan','Tanggal','UMKM','Produk','Pembeli','Jumlah','Total','Metode','Status']);foreach($orders as $x)fputcsv($o,[$x->id,optional($x->tanggal_pesan)->format('Y-m-d H:i'),$x->produk->umkm->nama_umkm,$x->produk->nama_produk,$x->pembeli->nama_lengkap,$x->jumlah,(float)$x->total_harga,$x->metode_pembayaran,$x->status]);fclose($o);},'laporan-platform-'.date('Ymd-His').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']); }
}
