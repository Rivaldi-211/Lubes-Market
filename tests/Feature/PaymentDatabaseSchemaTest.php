<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentDatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $username = 'user_test', string $role = 'pembeli'): User
    {
        return User::create([
            'username' => $username,
            'nama_lengkap' => 'Nama Test',
            'email' => $username . '@example.com',
            'no_hp' => '08123456789',
            'password' => 'password123',
            'role' => $role,
            'status' => 'aktif',
        ]);
    }

    public function test_payments_and_payment_pesanan_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));
        $this->assertTrue(Schema::hasTable('payment_pesanan'));
        $this->assertTrue(Schema::hasColumns('payments', [
            'id', 'user_id', 'reference_id', 'xendit_payment_request_id', 'xendit_payment_id',
            'amount', 'payment_method', 'status', 'qr_string', 'raw_response', 'expires_at', 'paid_at', 'created_at', 'updated_at'
        ]));
        $this->assertTrue(Schema::hasColumns('payment_pesanan', [
            'id', 'payment_id', 'pesanan_id', 'created_at', 'updated_at'
        ]));
    }

    public function test_reference_id_must_be_unique(): void
    {
        Payment::create([
            'reference_id' => 'PAY-TEST-001',
            'amount' => 15000,
            'status' => 'CREATING',
        ]);

        $this->expectException(QueryException::class);

        Payment::create([
            'reference_id' => 'PAY-TEST-001',
            'amount' => 20000,
            'status' => 'CREATING',
        ]);
    }

    public function test_payment_default_status_is_creating(): void
    {
        $payment = Payment::create([
            'reference_id' => 'PAY-DEFAULT-STATUS-001',
            'amount' => 15000,
        ]);

        $this->assertSame('CREATING', $payment->status);
    }

    public function test_payment_belongs_to_user_and_user_has_many_payments(): void
    {
        $user = $this->createUser('pembeli_1', 'pembeli');
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-USER-001',
            'amount' => 50000,
            'status' => 'PENDING',
        ]);

        $this->assertTrue($payment->user->is($user));
        $this->assertTrue($user->payments->contains($payment));
    }

    public function test_user_deletion_sets_payment_user_id_to_null(): void
    {
        $user = $this->createUser('pembeli_delete_target', 'pembeli');
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-NULL-ON-DELETE',
            'amount' => 25000,
            'status' => 'PAID',
        ]);

        $user->delete();

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
        $this->assertNull($payment->fresh()->user_id);
    }

    public function test_payment_belongs_to_many_pesanan_and_pesanan_belongs_to_many_payments(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $pesanan = Pesanan::where('pembeli_id', $user->id)->firstOrFail();

        $payment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-PESANAN-001',
            'amount' => (int) explode('.', (string) $pesanan->total_harga)[0],
            'status' => 'CREATING',
        ]);

        $payment->pesanan()->attach($pesanan->id);

        $this->assertTrue($payment->pesanan->contains($pesanan));
        $this->assertTrue($pesanan->payments->contains($payment));
    }

    public function test_hard_deleting_pesanan_with_attached_payment_is_restricted(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $pesanan = Pesanan::where('pembeli_id', $user->id)->firstOrFail();

        $payment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-RESTRICT-DELETE',
            'amount' => 15000,
            'status' => 'PENDING',
        ]);
        $payment->pesanan()->attach($pesanan->id);

        $this->expectException(QueryException::class);
        $pesanan->delete();
    }

    public function test_single_pesanan_can_have_multiple_payment_attempts_history(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $pesanan = Pesanan::where('pembeli_id', $user->id)->firstOrFail();

        $paymentExpired = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-ATTEMPT-01',
            'amount' => (int) explode('.', (string) $pesanan->total_harga)[0],
            'status' => 'EXPIRED',
        ]);
        $paymentExpired->pesanan()->attach($pesanan->id);

        $paymentPaid = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-ATTEMPT-02',
            'amount' => (int) explode('.', (string) $pesanan->total_harga)[0],
            'status' => 'PAID',
        ]);
        $paymentPaid->pesanan()->attach($pesanan->id);

        $this->assertCount(2, $pesanan->fresh()->payments);
        $this->assertTrue($pesanan->payments->pluck('status')->contains('EXPIRED'));
        $this->assertTrue($pesanan->payments->pluck('status')->contains('PAID'));
    }

    public function test_single_payment_can_cover_multiple_pesanan(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $orders = Pesanan::where('pembeli_id', $user->id)->take(2)->get();

        $totalAmount = (int) $orders->sum(fn($o) => (int) explode('.', (string) $o->total_harga)[0]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-MULTI-001',
            'amount' => $totalAmount,
            'status' => 'PENDING',
        ]);

        $payment->pesanan()->attach($orders->pluck('id'));

        $this->assertCount($orders->count(), $payment->pesanan);
    }

    public function test_duplicate_payment_id_and_pesanan_id_is_rejected(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $user = User::where('role', 'pembeli')->firstOrFail();
        $pesanan = Pesanan::where('pembeli_id', $user->id)->firstOrFail();

        $payment = Payment::create([
            'user_id' => $user->id,
            'reference_id' => 'PAY-DUP-001',
            'amount' => 10000,
            'status' => 'PENDING',
        ]);

        $payment->pesanan()->attach($pesanan->id);

        $this->expectException(QueryException::class);
        $payment->pesanan()->attach($pesanan->id);
    }

    public function test_duplicate_xendit_payment_request_id_is_rejected(): void
    {
        Payment::create([
            'reference_id' => 'PAY-PR-001',
            'xendit_payment_request_id' => 'pr-test-unique-001',
            'amount' => 10000,
            'status' => 'PENDING',
        ]);

        $this->expectException(QueryException::class);

        Payment::create([
            'reference_id' => 'PAY-PR-002',
            'xendit_payment_request_id' => 'pr-test-unique-001',
            'amount' => 20000,
            'status' => 'PENDING',
        ]);
    }

    public function test_duplicate_xendit_payment_id_is_rejected(): void
    {
        Payment::create([
            'reference_id' => 'PAY-PY-001',
            'xendit_payment_id' => 'py-test-unique-001',
            'amount' => 10000,
            'status' => 'PAID',
        ]);

        $this->expectException(QueryException::class);

        Payment::create([
            'reference_id' => 'PAY-PY-002',
            'xendit_payment_id' => 'py-test-unique-001',
            'amount' => 20000,
            'status' => 'PAID',
        ]);
    }
}
