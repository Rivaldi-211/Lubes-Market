<?php

namespace App\Services;

use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function raw(): array
    {
        return Session::get('cart', []);
    }

    public function keroyokanContext(): ?array
    {
        return Session::get('keroyokan_context');
    }

    public function isKeroyokan(): bool
    {
        $ctx = $this->keroyokanContext();
        return !empty($ctx) && !empty($ctx['kelompok_keroyokan_id']) && !empty($ctx['allocations']);
    }

    public function count(): int
    {
        $count = $this->isKeroyokan() ? 1 : 0;
        $count += count($this->raw());
        return $count;
    }

    public function keroyokanItems(): Collection
    {
        if (!$this->isKeroyokan()) {
            return collect();
        }
        $allocations = $this->keroyokanContext()['allocations'] ?? [];
        if (empty($allocations)) {
            return collect();
        }
        $productIds = array_column($allocations, 'product_id');
        $products = Produk::with(['umkm', 'kategori'])->whereIn('id', $productIds)->get()->keyBy('id');

        return collect($allocations)->map(function ($alloc) use ($products) {
            $product = $products->get((int)$alloc['product_id']);
            if (!$product) return null;
            $qty = (int)$alloc['quantity'];
            return [
                'product' => $product,
                'quantity' => $qty,
                'pcs_per_box' => $alloc['pcs_per_box'] ?? 1,
                'line_total' => (float)$product->harga * $qty,
                'is_keroyokan' => true,
            ];
        })->filter()->values();
    }

    public function regularItems(): Collection
    {
        $cart = $this->raw();
        if ($cart === []) return collect();
        $products = Produk::with(['umkm', 'kategori'])->whereIn('id', array_keys($cart))->get()->keyBy('id');

        return collect($cart)->map(function ($quantity, $id) use ($products) {
            $product = $products->get((int)$id);
            if (!$product) return null;
            $qty = (int)$quantity;
            return [
                'product' => $product,
                'quantity' => $qty,
                'line_total' => (float)$product->harga * $qty,
                'is_keroyokan' => false,
            ];
        })->filter()->values();
    }

    public function items(): Collection
    {
        return $this->keroyokanItems()->concat($this->regularItems());
    }

    public function add(Produk $produk, int $quantity): void
    {
        if (!$produk->isAvailable()) {
            throw ValidationException::withMessages(['jumlah' => 'Produk sedang tidak tersedia.']);
        }
        $cart = $this->raw();
        $current = (int)($cart[$produk->id] ?? 0);
        $target = $current + $quantity;

        // Count any quantity currently allocated in Keroyokan package
        $keroyokanQty = 0;
        if ($this->isKeroyokan()) {
            foreach ($this->keroyokanContext()['allocations'] as $alloc) {
                if ((int)$alloc['product_id'] === (int)$produk->id) {
                    $keroyokanQty += (int)$alloc['quantity'];
                }
            }
        }

        if ($quantity < 1 || ($target + $keroyokanQty) > $produk->stok_jumlah) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah melebihi stok yang tersedia.']);
        }

        $cart[$produk->id] = $target;
        Session::put('cart', $cart);
    }

    public function update(array $quantities): void
    {
        $cart = $this->raw();
        foreach ($quantities as $id => $quantity) {
            $product = Produk::find((int)$id);
            if (!$product) {
                unset($cart[$id]);
                continue;
            }
            $quantity = (int)$quantity;
            if ($quantity <= 0) {
                unset($cart[$id]);
                continue;
            }

            $keroyokanQty = 0;
            if ($this->isKeroyokan()) {
                foreach ($this->keroyokanContext()['allocations'] as $alloc) {
                    if ((int)$alloc['product_id'] === (int)$product->id) {
                        $keroyokanQty += (int)$alloc['quantity'];
                    }
                }
            }

            if (!$product->isAvailable() || ($quantity + $keroyokanQty) > $product->stok_jumlah) {
                throw ValidationException::withMessages(["jumlah_cart.$id" => "Jumlah {$product->nama_produk} melebihi stok."]);
            }
            $cart[$product->id] = $quantity;
        }
        Session::put('cart', $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        Session::put('cart', $cart);
    }

    public function removeKeroyokan(): void
    {
        Session::forget('keroyokan_context');
    }

    public function clear(): void
    {
        Session::put('cart', []);
        Session::forget('keroyokan_context');
    }

    public function replaceForKeroyokan(array $allocations, int $kelompokId, int $targetJumlah, array $meta = []): void
    {
        $cart = $this->raw();
        $allocationsMeta = [];

        foreach ($allocations as $alloc) {
            $productId = (int) ($alloc['product_id'] ?? $alloc['product']->id);
            $qty = (int) $alloc['quantity'];

            $product = Produk::find($productId);
            $regularQty = (int)($cart[$productId] ?? 0);

            if (!$product || !$product->isAvailable() || ($product->stok_jumlah < ($qty + $regularQty))) {
                throw ValidationException::withMessages([
                    'cart' => 'Salah satu stok produk dalam Keroyokan berubah. Silakan hitung ulang alokasi.'
                ]);
            }

            $allocationsMeta[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'pcs_per_box' => $alloc['pcs_per_box'] ?? 1,
                'unit_price' => (float) $product->harga,
            ];
        }

        Session::put('keroyokan_context', array_merge([
            'kelompok_keroyokan_id' => $kelompokId,
            'target_jumlah' => $targetJumlah,
            'allocations' => $allocationsMeta,
            'product_ids' => array_column($allocationsMeta, 'product_id'),
        ], $meta));
    }

    public function subtotal(): float
    {
        return (float)$this->items()->sum('line_total');
    }

    public function keroyokanSubtotal(): float
    {
        return (float)$this->keroyokanItems()->sum('line_total');
    }

    public function regularSubtotal(): float
    {
        return (float)$this->regularItems()->sum('line_total');
    }
}

