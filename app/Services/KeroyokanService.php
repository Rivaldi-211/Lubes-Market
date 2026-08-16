<?php

namespace App\Services;

use App\Models\KelompokKeroyokan;
use App\Models\Produk;
use Illuminate\Support\Collection;

class KeroyokanService
{
    public function getAllProducts(KelompokKeroyokan $kelompok): Collection
    {
        if (!$kelompok->aktif) {
            return collect();
        }

        return Produk::query()
            ->with(['umkm', 'kategori'])
            ->where('kelompok_keroyokan_id', $kelompok->id)
            ->whereHas('umkm', fn($q) => $q->where('status', 'aktif'))
            ->orderBy('harga', 'asc')
            ->orderBy('stok_jumlah', 'desc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getEligibleProducts(KelompokKeroyokan $kelompok): Collection
    {
        if (!$kelompok->aktif) {
            return collect();
        }

        return Produk::query()
            ->with(['umkm', 'kategori'])
            ->where('kelompok_keroyokan_id', $kelompok->id)
            ->where('stok_jumlah', '>', 0)
            ->where('stok_status', '!=', 'Habis')
            ->whereHas('umkm', fn($q) => $q->where('status', 'aktif'))
            ->orderBy('harga', 'asc')
            ->orderBy('stok_jumlah', 'desc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getAlternativeProducts(KelompokKeroyokan $kelompok, array $excludeProductIds = []): Collection
    {
        return Produk::query()
            ->with(['umkm', 'kategori'])
            ->where('stok_jumlah', '>', 0)
            ->where('stok_status', '!=', 'Habis')
            ->whereHas('umkm', fn($q) => $q->where('status', 'aktif'))
            ->where(function ($query) use ($kelompok) {
                $query->where('kelompok_keroyokan_id', $kelompok->id)
                    ->orWhere('kategori_id', $kelompok->kategori_id);
            })
            ->whereNotIn('id', $excludeProductIds)
            ->orderBy('stok_jumlah', 'desc')
            ->orderBy('harga', 'asc')
            ->take(8)
            ->get();
    }

    public function calculateCustomBoxAllocation(
        KelompokKeroyokan $kelompok,
        int $jumlahBox,
        array $boxItems = [],
        array $substitutions = []
    ): array {
        if ($jumlahBox < 15) {
            return [
                'status' => 'invalid_quantity',
                'message' => 'Jumlah pesanan minimal 15 box.',
            ];
        }

        if (!$kelompok->aktif) {
            return [
                'status' => 'group_inactive',
                'message' => 'Kelompok Keroyokan ini sedang tidak aktif.',
            ];
        }

        $allGroupProducts = $this->getAllProducts($kelompok);
        if ($allGroupProducts->isEmpty()) {
            return [
                'status' => 'insufficient_stock',
                'message' => 'Belum ada produk mitra yang terdaftar pada kelompok Keroyokan ini.',
            ];
        }

        // Default: if no custom box items specified, default to 1 pcs for each product in group
        if (empty($boxItems)) {
            foreach ($allGroupProducts as $p) {
                $boxItems[$p->id] = 1;
            }
        }

        // Clean & validate box items
        $cleanedBoxItems = [];
        $totalPcsInBox = 0;
        foreach ($boxItems as $productId => $pcs) {
            $pcsInt = max(0, (int) $pcs);
            if ($pcsInt > 0) {
                $cleanedBoxItems[(int) $productId] = $pcsInt;
                $totalPcsInBox += $pcsInt;
            }
        }

        if ($totalPcsInBox < 1) {
            return [
                'status' => 'invalid_box_content',
                'message' => 'Pilih dan tentukan minimal 1 item produk untuk isi di dalam box.',
            ];
        }

        $allocations = [];
        $shortages = [];
        $boxPrice = 0.0;
        $totalUnits = 0;
        $usedProductIds = array_keys($cleanedBoxItems);

        foreach ($cleanedBoxItems as $productId => $pcsPerBox) {
            $product = Produk::with(['umkm', 'kategori'])->find($productId);
            if (!$product) {
                continue;
            }

            $boxPrice += ((float) $product->harga * $pcsPerBox);
            $requiredQty = $jumlahBox * $pcsPerBox;
            $totalUnits += $requiredQty;

            $availableStock = ($product->stok_status !== 'Habis' && $product->umkm?->status === 'aktif')
                ? max(0, (int) $product->stok_jumlah)
                : 0;

            if ($availableStock >= $requiredQty) {
                // Direct full allocation
                $lineTotal = (float) $product->harga * $requiredQty;
                $allocations[$product->id] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'quantity' => $requiredQty,
                    'pcs_per_box' => $pcsPerBox,
                    'unit_price' => (float) $product->harga,
                    'line_total' => $lineTotal,
                    'is_substitution' => false,
                ];
            } else {
                // Partial direct allocation
                $directAllocated = $availableStock;
                $shortageQty = $requiredQty - $directAllocated;

                if ($directAllocated > 0) {
                    $lineTotal = (float) $product->harga * $directAllocated;
                    $allocations[$product->id] = [
                        'product' => $product,
                        'product_id' => $product->id,
                        'quantity' => $directAllocated,
                        'pcs_per_box' => $pcsPerBox,
                        'unit_price' => (float) $product->harga,
                        'line_total' => $lineTotal,
                        'is_substitution' => false,
                    ];
                }

                // Check for user-chosen substitution
                $substituteId = $substitutions[$productId] ?? null;
                $substituteProduct = $substituteId ? Produk::with(['umkm', 'kategori'])->find($substituteId) : null;
                $substituteCovered = 0;

                if ($substituteProduct && $substituteProduct->isAvailable()) {
                    $subAvailable = max(0, (int) $substituteProduct->stok_jumlah);
                    // Deduct if already partially allocated elsewhere
                    $alreadyAllocated = isset($allocations[$substituteProduct->id]) ? $allocations[$substituteProduct->id]['quantity'] : 0;
                    $subAvailable = max(0, $subAvailable - $alreadyAllocated);

                    $substituteCovered = min($subAvailable, $shortageQty);
                    if ($substituteCovered > 0) {
                        $subLineTotal = (float) $substituteProduct->harga * $substituteCovered;
                        if (isset($allocations[$substituteProduct->id])) {
                            $allocations[$substituteProduct->id]['quantity'] += $substituteCovered;
                            $allocations[$substituteProduct->id]['line_total'] += $subLineTotal;
                        } else {
                            $allocations[$substituteProduct->id] = [
                                'product' => $substituteProduct,
                                'product_id' => $substituteProduct->id,
                                'quantity' => $substituteCovered,
                                'pcs_per_box' => round($substituteCovered / $jumlahBox, 2),
                                'unit_price' => (float) $substituteProduct->harga,
                                'line_total' => $subLineTotal,
                                'is_substitution' => true,
                                'substitute_for' => $product->nama_produk,
                            ];
                        }
                        $usedProductIds[] = $substituteProduct->id;
                    }
                }

                $remainingShortage = $shortageQty - $substituteCovered;
                if ($remainingShortage > 0) {
                    $candidates = $this->getAlternativeProducts($kelompok, $usedProductIds);
                    $shortages[] = [
                        'product' => $product,
                        'product_id' => $product->id,
                        'required' => $requiredQty,
                        'available' => $availableStock,
                        'shortage' => $remainingShortage,
                        'alternatives' => $candidates,
                    ];
                }
            }
        }

        $grandTotal = (float) collect($allocations)->sum('line_total');
        $distinctUmkms = collect($allocations)->pluck('product.umkm_id')->unique()->count();

        if (!empty($shortages)) {
            return [
                'status' => 'has_shortage',
                'kelompok' => $kelompok,
                'jumlah_box' => $jumlahBox,
                'total_pcs_in_box' => $totalPcsInBox,
                'box_price' => $boxPrice,
                'box_items' => $cleanedBoxItems,
                'allocations' => array_values($allocations),
                'shortages' => $shortages,
                'grand_total' => $grandTotal,
                'target_quantity' => $totalUnits,
                'distinct_umkms_count' => $distinctUmkms,
                'message' => 'Beberapa produk pilihan memiliki stok yang tidak mencukupi untuk ' . $jumlahBox . ' box. Silakan pilih produk alternatif pengganti yang disarankan.',
            ];
        }

        return [
            'status' => 'success',
            'kelompok' => $kelompok,
            'jumlah_box' => $jumlahBox,
            'total_pcs_in_box' => $totalPcsInBox,
            'box_price' => $boxPrice,
            'box_items' => $cleanedBoxItems,
            'allocations' => array_values($allocations),
            'shortages' => [],
            'grand_total' => $grandTotal,
            'target_quantity' => $totalUnits,
            'distinct_umkms_count' => $distinctUmkms,
        ];
    }

    public function calculateAllocation(KelompokKeroyokan $kelompok, int $targetQuantity): array
    {
        if ($targetQuantity < 15) {
            return [
                'status' => 'invalid_quantity',
                'message' => 'Jumlah pesanan minimal 15 unit.',
            ];
        }

        if (!$kelompok->aktif) {
            return [
                'status' => 'group_inactive',
                'message' => 'Kelompok Keroyokan ini sedang tidak aktif.',
            ];
        }

        $eligibleProducts = $this->getEligibleProducts($kelompok);
        if ($eligibleProducts->isEmpty()) {
            return [
                'status' => 'insufficient_stock',
                'available' => 0,
                'shortage' => $targetQuantity,
                'message' => 'Stok Keroyokan saat ini sedang habis.',
            ];
        }

        $totalStock = (int) $eligibleProducts->sum('stok_jumlah');
        $maxSingleStock = (int) $eligibleProducts->max('stok_jumlah');

        if ($maxSingleStock >= $targetQuantity) {
            return [
                'status' => 'single_umkm_sufficient',
                'max_single_stock' => $maxSingleStock,
                'target' => $targetQuantity,
                'message' => 'Pesanan ini masih dapat dipenuhi oleh satu UMKM. Silakan gunakan pembelian produk biasa.',
            ];
        }

        if ($totalStock < $targetQuantity) {
            $candidates = $this->getAlternativeProducts($kelompok, $eligibleProducts->pluck('id')->toArray());
            return [
                'status' => 'insufficient_stock',
                'available' => $totalStock,
                'shortage' => $targetQuantity - $totalStock,
                'alternatives' => $candidates,
                'message' => "Keroyokan belum dapat memenuhi permintaan. Tersedia {$totalStock} dari {$targetQuantity} unit.",
            ];
        }

        $remaining = $targetQuantity;
        $allocations = [];
        $grandTotal = 0.0;

        foreach ($eligibleProducts as $product) {
            $allocated = min($product->stok_jumlah, $remaining);
            if ($allocated > 0) {
                $lineTotal = (float) $product->harga * $allocated;
                $allocations[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'quantity' => $allocated,
                    'pcs_per_box' => 1,
                    'unit_price' => (float) $product->harga,
                    'line_total' => $lineTotal,
                ];
                $grandTotal += $lineTotal;
                $remaining -= $allocated;
            }

            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            return [
                'status' => 'insufficient_stock',
                'available' => $totalStock - $remaining,
                'shortage' => $remaining,
                'message' => 'Stok gabungan tidak mencukupi.',
            ];
        }

        $distinctUmkms = collect($allocations)->pluck('product.umkm_id')->unique()->count();

        return [
            'status' => 'success',
            'kelompok' => $kelompok,
            'target_quantity' => $targetQuantity,
            'allocations' => $allocations,
            'grand_total' => $grandTotal,
            'distinct_umkms_count' => $distinctUmkms,
        ];
    }
}
