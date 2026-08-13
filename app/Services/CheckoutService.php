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

                if ($isQris) {
                    $priceStr = (string) $product->harga;
                    $parts = explode('.', $priceStr);
                    $fraction = $parts[1] ?? '00';
                    if ($fraction !== '00' && $fraction !== '0' && rtrim($fraction, '0') !== '') {
                        throw ValidationException::withMessages(['cart' => "Harga {$product->nama_produk} mengandung nilai pecahan desimal yang tidak valid untuk QRIS."]);
                    }
                    $itemPrice = (int) $parts[0];
                    $totalQrisAmount += ($itemPrice * $quantity);
                }

                $orderData = [
                    'pembeli_id' => $buyer->id,
                    'batch_keroyokan_id' => $batch?->id,
                    'produk_id' => $product->id,
                    'jumlah' => $quantity,
                    'total_harga' => (float)$product->harga * $quantity,
                    'metode_pembayaran' => $payload['metode_pembayaran'],
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
