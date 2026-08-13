<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\KelompokKeroyokan;
use App\Services\CartService;
use App\Services\KeroyokanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeroyokanController extends Controller
{
    public function index(KeroyokanService $keroyokanService): View
    {
        $groups = KelompokKeroyokan::query()
            ->with(['kategori', 'produk.umkm'])
            ->where('aktif', true)
            ->get()
            ->filter(function ($group) use ($keroyokanService) {
                $allProducts = $keroyokanService->getAllProducts($group);
                return $allProducts->count() >= 1;
            })
            ->values();

        return view('public.keroyokan.index', compact('groups'));
    }

    public function show(KelompokKeroyokan $kelompokKeroyokan, KeroyokanService $keroyokanService): View
    {
        abort_unless($kelompokKeroyokan->aktif, 404);

        $allProducts = $keroyokanService->getAllProducts($kelompokKeroyokan);
        $eligibleProducts = $keroyokanService->getEligibleProducts($kelompokKeroyokan);
        $totalStock = (int) $eligibleProducts->sum('stok_jumlah');
        $minPrice = $allProducts->min('harga') ?: 0;

        return view('public.keroyokan.show', [
            'kelompok' => $kelompokKeroyokan,
            'allProducts' => $allProducts,
            'eligibleProducts' => $eligibleProducts,
            'totalStock' => $totalStock,
            'minPrice' => $minPrice,
        ]);
    }

    public function simulate(
        Request $request,
        KelompokKeroyokan $kelompokKeroyokan,
        KeroyokanService $keroyokanService
    ): View {
        abort_unless($kelompokKeroyokan->aktif, 404);

        $request->validate([
            'target_jumlah' => ['required', 'integer', 'min:2', 'max:100000'],
        ], [
            'target_jumlah.required' => 'Jumlah kebutuhan wajib diisi.',
            'target_jumlah.min' => 'Jumlah pesanan minimal 2 unit.',
        ]);

        $targetJumlah = (int) $request->input('target_jumlah');
        $result = $keroyokanService->calculateAllocation($kelompokKeroyokan, $targetJumlah);

        $allProducts = $keroyokanService->getAllProducts($kelompokKeroyokan);
        $eligibleProducts = $keroyokanService->getEligibleProducts($kelompokKeroyokan);

        return view('public.keroyokan.show', [
            'kelompok' => $kelompokKeroyokan,
            'allProducts' => $allProducts,
            'eligibleProducts' => $eligibleProducts,
            'totalStock' => (int) $eligibleProducts->sum('stok_jumlah'),
            'minPrice' => $allProducts->min('harga') ?: 0,
            'simulation' => $result,
            'inputQuantity' => $targetJumlah,
        ]);
    }

    public function confirm(
        Request $request,
        KelompokKeroyokan $kelompokKeroyokan,
        KeroyokanService $keroyokanService,
        CartService $cartService
    ): RedirectResponse {
        abort_unless($kelompokKeroyokan->aktif, 404);

        $request->validate([
            'target_jumlah' => ['required', 'integer', 'min:2', 'max:100000'],
        ]);

        $targetJumlah = (int) $request->input('target_jumlah');
        $result = $keroyokanService->calculateAllocation($kelompokKeroyokan, $targetJumlah);

        if ($result['status'] !== 'success') {
            return redirect()->route('keroyokan.show', $kelompokKeroyokan)
                ->with('error', $result['message'] ?? 'Alokasi Keroyokan tidak memenuhi syarat.');
        }

        if ($cartService->raw() !== []) {
            return redirect()->route('keroyokan.show', $kelompokKeroyokan)
                ->with('error', 'Keranjang Anda masih berisi produk. Selesaikan atau kosongkan keranjang sebelum membuat pesanan Keroyokan.');
        }

        $cartService->replaceForKeroyokan($result['allocations'], $kelompokKeroyokan->id, $targetJumlah);

        return redirect()->route('checkout.create')->with('success', 'Rencana Keroyokan berhasil disiapkan. Silakan lengkapi pesanan Anda.');
    }
}
