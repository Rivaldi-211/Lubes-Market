<?php

namespace App\Services;

use App\Models\BatchKeroyokan;
use App\Models\KelompokKeroyokan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private CartService $cart) {}

    public function checkout(User $buyer, array $payload): Collection
    {
        if ($this->cart->items()->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Keranjang Anda masih kosong.']);
        }

        $keroyokanContext = Session::get('keroyokan_context');
        $validKeroyokanContext = false;
        $kelompokKeroyokan = null;

        if (is_array($keroyokanContext) && isset($keroyokanContext['kelompok_keroyokan_id'])) {
            $kelompokKeroyokan = KelompokKeroyokan::find($keroyokanContext['kelompok_keroyokan_id']);
            if ($kelompokKeroyokan && $kelompokKeroyokan->aktif) {
                $validKeroyokanContext = true;
            }
        }

        $selection = Session::get('checkout_selection');

        $includeKeroyokan = $validKeroyokanContext;
        if ($selection !== null) {
            $includeKeroyokan = $includeKeroyokan && !empty($selection['keroyokan']);
        }
        $validKeroyokanContext = $includeKeroyokan;

        $keroyokanItems = $validKeroyokanContext ? $this->cart->keroyokanItems() : collect();
        $regularItems = $this->cart->regularItems();

        if ($selection !== null && isset($selection['products'])) {
            $allowedProductIds = $selection['products'];
            $regularItems = $regularItems->filter(function ($item) use ($allowedProductIds) {
                return in_array((int)$item['product']->id, $allowedProductIds, true);
            })->values();
        }

        $allCheckoutItems = $keroyokanItems->concat($regularItems);

        if ($allCheckoutItems->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Tidak ada produk yang dipilih untuk checkout.']);
        }

        $orders = DB::transaction(function () use ($buyer, $payload, $validKeroyokanContext, $kelompokKeroyokan, $keroyokanContext, $allCheckoutItems) {
            $created = collect();
            $totalQrisAmount = 0;
            $isQris = ($payload['metode_pembayaran'] === 'QRIS');

            $batch = null;
            if ($validKeroyokanContext) {
                $batch = BatchKeroyokan::create([
                    'pembeli_id' => $buyer->id,
                    'kelompok_keroyokan_id' => $kelompokKeroyokan->id,
                    'target_jumlah' => (int) ($keroyokanContext['jumlah_box'] ?? $keroyokanContext['target_jumlah'] ?? $allCheckoutItems->where('is_keroyokan', true)->sum('quantity')),
                    'total_harga' => 0,
                ]);
            }

            $rekeningBankId = null;
            $rekeningSnapshot = null;
            if ($payload['metode_pembayaran'] === 'Transfer' && !empty($payload['rekening_bank_id'])) {
                $bank = \App\Models\RekeningBank::whereNull('umkm_id')->find($payload['rekening_bank_id']);
                if ($bank) {
                    $rekeningBankId = $bank->id;
                    $rekeningSnapshot = "{$bank->nama_bank} - {$bank->nomor_rekening} a.n. {$bank->atas_nama}";
                }
            }

            // 1. Validasi produk & stok akumulasi
            $totalNeededPerProduct = [];
            foreach ($allCheckoutItems as $item) {
                $pId = (int)$item['product']->id;
                $totalNeededPerProduct[$pId] = ($totalNeededPerProduct[$pId] ?? 0) + (int)$item['quantity'];
            }

            $itemsData = [];
            $itemsByUmkm = [];

            foreach ($allCheckoutItems as $index => $item) {
                $productId = (int)$item['product']->id;
                $quantity = (int)$item['quantity'];
                $isKeroyokan = !empty($item['is_keroyokan']);

                $product = Produk::query()->whereKey($productId)->lockForUpdate()->first();
                if (!$product || !$product->umkm()->where('status', 'aktif')->exists()) {
                    throw ValidationException::withMessages(['cart' => 'Salah satu produk tidak lagi tersedia.']);
                }
                if ($quantity < 1 || !$product->isAvailable() || $product->stok_jumlah < $totalNeededPerProduct[$productId]) {
                    throw ValidationException::withMessages(['cart' => "Stok {$product->nama_produk} berubah. Tersedia {$product->stok_jumlah} unit."]);
                }

                $itemKey = $index . '_' . $productId;
                $itemsData[$itemKey] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'umkm_id' => $product->umkm_id,
                    'is_keroyokan' => $isKeroyokan,
                ];

                if (!$isKeroyokan) {
                    $itemsByUmkm[$product->umkm_id][] = $itemKey;
                }
            }

            // 2. Ongkir & Packing Calculation
            $zona = isset($payload['zona_pengiriman']) ? \App\Models\ZonaPengiriman::where('nama_zona', $payload['zona_pengiriman'])->first() : null;
            $zonaBiaya = (int) ($zona ? $zona->biaya : 0);

            $opsiPackingNama = $payload['opsi_packing'] ?? 'Standar';
            $packing = \App\Models\OpsiPacking::where('nama', $opsiPackingNama)->first();
            $biayaPackingTotal = (int) ($packing ? $packing->biaya : 0);

            $itemCount = count($itemsData);

            // Alokasi Biaya Packing
            $packingAllocations = [];
            if ($itemCount > 0) {
                $basePacking = intdiv($biayaPackingTotal, $itemCount);
                $remPacking = $biayaPackingTotal % $itemCount;
                $pIdx = 0;
                foreach (array_keys($itemsData) as $itemKey) {
                    $packingAllocations[$itemKey] = $basePacking + ($pIdx < $remPacking ? 1 : 0);
                    $pIdx++;
                }
            }

            // Alokasi Ongkir:
            // - Bagian Keroyokan: 1x tarif zona dibagi rata ke seluruh item Keroyokan
            // - Bagian Reguler: Tarif zona per toko UMKM reguler
            $ongkosAllocations = [];
            $keroyokanItemKeys = array_keys(array_filter($itemsData, fn($i) => $i['is_keroyokan']));
            $kCount = count($keroyokanItemKeys);

            if ($kCount > 0) {
                $baseKOngkir = intdiv($zonaBiaya, $kCount);
                $remKOngkir = $zonaBiaya % $kCount;
                $kIdx = 0;
                foreach ($keroyokanItemKeys as $itemKey) {
                    $ongkosAllocations[$itemKey] = $baseKOngkir + ($kIdx < $remKOngkir ? 1 : 0);
                    $kIdx++;
                }
            }

            foreach ($itemsByUmkm as $umkmId => $itemKeys) {
                $umkmItemCount = count($itemKeys);
                $baseOngkirUmkm = intdiv($zonaBiaya, $umkmItemCount);
                $remOngkirUmkm = $zonaBiaya % $umkmItemCount;
                foreach ($itemKeys as $pos => $itemKey) {
                    $ongkosAllocations[$itemKey] = $baseOngkirUmkm + ($pos < $remOngkirUmkm ? 1 : 0);
                }
            }

            // 3. QRIS Pre-validation
            if ($isQris) {
                $estimatedQris = 0;
                foreach ($itemsData as $itemKey => $item) {
                    $product = $item['product'];
                    $quantity = $item['quantity'];
                    $parts = explode('.', (string) $product->harga);
                    $fraction = $parts[1] ?? '00';
                    if ($fraction !== '00' && $fraction !== '0' && rtrim($fraction, '0') !== '') {
                        throw ValidationException::withMessages(['cart' => "Nominal harga produk {$product->nama_produk} mengandung nilai pecahan desimal yang tidak valid untuk QRIS."]);
                    }
                    $sub = (float)$product->harga * $quantity;
                    $ong = $ongkosAllocations[$itemKey] ?? 0;
                    $pac = $packingAllocations[$itemKey] ?? 0;
                    $estimatedQris += ($sub + $ong + $pac);
                }
                if ($estimatedQris > 10_000_000) {
                    throw ValidationException::withMessages(['cart' => 'Total pembayaran QRIS tidak boleh melebihi Rp10.000.000.']);
                }
            }

            // 4. Simpan Pesanan
            foreach ($itemsData as $itemKey => $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                $isKeroyokanItem = $item['is_keroyokan'];

                $subtotalProduk = (int) round((float)$product->harga * $quantity);
                $komisiAdmin = (int) round($subtotalProduk * 0.03);
                $pendapatanPenjual = $subtotalProduk - $komisiAdmin;
                $ongkosPerItem = $ongkosAllocations[$itemKey] ?? 0;
                $packingPerItem = $packingAllocations[$itemKey] ?? 0;

                $totalHargaItem = $subtotalProduk + $ongkosPerItem + $packingPerItem;

                if ($isQris) {
                    $totalQrisAmount += (int)$totalHargaItem;
                }

                $orderData = [
                    'pembeli_id' => $buyer->id,
                    'batch_keroyokan_id' => $isKeroyokanItem ? $batch?->id : null,
                    'produk_id' => $product->id,
                    'jumlah' => $quantity,
                    'total_harga' => $totalHargaItem,
                    'ongkos_kirim' => $ongkosPerItem,
                    'biaya_packing' => $packingPerItem,
                    'komisi_admin' => $komisiAdmin,
                    'pendapatan_penjual' => $pendapatanPenjual,
                    'opsi_packing' => $opsiPackingNama,
                    'zona_pengiriman' => $payload['zona_pengiriman'] ?? null,
                    'metode_pembayaran' => $payload['metode_pembayaran'],
                    'rekening_bank_id' => $rekeningBankId,
                    'rekening_bank_snapshot' => $rekeningSnapshot,
                    'alamat_pengiriman' => $payload['alamat_pengiriman'],
                    'no_hp_pembeli' => $payload['no_hp_pembeli'],
                    'status' => 'Menunggu',
                    'catatan' => $payload['catatan'] ?? null,
                    'tanggal_pesan' => now(),
                ];

                $createdOrder = Pesanan::create($orderData);
                $created->push($createdOrder);
                $product->decrement('stok_jumlah', $quantity);
            }

            if ($batch) {
                $batchOrders = $created->where('batch_keroyokan_id', $batch->id);
                $batch->update([
                    'total_harga' => (float) $batchOrders->sum('total_harga')
                ]);
            }

            if ($isQris) {
                if ($totalQrisAmount < 1 || $totalQrisAmount > 10_000_000) {
                    throw ValidationException::withMessages(['cart' => "Total nominal pembayaran QRIS harus antara Rp1 dan Rp10.000.000 (total saat ini: Rp" . number_format($totalQrisAmount, 0, ',', '.') . ")."]);
                }
            }

            return $created;
        }, 3);

        if ($validKeroyokanContext) {
            Session::forget('keroyokan_context');
        }
        foreach ($regularItems as $item) {
            $this->cart->remove((int)$item['product']->id);
        }
        Session::forget('checkout_selection');
        return $orders;
    }
}
