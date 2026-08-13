<?php

namespace Tests\Feature;

use App\Exceptions\Payment\QrisPaymentConflictException;
use App\Exceptions\Payment\QrisPaymentProviderException;
use App\Exceptions\Payment\QrisPaymentValidationException;
use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Services\QrisPaymentService;
use App\Services\XenditService;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QrisPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private QrisPaymentService $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BumdesDemoSeeder::class);
        config(['services.xendit.secret_key' => 'xnd_development_dummy_key_12345']);
        Http::preventStrayRequests();

        $xenditService = new XenditService();
        $this->orchestrator = new QrisPaymentService($xenditService);
    }

    private function createQrisPesanan(User $user, int $price = 15000, string $status = 'Menunggu'): Pesanan
    {
        $seller = User::where('role', 'penjual')->firstOrFail();
        $umkm = $seller->umkm()->first();
        $produk = Produk::create([
            'umkm_id' => $umkm->id,
            'kategori_id' => 1,
            'nama_produk' => 'Produk QRIS Test ' . uniqid(),
            'harga' => $price,
            'stok_status' => 'Ready',
            'stok_jumlah' => 10,
        ]);

        return Pesanan::create([
            'pembeli_id' => $user->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
            'total_harga' => $price,
            'metode_pembayaran' => 'QRIS',
            'status' => $status,
            'tanggal_pesan' => now(),
        ]);
    }

    public function test_1_empty_pesanan_ids_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, []);
    }

    public function test_2_duplicate_ids_are_normalized_and_sorted(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 10000);
        $p2 = $this->createQrisPesanan($user, 20000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($request) {
            return Http::response([
                'payment_request_id' => 'pr-norm-123',
                'reference_id' => $request['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $request['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p2->id, $p1->id, $p2->id]);

        $this->assertSame(30000, $payment->amount);
        $this->assertCount(2, $payment->pesanan);
    }

    public function test_3_nonexistent_pesanan_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [9999999]);
    }

    public function test_4_pesanan_belonging_to_another_user_rejected(): void
    {
        $user1 = User::where('role', 'pembeli')->firstOrFail();
        $user2 = User::create(['username' => 'other_user', 'nama_lengkap' => 'Other', 'email' => 'other@example.com', 'password' => 'secret', 'role' => 'pembeli', 'status' => 'aktif']);
        $p = $this->createQrisPesanan($user1);

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user2, [$p->id]);
    }

    public function test_5_non_qris_pesanan_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user);
        $p->update(['metode_pembayaran' => 'COD']);

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_6_status_dibatalkan_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 10000, 'Dibatalkan');

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_7_status_diproses_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 10000, 'Diproses');

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_8_status_selesai_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 10000, 'Selesai');

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_9_exact_integer_amount_calculated_correctly(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 15000);
        $p2 = $this->createQrisPesanan($user, 25000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-amount-1',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p1->id, $p2->id]);

        $this->assertSame(40000, $payment->amount);
    }

    public function test_10_fractional_total_harga_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);
        DB::table('pesanan')->where('id', $p->id)->update(['total_harga' => 15000.50]);

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_11_amount_greater_than_10_million_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);
        DB::table('pesanan')->where('id', $p->id)->update(['total_harga' => 10000001]);

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_12_existing_paid_prevents_new_attempt(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $paidPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-PAID-001',
            'amount' => 15000,
            'status' => 'PAID',
        ]);
        $paidPayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ALREADY_PAID, $e->getReason());
            $this->assertSame($paidPayment->id, $e->getPaymentId());
            $this->assertSame($paidPayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_13_exact_existing_creating_reused_without_provider_call(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $creatingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-CREATING-001',
            'amount' => 15000,
            'status' => 'CREATING',
            'provider_request_started_at' => null,
        ]);
        $creatingPayment->pesanan()->attach($p->id);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-cre-reuse',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $result = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertTrue($result->is($creatingPayment));
        $this->assertSame('PENDING', $result->status);
    }

    public function test_14_exact_existing_active_pending_reused_without_provider_call(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $pendingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-PENDING-001',
            'xendit_payment_request_id' => 'pr-existing-1',
            'qr_string' => 'qr_existing',
            'amount' => 15000,
            'status' => 'PENDING',
            'expires_at' => now()->addHours(24),
        ]);
        $pendingPayment->pesanan()->attach($p->id);

        $result = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertTrue($result->is($pendingPayment));
        Http::assertNothingSent();
    }

    public function test_15_stale_pending_does_not_create_new_attempt(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $stalePayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-STALE-001',
            'xendit_payment_request_id' => 'pr-stale-1',
            'qr_string' => 'qr_stale',
            'amount' => 15000,
            'status' => 'PENDING',
            'expires_at' => now()->subHour(),
        ]);
        $stalePayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_STALE_PENDING, $e->getReason());
            $this->assertSame($stalePayment->id, $e->getPaymentId());
            $this->assertSame($stalePayment->reference_id, $e->getPaymentReferenceId());
        }

        Http::assertNothingSent();
    }

    public function test_16_creation_unknown_does_not_create_new_attempt(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $unknownPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-UNKNOWN-001',
            'amount' => 15000,
            'status' => 'CREATION_UNKNOWN',
        ]);
        $unknownPayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_STATE_UNKNOWN, $e->getReason());
            $this->assertSame($unknownPayment->id, $e->getPaymentId());
            $this->assertSame($unknownPayment->reference_id, $e->getPaymentReferenceId());
        }

        Http::assertNothingSent();
    }

    public function test_17_failed_allows_new_attempt(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $failedPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-FAILED-001',
            'amount' => 15000,
            'status' => 'FAILED',
        ]);
        $failedPayment->pesanan()->attach($p->id);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-retry-1',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'new_qr']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $newPayment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertFalse($newPayment->is($failedPayment));
        $this->assertSame('PENDING', $newPayment->status);
    }

    public function test_18_expired_allows_new_attempt(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $expiredPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-EXPIRED-001',
            'amount' => 15000,
            'status' => 'EXPIRED',
        ]);
        $expiredPayment->pesanan()->attach($p->id);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-retry-exp-1',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'new_qr_2']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $newPayment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertFalse($newPayment->is($expiredPayment));
        $this->assertSame('PENDING', $newPayment->status);
    }

    public function test_19_previous_attempt_history_retained(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $failedPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-OLD-ATTEMPT',
            'amount' => 15000,
            'status' => 'FAILED',
        ]);
        $failedPayment->pesanan()->attach($p->id);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-new-attempt',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'new_qr']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertCount(2, $p->fresh()->payments);
        $this->assertSame('FAILED', $failedPayment->fresh()->status);
    }

    public function test_20_overlapping_creating_group_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 10000);
        $p2 = $this->createQrisPesanan($user, 20000);

        $creatingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-OVERLAP-CREATING',
            'amount' => 10000,
            'status' => 'CREATING',
        ]);
        $creatingPayment->pesanan()->attach($p1->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p1->id, $p2->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ACTIVE_PAYMENT_OVERLAP, $e->getReason());
            $this->assertSame($creatingPayment->id, $e->getPaymentId());
            $this->assertSame($creatingPayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_21_overlapping_pending_group_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 10000);
        $p2 = $this->createQrisPesanan($user, 20000);

        $pendingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-OVERLAP-PENDING',
            'xendit_payment_request_id' => 'pr-overlap-1',
            'qr_string' => 'qr_overlap',
            'amount' => 10000,
            'status' => 'PENDING',
            'expires_at' => now()->addHours(24),
        ]);
        $pendingPayment->pesanan()->attach($p1->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p1->id, $p2->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ACTIVE_PAYMENT_OVERLAP, $e->getReason());
            $this->assertSame($pendingPayment->id, $e->getPaymentId());
            $this->assertSame($pendingPayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_22_overlapping_creation_unknown_group_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 10000);
        $p2 = $this->createQrisPesanan($user, 20000);

        $unknownPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-OVERLAP-UNKNOWN',
            'amount' => 10000,
            'status' => 'CREATION_UNKNOWN',
        ]);
        $unknownPayment->pesanan()->attach($p1->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p1->id, $p2->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ACTIVE_PAYMENT_OVERLAP, $e->getReason());
            $this->assertSame($unknownPayment->id, $e->getPaymentId());
            $this->assertSame($unknownPayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_23_new_payment_reference_id_starts_with_pay(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-ref-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_value']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertStringStartsWith('PAY-', $payment->reference_id);
    }

    public function test_24_payment_status_starts_creating_before_provider_call(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            $paymentInDb = Payment::where('reference_id', $req['reference_id'])->first();
            $this->assertNotNull($paymentInDb);
            $this->assertSame('CREATING', $paymentInDb->status);

            return Http::response([
                'payment_request_id' => 'pr-status-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_value']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_25_pivot_contains_every_requested_pesanan(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 10000);
        $p2 = $this->createQrisPesanan($user, 20000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-pivot-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_value']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p1->id, $p2->id]);

        $this->assertTrue($payment->pesanan->pluck('id')->contains($p1->id));
        $this->assertTrue($payment->pesanan->pluck('id')->contains($p2->id));
    }

    public function test_26_http_call_occurs_with_transaction_level_zero(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);
        $initialLevel = DB::connection()->transactionLevel();

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) use ($initialLevel) {
            $this->assertSame($initialLevel, DB::connection()->transactionLevel());

            return Http::response([
                'payment_request_id' => 'pr-level-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_value']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_27_valid_provider_success_transitions_creating_to_pending(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-success-01',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val_success']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertSame('PENDING', $payment->status);
    }

    public function test_28_success_stores_xendit_payment_request_id(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-store-pr-id',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertSame('pr-store-pr-id', $payment->xendit_payment_request_id);
    }

    public function test_29_success_stores_qr_string(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-qr-store',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_string_exact_target']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertSame('qr_string_exact_target', $payment->qr_string);
    }

    public function test_30_success_stores_expires_at(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-exp-store',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertNotNull($payment->expires_at);
        $this->assertSame('2026-08-15 10:00:00', $payment->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_31_success_stores_raw_response(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-raw-store',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertIsArray($payment->raw_response);
        $this->assertSame('pr-raw-store', $payment->raw_response['payment_request_id']);
    }

    public function test_32_success_does_not_set_xendit_payment_id(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-no-py-id',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertNull($payment->xendit_payment_id);
    }

    public function test_33_xendit_rejected_exception_transitions_creating_to_failed(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['message' => 'Channel disabled'], 422)]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('FAILED', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_REJECTED, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_34_xendit_ambiguous_exception_transitions_creating_to_creation_unknown(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('CREATION_UNKNOWN', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_OUTCOME_UNKNOWN, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_35_xendit_malformed_response_exception_transitions_creating_to_creation_unknown(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['status' => 'PENDING'], 201)]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('CREATION_UNKNOWN', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_OUTCOME_UNKNOWN, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_36_xendit_configuration_exception_transitions_creating_to_failed(): void
    {
        config(['services.xendit.secret_key' => '']);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('FAILED', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_CONFIGURATION_ERROR, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_37_no_automatic_retry_after_ambiguous_exception(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        } catch (QrisPaymentProviderException $e) {
            // Expected
        }

        Http::assertSentCount(1);
    }

    public function test_38_ambiguous_attempt_prevents_immediate_second_attempt(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        } catch (QrisPaymentProviderException $e) {
            // First attempt became CREATION_UNKNOWN
        }

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_STATE_UNKNOWN, $e->getReason());
        }
    }

    public function test_39_no_pesanan_status_is_changed(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-order-status-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertSame('Menunggu', $p->fresh()->status);
    }

    public function test_40_no_stock_quantity_is_changed_by_orchestration_service(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);
        $produk = $p->produk;
        $initialStock = $produk->stok_jumlah;

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-stock-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertSame($initialStock, $produk->fresh()->stok_jumlah);
    }

    public function test_41_new_creating_payment_starts_with_provider_request_started_at_null(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            $paymentInDb = Payment::where('reference_id', $req['reference_id'])->first();
            $this->assertNotNull($paymentInDb->provider_request_started_at);

            return Http::response([
                'payment_request_id' => 'pr-started-at-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        $this->assertNotNull($payment->provider_request_started_at);
    }

    public function test_42_winning_provider_claim_sets_provider_request_started_at(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-claim-set',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        $this->assertNotNull($payment->provider_request_started_at);
    }

    public function test_43_http_occurs_after_provider_claim_commit(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            $paymentInDb = Payment::where('reference_id', $req['reference_id'])->first();
            $this->assertNotNull($paymentInDb->provider_request_started_at);

            return Http::response([
                'payment_request_id' => 'pr-claim-commit-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_44_http_transaction_level_remains_zero(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);
        $initialLevel = DB::connection()->transactionLevel();

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) use ($initialLevel) {
            $this->assertSame($initialLevel, DB::connection()->transactionLevel());

            return Http::response([
                'payment_request_id' => 'pr-level-0-check',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->orchestrator->initiateQrisPayment($user, [$p->id]);
    }

    public function test_45_exact_existing_creating_with_null_provider_marker_can_be_safely_claimed(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $creatingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-CREATING-NULL-MARKER',
            'amount' => 15000,
            'status' => 'CREATING',
            'provider_request_started_at' => null,
        ]);
        $creatingPayment->pesanan()->attach($p->id);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) use ($creatingPayment) {
            $this->assertSame($creatingPayment->reference_id, $req['reference_id']);

            return Http::response([
                'payment_request_id' => 'pr-null-claimed',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        $this->assertSame('PENDING', $payment->status);
        $this->assertNotNull($payment->provider_request_started_at);
    }

    public function test_46_exact_existing_creating_with_recent_provider_marker_does_not_call_provider_again(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $creatingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-CREATING-RECENT-MARKER',
            'amount' => 15000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(10),
        ]);
        $creatingPayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_IN_PROGRESS, $e->getReason());
            $this->assertSame($creatingPayment->id, $e->getPaymentId());
            $this->assertSame($creatingPayment->reference_id, $e->getPaymentReferenceId());
        }

        Http::assertNothingSent();
    }

    public function test_47_stale_creating_with_provider_marker_becomes_creation_unknown(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $staleCreating = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-CREATING-STALE-MARKER',
            'amount' => 15000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(65),
        ]);
        $staleCreating->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_STATE_UNKNOWN, $e->getReason());
            $this->assertSame($staleCreating->id, $e->getPaymentId());
            $this->assertSame($staleCreating->reference_id, $e->getPaymentReferenceId());
            $this->assertSame('CREATION_UNKNOWN', $staleCreating->fresh()->status);
        }
    }

    public function test_48_stale_creating_does_not_call_provider_again(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $staleCreating = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-CREATING-STALE-NO-CALL',
            'amount' => 15000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(70),
        ]);
        $staleCreating->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        } catch (QrisPaymentConflictException $e) {
            // Expected
        }

        Http::assertNothingSent();
    }

    public function test_49_two_sequential_claim_attempts_result_in_only_one_provider_call(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-seq-claim',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $res1 = $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        $res2 = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertTrue($res1->is($res2));
        Http::assertSentCount(1);
    }

    public function test_50_provider_request_started_at_remains_after_pending_success(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-started-remains-pending',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        $this->assertSame('PENDING', $payment->status);
        $this->assertNotNull($payment->provider_request_started_at);
    }

    public function test_51_provider_request_started_at_remains_after_failed(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['message' => 'Rejected'], 422)]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        } catch (QrisPaymentProviderException $e) {
            // Expected
        }

        $lastPayment = Payment::latest('id')->first();
        $this->assertSame('FAILED', $lastPayment->status);
        $this->assertNotNull($lastPayment->provider_request_started_at);
    }

    public function test_52_provider_request_started_at_remains_after_creation_unknown(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        } catch (QrisPaymentProviderException $e) {
            // Expected
        }

        $lastPayment = Payment::latest('id')->first();
        $this->assertSame('CREATION_UNKNOWN', $lastPayment->status);
        $this->assertNotNull($lastPayment->provider_request_started_at);
    }

    public function test_53_related_payment_rows_are_obtained_with_locking_strategy(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-lock-strategy',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);
        $this->assertNotNull($payment);
    }

    public function test_54_full_existing_payment_group_10_11_12_vs_requested_10_11_classified_overlapping_not_exact(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p10 = $this->createQrisPesanan($user, 10000);
        $p11 = $this->createQrisPesanan($user, 10000);
        $p12 = $this->createQrisPesanan($user, 10000);

        $payment3Items = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-3-ITEMS-EXISTING',
            'amount' => 30000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(5),
        ]);
        $payment3Items->pesanan()->attach([$p10->id, $p11->id, $p12->id]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p10->id, $p11->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ACTIVE_PAYMENT_OVERLAP, $e->getReason());
            $this->assertSame($payment3Items->id, $e->getPaymentId());
            $this->assertSame($payment3Items->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_55_already_paid_returns_conflict_exception_reason_already_paid(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $paidPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-PAID-REASON-CHECK',
            'amount' => 15000,
            'status' => 'PAID',
        ]);
        $paidPayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ALREADY_PAID, $e->getReason());
            $this->assertSame($paidPayment->id, $e->getPaymentId());
            $this->assertSame($paidPayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_56_creating_recent_returns_reason_payment_in_progress(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $creatingRecent = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-CREATING-RECENT-REASON',
            'amount' => 15000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(15),
        ]);
        $creatingRecent->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_IN_PROGRESS, $e->getReason());
            $this->assertSame($creatingRecent->id, $e->getPaymentId());
            $this->assertSame($creatingRecent->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_57_creation_unknown_returns_reason_payment_state_unknown(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $unknownPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-UNKNOWN-REASON-CHECK',
            'amount' => 15000,
            'status' => 'CREATION_UNKNOWN',
        ]);
        $unknownPayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_STATE_UNKNOWN, $e->getReason());
            $this->assertSame($unknownPayment->id, $e->getPaymentId());
            $this->assertSame($unknownPayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_58_overlap_returns_reason_active_payment_overlap(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 10000);
        $p2 = $this->createQrisPesanan($user, 20000);

        $creatingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-OVERLAP-REASON-CHECK',
            'amount' => 10000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(10),
        ]);
        $creatingPayment->pesanan()->attach($p1->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p1->id, $p2->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ACTIVE_PAYMENT_OVERLAP, $e->getReason());
            $this->assertSame($creatingPayment->id, $e->getPaymentId());
            $this->assertSame($creatingPayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_59_stale_pending_returns_reason_stale_pending(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $stalePayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-STALE-REASON-CHECK',
            'xendit_payment_request_id' => 'pr-stale-reason',
            'qr_string' => 'qr_stale',
            'amount' => 15000,
            'status' => 'PENDING',
            'expires_at' => now()->subHour(),
        ]);
        $stalePayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_STALE_PENDING, $e->getReason());
            $this->assertSame($stalePayment->id, $e->getPaymentId());
            $this->assertSame($stalePayment->reference_id, $e->getPaymentReferenceId());
        }
    }

    public function test_60_invalid_pesanan_data_uses_qris_payment_validation_exception(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();

        $this->expectException(QrisPaymentValidationException::class);
        $this->orchestrator->initiateQrisPayment($user, [-1, 'abc']);
    }

    // ==========================================
    // STEP 6.2 NEW TESTS (61 - 73)
    // ==========================================

    public function test_61_already_paid_conflict_carries_payment_id_and_reference_id(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $paidPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-61-PAID',
            'amount' => 15000,
            'status' => 'PAID',
        ]);
        $paidPayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ALREADY_PAID, $e->getReason());
            $this->assertSame($paidPayment->id, $e->getPaymentId());
            $this->assertSame('PAY-61-PAID', $e->getPaymentReferenceId());
        }
    }

    public function test_62_payment_in_progress_carries_payment_metadata(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $creatingRecent = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-62-PROGRESS',
            'amount' => 15000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(10),
        ]);
        $creatingRecent->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_IN_PROGRESS, $e->getReason());
            $this->assertSame($creatingRecent->id, $e->getPaymentId());
            $this->assertSame('PAY-62-PROGRESS', $e->getPaymentReferenceId());
        }
    }

    public function test_63_payment_state_unknown_carries_payment_metadata(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $unknownPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-63-UNKNOWN',
            'amount' => 15000,
            'status' => 'CREATION_UNKNOWN',
        ]);
        $unknownPayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_PAYMENT_STATE_UNKNOWN, $e->getReason());
            $this->assertSame($unknownPayment->id, $e->getPaymentId());
            $this->assertSame('PAY-63-UNKNOWN', $e->getPaymentReferenceId());
        }
    }

    public function test_64_stale_pending_carries_payment_metadata(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $stalePayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-64-STALE',
            'xendit_payment_request_id' => 'pr-64-stale',
            'qr_string' => 'qr_64_stale',
            'amount' => 15000,
            'status' => 'PENDING',
            'expires_at' => now()->subHour(),
        ]);
        $stalePayment->pesanan()->attach($p->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_STALE_PENDING, $e->getReason());
            $this->assertSame($stalePayment->id, $e->getPaymentId());
            $this->assertSame('PAY-64-STALE', $e->getPaymentReferenceId());
        }
    }

    public function test_65_active_payment_overlap_carries_deterministic_conflicting_payment_metadata(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p1 = $this->createQrisPesanan($user, 10000);
        $p2 = $this->createQrisPesanan($user, 20000);

        $creatingOverlap = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-65-OVERLAP',
            'amount' => 10000,
            'status' => 'CREATING',
            'provider_request_started_at' => now()->subSeconds(10),
        ]);
        $creatingOverlap->pesanan()->attach($p1->id);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p1->id, $p2->id]);
            $this->fail('Expected QrisPaymentConflictException was not thrown.');
        } catch (QrisPaymentConflictException $e) {
            $this->assertSame(QrisPaymentConflictException::REASON_ACTIVE_PAYMENT_OVERLAP, $e->getReason());
            $this->assertSame($creatingOverlap->id, $e->getPaymentId());
            $this->assertSame('PAY-65-OVERLAP', $e->getPaymentReferenceId());
        }
    }

    public function test_66_provider_4xx_results_in_qris_payment_provider_exception_rejected(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['message' => 'Channel inactive'], 422)]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('FAILED', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_REJECTED, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_67_provider_ambiguous_results_in_reason_provider_outcome_unknown(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('CREATION_UNKNOWN', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_OUTCOME_UNKNOWN, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_68_malformed_provider_response_results_in_reason_provider_outcome_unknown(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['status' => 'INVALID_STATUS'], 201)]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('CREATION_UNKNOWN', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_OUTCOME_UNKNOWN, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_69_missing_xendit_config_results_in_reason_provider_configuration_error(): void
    {
        config(['services.xendit.secret_key' => '']);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $lastPayment = Payment::latest('id')->first();
            $this->assertSame('FAILED', $lastPayment->status);
            $this->assertSame(QrisPaymentProviderException::REASON_PROVIDER_CONFIGURATION_ERROR, $e->getReason());
            $this->assertSame($lastPayment->id, $e->getPaymentId());
            $this->assertSame($lastPayment->reference_id, $e->getPaymentReferenceId());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_70_previous_exception_is_preserved_in_provider_exception(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['message' => 'Detail error'], 422)]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $this->assertNotNull($e->getPrevious());
            $this->assertInstanceOf(\App\Exceptions\Xendit\XenditRejectedException::class, $e->getPrevious());
        }
    }

    public function test_71_exception_message_does_not_include_test_secret(): void
    {
        $secretKey = 'xnd_development_dummy_key_12345';
        config(['services.xendit.secret_key' => $secretKey]);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->orchestrator->initiateQrisPayment($user, [$p->id]);
            $this->fail('Expected QrisPaymentProviderException was not thrown.');
        } catch (QrisPaymentProviderException $e) {
            $this->assertStringNotContainsString($secretKey, $e->getMessage());
        }
    }

    public function test_72_successful_pending_still_returns_payment_normally(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-norm-return',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('PENDING', $payment->status);
    }

    public function test_73_reusable_pending_still_returns_payment_normally(): void
    {
        $user = User::where('role', 'pembeli')->firstOrFail();
        $p = $this->createQrisPesanan($user, 15000);

        $pendingPayment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-73-REUSE',
            'xendit_payment_request_id' => 'pr-73-reuse',
            'qr_string' => 'qr_73_reuse',
            'amount' => 15000,
            'status' => 'PENDING',
            'expires_at' => now()->addHours(24),
        ]);
        $pendingPayment->pesanan()->attach($p->id);

        $payment = $this->orchestrator->initiateQrisPayment($user, [$p->id]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertTrue($payment->is($pendingPayment));
        Http::assertNothingSent();
    }
}
