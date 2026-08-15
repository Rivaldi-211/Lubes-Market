<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZonaPengiriman;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZonaPengirimanController extends Controller
{
    public function index(): View
    {
        $zonas = ZonaPengiriman::orderBy('urutan')->get();
        return view('admin.zona-pengiriman.index', compact('zonas'));
    }

    public function update(Request $request, ZonaPengiriman $zonaPengiriman, ActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'biaya'      => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'aktif'      => ['nullable', 'boolean'],
        ]);

        $zonaPengiriman->update([
            'biaya'      => $validated['biaya'],
            'keterangan' => $validated['keterangan'] ?? $zonaPengiriman->keterangan,
            'aktif'      => $request->has('aktif'),
        ]);

        $logger->log("Memperbarui tarif zona pengiriman {$zonaPengiriman->nama_zona} menjadi Rp" . number_format($validated['biaya'], 0, ',', '.'), auth()->user(), $request->ip());

        return back()->with('success', "Tarif zona {$zonaPengiriman->nama_zona} berhasil diperbarui.");
    }
}
