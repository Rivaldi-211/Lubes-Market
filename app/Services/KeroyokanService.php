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

    public function calculateAllocation(KelompokKeroyokan $kelompok, int $targetQuantity): array
    {
        if ($targetQuantity < 2) {
            return [
                'status' => 'invalid_quantity',
                'message' => 'Jumlah pesanan minimal 2 unit.',
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
            return [
                'status' => 'insufficient_stock',
                'available' => $totalStock,
                'shortage' => $targetQuantity - $totalStock,
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
        if ($distinctUmkms < 2) {
            return [
                'status' => 'single_umkm_sufficient',
                'message' => 'Pesanan ini masih dapat dipenuhi oleh satu UMKM. Silakan gunakan pembelian produk biasa.',
            ];
        }

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
