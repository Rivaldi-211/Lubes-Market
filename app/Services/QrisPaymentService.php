<?php

namespace App\Services;

use App\Exceptions\Payment\QrisPaymentConflictException;
use App\Exceptions\Payment\QrisPaymentProviderException;
use App\Exceptions\Payment\QrisPaymentValidationException;
use App\Exceptions\Xendit\XenditAmbiguousException;
use App\Exceptions\Xendit\XenditConfigurationException;
use App\Exceptions\Xendit\XenditMalformedResponseException;
use App\Exceptions\Xendit\XenditRejectedException;
use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QrisPaymentService
{
    public const CREATING_PROVIDER_CLAIM_STALE_SECONDS = 60;

    public function __construct(private XenditService $xenditService)
    {
    }

    public function initiateQrisPayment(User $user, array $pesananIds): Payment
    {
        $normalizedIds = $this->normalizePesananIds($pesananIds);

        /** @var Payment $payment */
        /** @var bool|string $actionState */
        [$payment, $actionState] = DB::transaction(function () use ($user, $normalizedIds) {
            $pesananList = Pesanan::query()
                ->whereIn('id', $normalizedIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($pesananList->count() !== count($normalizedIds)) {
                throw new QrisPaymentValidationException('Satu atau lebih pesanan tidak ditemukan.');
            }

            foreach ($pesananList as $pesanan) {
                if ($pesanan->pembeli_id !== $user->id) {
                    throw new QrisPaymentValidationException('Satu atau lebih pesanan bukan milik pengguna yang sedang login.');
                }

                if ($pesanan->metode_pembayaran !== 'QRIS') {
                    throw new QrisPaymentValidationException('Metode pembayaran pesanan harus QRIS.');
                }

                if ($pesanan->status !== 'Menunggu') {
                    throw new QrisPaymentValidationException("Pesanan hanya dapat dibayar saat status Menunggu (status saat ini: {$pesanan->status}).");
                }
            }

            $attachedPaymentIds = Payment::query()
                ->whereHas('pesanan', fn($q) => $q->whereIn('pesanan.id', $normalizedIds))
                ->pluck('id')
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            $attachedPayments = empty($attachedPaymentIds)
                ? collect()
                : Payment::query()
                    ->whereIn('id', $attachedPaymentIds)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->with('pesanan')
                    ->get();

            $paidPayment = $attachedPayments->first(fn($p) => $p->status === 'PAID');
            if ($paidPayment) {
                throw new QrisPaymentConflictException(
                    'Pesanan sudah memiliki pembayaran lunas.',
                    QrisPaymentConflictException::REASON_ALREADY_PAID,
                    $paidPayment->id,
                    $paidPayment->reference_id
                );
            }

            $requestedIdsSorted = array_values($normalizedIds);

            foreach ($attachedPayments as $existingPayment) {
                $existingPesananIds = $existingPayment->pesanan->pluck('id')->sort()->values()->toArray();
                $isExactGroup = ($existingPesananIds === $requestedIdsSorted);

                if (in_array($existingPayment->status, ['CREATING', 'PENDING', 'CREATION_UNKNOWN'], true)) {
                    if (!$isExactGroup) {
                        throw new QrisPaymentConflictException(
                            'Terdapat transaksi pembayaran aktif lain yang tumpang tindih dengan kumpulan pesanan ini.',
                            QrisPaymentConflictException::REASON_ACTIVE_PAYMENT_OVERLAP,
                            $existingPayment->id,
                            $existingPayment->reference_id
                        );
                    }

                    if ($existingPayment->status === 'CREATING') {
                        if ($existingPayment->provider_request_started_at === null) {
                            return [$existingPayment, true];
                        }

                        $startedAt = $existingPayment->provider_request_started_at;
                        if ($startedAt->isAfter(now()->subSeconds(self::CREATING_PROVIDER_CLAIM_STALE_SECONDS))) {
                            throw new QrisPaymentConflictException(
                                'Transaksi pembayaran sedang diproses oleh permintaan lain.',
                                QrisPaymentConflictException::REASON_PAYMENT_IN_PROGRESS,
                                $existingPayment->id,
                                $existingPayment->reference_id
                            );
                        }

                        return [$existingPayment, 'STALE_CREATING'];
                    }

                    if ($existingPayment->status === 'CREATION_UNKNOWN') {
                        throw new QrisPaymentConflictException(
                            'Pembayaran sebelumnya sedang dalam konfirmasi jaringan (CREATION_UNKNOWN). Silakan cek beberapa saat lagi.',
                            QrisPaymentConflictException::REASON_PAYMENT_STATE_UNKNOWN,
                            $existingPayment->id,
                            $existingPayment->reference_id
                        );
                    }

                    if ($existingPayment->status === 'PENDING') {
                        if (
                            $existingPayment->xendit_payment_request_id !== null &&
                            $existingPayment->qr_string !== null &&
                            $existingPayment->expires_at !== null &&
                            $existingPayment->expires_at->isFuture()
                        ) {
                            return [$existingPayment, false];
                        }

                        throw new QrisPaymentConflictException(
                            'Pembayaran QRIS sebelumnya telah kadaluarsa (STALE PENDING) dan membutuhkan rekonsiliasi.',
                            QrisPaymentConflictException::REASON_STALE_PENDING,
                            $existingPayment->id,
                            $existingPayment->reference_id
                        );
                    }
                }
            }

            $totalAmount = 0;
            foreach ($pesananList as $pesanan) {
                $parts = explode('.', (string) $pesanan->total_harga);
                $fraction = $parts[1] ?? '00';
                if ($fraction !== '00' && $fraction !== '0' && rtrim($fraction, '0') !== '') {
                    throw new QrisPaymentValidationException("Nominal pesanan #{$pesanan->id} mengandung nilai pecahan desimal.");
                }
                $itemAmount = (int) $parts[0];
                $totalAmount += $itemAmount;
            }

            if ($totalAmount < 1 || $totalAmount > 10_000_000) {
                throw new QrisPaymentValidationException("Total nominal pembayaran QRIS harus antara Rp1 dan Rp10.000.000 (total saat ini: {$totalAmount}).");
            }

            $referenceId = 'PAY-' . Str::ulid();

            $newPayment = Payment::create([
                'user_id' => $user->id,
                'reference_id' => $referenceId,
                'amount' => $totalAmount,
                'payment_method' => 'QRIS',
                'status' => 'CREATING',
                'provider_request_started_at' => null,
            ]);

            $newPayment->pesanan()->attach($normalizedIds);

            return [$newPayment, true];
        });

        if ($actionState === 'STALE_CREATING') {
            $this->updatePaymentStatusInTransactionB($payment->id, 'CREATION_UNKNOWN');
            throw new QrisPaymentConflictException(
                'Pembayaran sebelumnya dalam kondisi tidak pasti (CREATION_UNKNOWN). Silakan cek beberapa saat lagi.',
                QrisPaymentConflictException::REASON_PAYMENT_STATE_UNKNOWN,
                $payment->id,
                $payment->reference_id
            );
        }

        if ($actionState === false) {
            return $payment->fresh(['pesanan', 'user']);
        }

        $claimed = DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if (!$lockedPayment || $lockedPayment->status !== 'CREATING') {
                return false;
            }
            if ($lockedPayment->provider_request_started_at !== null) {
                return false;
            }
            $lockedPayment->update([
                'provider_request_started_at' => now(),
            ]);
            return true;
        });

        if (!$claimed) {
            $fresh = $payment->fresh(['pesanan', 'user']);
            if (in_array($fresh->status, ['PENDING', 'PAID'], true)) {
                return $fresh;
            }
            throw new QrisPaymentConflictException(
                'Transaksi pembayaran sedang diproses oleh permintaan lain.',
                QrisPaymentConflictException::REASON_PAYMENT_IN_PROGRESS,
                $payment->id,
                $payment->reference_id
            );
        }

        try {
            $xenditResult = $this->xenditService->createQrisPayment($payment);
        } catch (XenditRejectedException $e) {
            $this->updatePaymentStatusInTransactionB($payment->id, 'FAILED');
            throw new QrisPaymentProviderException(
                'Permintaan pembayaran QRIS ditolak oleh penyedia pembayaran.',
                QrisPaymentProviderException::REASON_PROVIDER_REJECTED,
                $payment->id,
                $payment->reference_id,
                0,
                $e
            );
        } catch (XenditAmbiguousException $e) {
            $this->updatePaymentStatusInTransactionB($payment->id, 'CREATION_UNKNOWN');
            throw new QrisPaymentProviderException(
                'Koneksi ke penyedia pembayaran tidak pasti atau mengalami kesalahan server.',
                QrisPaymentProviderException::REASON_PROVIDER_OUTCOME_UNKNOWN,
                $payment->id,
                $payment->reference_id,
                0,
                $e
            );
        } catch (XenditMalformedResponseException $e) {
            $this->updatePaymentStatusInTransactionB($payment->id, 'CREATION_UNKNOWN');
            throw new QrisPaymentProviderException(
                'Respon penyedia pembayaran tidak dapat diproses (struktur invalid).',
                QrisPaymentProviderException::REASON_PROVIDER_OUTCOME_UNKNOWN,
                $payment->id,
                $payment->reference_id,
                0,
                $e
            );
        } catch (XenditConfigurationException $e) {
            $this->updatePaymentStatusInTransactionB($payment->id, 'FAILED');
            throw new QrisPaymentProviderException(
                'Konfigurasi penyedia pembayaran QRIS belum lengkap atau tidak valid.',
                QrisPaymentProviderException::REASON_PROVIDER_CONFIGURATION_ERROR,
                $payment->id,
                $payment->reference_id,
                0,
                $e
            );
        }

        DB::transaction(function () use ($payment, $xenditResult) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($lockedPayment->status === 'CREATING') {
                $lockedPayment->update([
                    'status' => 'PENDING',
                    'xendit_payment_request_id' => $xenditResult['payment_request_id'],
                    'qr_string' => $xenditResult['qr_string'],
                    'expires_at' => $xenditResult['expires_at'],
                    'raw_response' => $xenditResult['raw_response'],
                ]);
            } elseif ($lockedPayment->status === 'PAID') {
                $lockedPayment->update([
                    'xendit_payment_request_id' => $lockedPayment->xendit_payment_request_id ?? $xenditResult['payment_request_id'],
                    'qr_string' => $lockedPayment->qr_string ?? $xenditResult['qr_string'],
                    'expires_at' => $lockedPayment->expires_at ?? $xenditResult['expires_at'],
                ]);
            }
        });

        return $payment->fresh(['pesanan', 'user']);
    }

    private function updatePaymentStatusInTransactionB(int $paymentId, string $targetStatus): void
    {
        DB::transaction(function () use ($paymentId, $targetStatus) {
            $lockedPayment = Payment::whereKey($paymentId)->lockForUpdate()->first();
            if ($lockedPayment && $lockedPayment->status === 'CREATING') {
                $lockedPayment->update(['status' => $targetStatus]);
            }
        });
    }

    public function expirePaymentRecord(Payment|int $paymentInput, string $targetStatus = 'EXPIRED'): bool
    {
        $paymentId = $paymentInput instanceof Payment ? $paymentInput->id : (int) $paymentInput;

        return DB::transaction(function () use ($paymentId, $targetStatus) {
            /** @var Payment|null $payment */
            $payment = Payment::whereKey($paymentId)->lockForUpdate()->first();
            if (!$payment) {
                return false;
            }

            if ($payment->status === 'PAID') {
                Log::info("Payment {$payment->reference_id} is already PAID. Skipping expiry.");
                return false;
            }

            if (!in_array($targetStatus, ['EXPIRED', 'FAILED'], true)) {
                $targetStatus = 'EXPIRED';
            }

            if (in_array($payment->status, ['CREATING', 'PENDING', 'CREATION_UNKNOWN'], true)) {
                $payment->status = $targetStatus;
            }

            foreach ($payment->pesanan as $pesanan) {
                if ($pesanan->status === 'Menunggu') {
                    $pesanan->update(['status' => 'Dibatalkan']);
                }
            }

            if (!$payment->isStockRestored()) {
                foreach ($payment->pesanan as $pesanan) {
                    /** @var Produk|null $produk */
                    $produk = Produk::where('id', $pesanan->produk_id)->lockForUpdate()->first();
                    if ($produk) {
                        $produk->increment('stok_jumlah', $pesanan->jumlah);
                    }
                }
                $payment->stock_restored_at = now();
            }

            $payment->save();

            Log::info("Payment {$payment->reference_id} processed as {$targetStatus}. Orders canceled & stock restored idempotently.");

            return true;
        });
    }

    private function normalizePesananIds(array $pesananIds): array
    {
        if (empty($pesananIds)) {
            throw new QrisPaymentValidationException('Daftar pesanan tidak boleh kosong.');
        }

        $cleaned = [];
        foreach ($pesananIds as $id) {
            if (!is_numeric($id)) {
                throw new QrisPaymentValidationException('Format ID pesanan tidak valid.');
            }
            $intId = (int) $id;
            if ($intId < 1) {
                throw new QrisPaymentValidationException('ID pesanan harus bilangan bulat positif.');
            }
            $cleaned[] = $intId;
        }

        $uniqueIds = array_values(array_unique($cleaned));
        if (empty($uniqueIds)) {
            throw new QrisPaymentValidationException('Daftar pesanan tidak boleh kosong.');
        }

        sort($uniqueIds, SORT_NUMERIC);

        return $uniqueIds;
    }
}
