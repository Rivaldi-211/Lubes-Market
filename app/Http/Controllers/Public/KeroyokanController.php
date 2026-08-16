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

        $defaultJumlahBox = 15;
        $defaultBoxItems = [];
        foreach ($allProducts as $p) {
            $defaultBoxItems[$p->id] = 1;
        }

        return view('public.keroyokan.show', [
            'kelompok' => $kelompokKeroyokan,
            'allProducts' => $allProducts,
            'eligibleProducts' => $eligibleProducts,
            'totalStock' => $totalStock,
            'minPrice' => $minPrice,
            'jumlahBox' => $defaultJumlahBox,
            'boxItems' => $defaultBoxItems,
            'substitutions' => [],
        ]);
    }

    public function simulate(
        Request $request,
        KelompokKeroyokan $kelompokKeroyokan,
        KeroyokanService $keroyokanService
    ): View {
        abort_unless($kelompokKeroyokan->aktif, 404);

        $request->validate([
            'jumlah_box' => ['nullable', 'integer', 'min:15', 'max:100000'],
            'target_jumlah' => ['nullable', 'integer', 'min:15', 'max:100000'],
            'box_items' => ['nullable', 'array'],
            'box_items.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'substitutions' => ['nullable', 'array'],
            'substitutions.*' => ['nullable', 'integer'],
        ], [
            'jumlah_box.min' => 'Jumlah pesanan minimal 15 box.',
            'target_jumlah.min' => 'Jumlah pesanan minimal 15 box.',
        ]);

        $hasCustom = $request->filled('jumlah_box') || ($request->filled('box_items') && !empty($request->input('box_items')));

        if ($hasCustom) {
            $jumlahBox = (int) ($request->input('jumlah_box') ?: 15);
            $boxItems = (array) $request->input('box_items', []);
            $substitutions = (array) $request->input('substitutions', []);

            $result = $keroyokanService->calculateCustomBoxAllocation(
                $kelompokKeroyokan,
                $jumlahBox,
                $boxItems,
                $substitutions
            );
        } else {
            $targetJumlah = (int) $request->input('target_jumlah', 15);
            $result = $keroyokanService->calculateAllocation($kelompokKeroyokan, $targetJumlah);
            $jumlahBox = $targetJumlah;
            $boxItems = [];
            $substitutions = [];
        }

        $allProducts = $keroyokanService->getAllProducts($kelompokKeroyokan);
        $eligibleProducts = $keroyokanService->getEligibleProducts($kelompokKeroyokan);

        return view('public.keroyokan.show', [
            'kelompok' => $kelompokKeroyokan,
            'allProducts' => $allProducts,
            'eligibleProducts' => $eligibleProducts,
            'totalStock' => (int) $eligibleProducts->sum('stok_jumlah'),
            'minPrice' => $allProducts->min('harga') ?: 0,
            'simulation' => $result,
            'jumlahBox' => $jumlahBox,
            'boxItems' => $boxItems ?: ($result['box_items'] ?? []),
            'substitutions' => $substitutions,
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
            'jumlah_box' => ['nullable', 'integer', 'min:15', 'max:100000'],
            'target_jumlah' => ['nullable', 'integer', 'min:15', 'max:100000'],
            'box_items' => ['nullable', 'array'],
            'box_items.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'substitutions' => ['nullable', 'array'],
            'substitutions.*' => ['nullable', 'integer'],
        ], [
            'jumlah_box.min' => 'Jumlah pesanan minimal 15 box.',
            'target_jumlah.min' => 'Jumlah pesanan minimal 15 box.',
        ]);

        $hasCustom = $request->filled('jumlah_box') || ($request->filled('box_items') && !empty($request->input('box_items')));

        if ($hasCustom) {
            $jumlahBox = (int) ($request->input('jumlah_box') ?: 15);
            $boxItems = (array) $request->input('box_items', []);
            $substitutions = (array) $request->input('substitutions', []);

            $result = $keroyokanService->calculateCustomBoxAllocation(
                $kelompokKeroyokan,
                $jumlahBox,
                $boxItems,
                $substitutions
            );
        } else {
            $targetJumlah = (int) $request->input('target_jumlah', 15);
            $result = $keroyokanService->calculateAllocation($kelompokKeroyokan, $targetJumlah);
            $jumlahBox = $targetJumlah;
        }

        if ($result['status'] !== 'success') {
            return redirect()->route('keroyokan.show', $kelompokKeroyokan)
                ->with('error', $result['message'] ?? 'Alokasi Keroyokan tidak memenuhi syarat.');
        }

        $cartService->replaceForKeroyokan(
            $result['allocations'],
            $kelompokKeroyokan->id,
            $result['target_quantity'],
            [
                'jumlah_box' => $jumlahBox,
                'box_price' => $result['box_price'] ?? 0,
                'total_pcs_in_box' => $result['total_pcs_in_box'] ?? 0,
            ]
        );

        return redirect()->route('checkout.create')->with('success', 'Rencana Paket Keroyokan (' . $jumlahBox . ' Box) berhasil disiapkan. Silakan lengkapi pesanan Anda.');
    }
}
