<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // 1. Fetch all orders belonging to this buyer with eager loaded relations
        $allOrders = Pesanan::query()
            ->where('pembeli_id', $user->id)
            ->with([
                'produk.umkm.user',
                'ulasan',
                'batchKeroyokan.kelompokKeroyokan.kategori',
            ])
            ->latest('tanggal_pesan')
            ->get();

        // 2. Separate into Keroyokan batches and Regular single orders
        $keroyokanGrouped = $allOrders->whereNotNull('batch_keroyokan_id')->groupBy('batch_keroyokan_id');
        $regularOrders = $allOrders->whereNull('batch_keroyokan_id');

        $transactionItems = collect();

        // Add Keroyokan batches as 1 consolidated transaction card each
        foreach ($keroyokanGrouped as $batchId => $ordersInBatch) {
            $firstOrder = $ordersInBatch->first();
            $batchModel = $firstOrder->batchKeroyokan;

            // Overall status of the batch
            $statuses = $ordersInBatch->pluck('status');
            if ($statuses->every(fn($s) => $s === 'Dibatalkan')) {
                $status = 'Dibatalkan';
            } elseif ($statuses->every(fn($s) => $s === 'Selesai')) {
                $status = 'Selesai';
            } elseif ($statuses->contains('Diproses') || $statuses->contains('Selesai')) {
                $status = 'Diproses';
            } else {
                $status = 'Menunggu';
            }

            $transactionItems->push([
                'type' => 'keroyokan',
                'batch_id' => $batchId,
                'batch' => $batchModel,
                'code' => '#KR-' . str_pad($batchId, 5, '0', STR_PAD_LEFT),
                'tanggal_pesan' => $firstOrder->tanggal_pesan,
                'kelompok_nama' => $batchModel?->kelompokKeroyokan?->nama_kelompok ?? 'Paket Keroyokan Pilihan',
                'kategori_nama' => $batchModel?->kelompokKeroyokan?->kategori?->nama_kategori,
                'status' => $status,
                'total_harga' => (float)$ordersInBatch->sum('total_harga'),
                'metode_pembayaran' => $firstOrder->metode_pembayaran,
                'alamat_pengiriman' => $firstOrder->alamat_pengiriman,
                'rekening_bank_snapshot' => $firstOrder->rekening_bank_snapshot,
                'bukti_pembayaran' => $ordersInBatch->pluck('bukti_pembayaran')->filter()->first(),
                'orders' => $ordersInBatch,
                'first_order' => $firstOrder,
                'box_count' => $batchModel?->target_jumlah ?? $ordersInBatch->sum('jumlah'),
            ]);
        }

        // Add Regular orders as 1 transaction card each
        foreach ($regularOrders as $order) {
            $transactionItems->push([
                'type' => 'regular',
                'order' => $order,
                'code' => '#' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
                'tanggal_pesan' => $order->tanggal_pesan,
                'status' => $order->status,
            ]);
        }

        // Sort by tanggal_pesan descending
        $transactionItems = $transactionItems->sortByDesc(fn($item) => $item['tanggal_pesan'])->values();

        // 3. Accurate Transaction Metrics
        $stats = [
            'total' => $transactionItems->count(),
            'menunggu' => $transactionItems->where('status', 'Menunggu')->count(),
            'diproses' => $transactionItems->where('status', 'Diproses')->count(),
            'selesai' => $transactionItems->where('status', 'Selesai')->count(),
        ];

        // 4. Pagination
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $transactionItems->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedTransactions = new LengthAwarePaginator(
            $currentItems,
            $transactionItems->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return view('buyer.dashboard', [
            'transactions' => $paginatedTransactions,
            'orders' => $paginatedTransactions, // For backwards compatibility
            'stats' => $stats,
        ]);
    }
}
