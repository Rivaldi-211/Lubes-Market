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
        if (!$buyer->isBuyer()) abort(403);
        $raw = $this->cart->raw();
        if ($raw === []) throw ValidationException::withMessages(['cart' => 'Keranjang Anda masih kosong.']);

        $keroyokanContext = Session::get('keroyokan_context');
        $validKeroyokanContext = false;
        $kelompokKeroyokan = null;

        if (is_array($keroyokanContext) && isset($keroyokanContext['kelompok_keroyokan_id'], $keroyokanContext['target_jumlah'])) {
            $kelompokKeroyokan = KelompokKeroyokan::find($keroyokanContext['kelompok_keroyokan_id']);
            if ($kelompokKeroyokan && $kelompokKeroyokan->aktif) {
                $targetJumlah = (int) $keroyokanContext['target_jumlah'];
                $sumCartQty = (int) array_sum($raw);
                if ($sumCartQty === $targetJumlah) {
                    $validKeroyokanContext = true;
                }
            }
        }

        $orders = DB::transaction(function () use ($buyer, $payload, $raw, $validKeroyokanContext, $kelompokKeroyokan, $keroyokanContext) {
            $created = collect();
            $totalQrisAmount = 0;
            $isQris = ($payload['metode_pembayaran'] === 'QRIS');

            $batch = null;
            if ($validKeroyokanContext) {
                $batch = BatchKeroyokan::create([
                    'pembeli_id' => $buyer->id,
                    'kelompok_keroyokan_id' => $kelompokKeroyokan->id,
                    'target_jumlah' => (int) $keroyokanContext['target_jumlah'],
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

            // 1. Validasi produk & kelompokkan per UMKM
            $itemsData = [];
            $itemsByUmkm = [];

            foreach ($raw as $productId => $quantity) {
                $product = Produk::query()->whereKey((int)$productId)->lockForUpdate()->first();
                if (!$product || !$product->umkm()->where('status', 'aktif')->exists()) {
                    throw ValidationException::withMessages(['cart' => 'Salah satu produk tidak lagi tersedia.']);
                }
                $quantity = (int)$quantity;
                if ($quantity < 1 || !$product->isAvailable() || $product->stok_jumlah < $quantity) {
                    throw ValidationException::withMessages(['cart' => "Stok {$product->nama_produk} berubah. Tersedia {$product->stok_jumlah} unit."]);
                }

                if ($validKeroyokanContext) {
                    if ((int)$product->kelompok_keroyokan_id !== (int)$kelompokKeroyokan->id) {
                        throw ValidationException::withMessages(['cart' => "Produk {$product->nama_produk} bukan bagian dari kelompok Keroyokan ini."]);
                    }
                }

                $itemsData[$productId] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'umkm_id' => $product->umkm_id,
                ];
                $itemsByUmkm[$product->umkm_id][] = $productId;
            }

            // 2. Ongkir & Packing Calculation
            $zona = isset($payload['zona_pengiriman']) ? \App\Models\ZonaPengiriman::where('nama_zona', $payload['zona_pengiriman'])->first() : null;
            $zonaBiaya = (int) ($zona ? $zona->biaya : 0);

            $opsiPackingNama = $payload['opsi_packing'] ?? 'Standar';
            $packing = \App\Models\OpsiPacking::where('nama', $opsiPackingNama)->first();
            $biayaPackingTotal = (int) ($packing ? $packing->biaya : 0);

            $itemCount = count($itemsData);

            // Alokasi Biaya Packing (dibagi integer remainder ke seluruh item)
            $packingAllocations = [];
            if ($itemCount > 0) {
                $basePacking = intdiv($biayaPackingTotal, $itemCount);
                $remPacking = $biayaPackingTotal % $itemCount;
                $pIdx = 0;
                foreach ($itemsData as $productId => $item) {
                    $packingAllocations[$productId] = $basePacking + ($pIdx < $remPacking ? 1 : 0);
                    $pIdx++;
                }
            }

            // Alokasi Ongkir:
            // - Jika Keroyokan: 1x tarif zona dibagi ke seluruh item rombongan
            // - Jika Regular Order: Tarif zona dihitung PER TOKO (UMKM), didistribusikan ke produk dalam toko tsb
            $ongkosAllocations = [];
            if ($validKeroyokanContext) {
                $baseOngkir = $itemCount > 0 ? intdiv($zonaBiaya, $itemCount) : 0;
                $remOngkir = $itemCount > 0 ? $zonaBiaya % $itemCount : 0;
                $oIdx = 0;
                foreach ($itemsData as $productId => $item) {
                    $ongkosAllocations[$productId] = $baseOngkir + ($oIdx < $remOngkir ? 1 : 0);
                    $oIdx++;
                }
            } else {
                foreach ($itemsByUmkm as $umkmId => $productIds) {
                    $umkmItemCount = count($productIds);
                    $baseOngkirUmkm = intdiv($zonaBiaya, $umkmItemCount);
                    $remOngkirUmkm = $zonaBiaya % $umkmItemCount;
                    foreach ($productIds as $pos => $productId) {
                        $ongkosAllocations[$productId] = $baseOngkirUmkm + ($pos < $remOngkirUmkm ? 1 : 0);
                    }
                }
            }

            // 3. Simpan Pesanan
            foreach ($itemsData as $productId => $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];

                $subtotalProduk = (int) round((float)$product->harga * $quantity);
                $komisiAdmin = (int) round($subtotalProduk * 0.03);
                $pendapatanPenjual = $subtotalProduk - $komisiAdmin;
                $ongkosPerItem = $ongkosAllocations[$productId] ?? 0;
                $packingPerItem = $packingAllocations[$productId] ?? 0;

                $totalHargaItem = $subtotalProduk + $ongkosPerItem + $packingPerItem;

                if ($isQris) {
                    $totalQrisAmount += (int)$totalHargaItem;
                }

                $orderData = [
                    'pembeli_id' => $buyer->id,
                    'batch_keroyokan_id' => $batch?->id,
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

                $created->push(Pesanan::create($orderData));
                $product->decrement('stok_jumlah', $quantity);
            }

            if ($batch) {
                $batch->update([
                    'total_harga' => (float) $created->sum('total_harga')
                ]);
            }

            if ($isQris) {
                if ($totalQrisAmount < 1 || $totalQrisAmount > 10_000_000) {
                    throw ValidationException::withMessages(['cart' => "Total nominal pembayaran QRIS harus antara Rp1 dan Rp10.000.000 (total saat ini: Rp" . number_format($totalQrisAmount, 0, ',', '.') . ")."]);
                }
            }

            return $created;
        }, 3);

        $this->cart->clear();
        Session::forget('keroyokan_context');
        return $orders;
    }
}
