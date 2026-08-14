<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\RekomendasiStrategi;
use App\Models\Ulasan;
use App\Models\Umkm;
use Illuminate\Http\Request;

class UmkmAnalyticsController extends Controller
{
    public function index()
    {
        $bulanIni  = now()->format('Y-m');
        $bulanLalu = now()->subMonth()->format('Y-m');
        $driver    = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $dateFormatSql = $driver === 'sqlite' ? "strftime('%Y-%m', tanggal_pesan)" : "DATE_FORMAT(tanggal_pesan, '%Y-%m')";

        $umkms = Umkm::where('status', 'aktif')->get()->map(function ($umkm) use ($bulanIni, $bulanLalu, $dateFormatSql) {
            $base = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
                ->where('status', 'Selesai');

            $omzetIni  = (clone $base)->whereRaw("{$dateFormatSql}=?", [$bulanIni])->sum('total_harga');
            $omzetLalu = (clone $base)->whereRaw("{$dateFormatSql}=?", [$bulanLalu])->sum('total_harga');

            $umkm->omzet_ini    = (float) $omzetIni;
            $umkm->omzet_lalu   = (float) $omzetLalu;
            $umkm->growth       = $omzetLalu > 0 ? round((($omzetIni - $omzetLalu) / $omzetLalu) * 100, 1) : null;

            $avgRating = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))->avg('rating');
            $umkm->avg_rating   = round((float)($avgRating ?? 0), 1);
            $umkm->total_ulasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))->count();

            return $umkm;
        })->sortByDesc('omzet_ini')->values();

        return view('admin.umkm.analytics', compact('umkms', 'bulanIni', 'bulanLalu'));
    }

    public function rekomendasiCreate(Umkm $umkm)
    {
        $bulanIni = now()->format('Y-m');
        $driver   = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $dateFormatSql = $driver === 'sqlite' ? "strftime('%Y-%m', tanggal_pesan)" : "DATE_FORMAT(tanggal_pesan, '%Y-%m')";

        $omzetBulanIni = (float) Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
            ->where('status', 'Selesai')
            ->whereRaw("{$dateFormatSql}=?", [$bulanIni])
            ->sum('total_harga');

        $avgRating = (float) (Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))->avg('rating') ?? 0);
        $totalUlasan = Ulasan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))->count();

        $history = RekomendasiStrategi::where('umkm_id', $umkm->id)
            ->latest()
            ->get();

        return view('admin.umkm.rekomendasi_form', compact(
            'umkm',
            'omzetBulanIni',
            'avgRating',
            'totalUlasan',
            'history'
        ));
    }

    public function rekomendasiStore(Request $request, Umkm $umkm)
    {
        $validated = $request->validate([
            'judul'   => 'required|string|max:200',
            'isi'     => 'required|string',
            'tipe'    => 'required|in:promosi,produk,harga,distribusi',
            'periode' => 'required|date_format:Y-m',
        ]);

        RekomendasiStrategi::create($validated + ['umkm_id' => $umkm->id]);

        return redirect()
            ->route('admin.umkm.analytics')
            ->with('success', 'Rekomendasi strategi berhasil dikirim ke ' . $umkm->nama_umkm);
    }
}
