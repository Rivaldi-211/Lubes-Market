<?php

namespace Tests\Feature;

use App\Exceptions\Xendit\XenditAmbiguousException;
use App\Exceptions\Xendit\XenditConfigurationException;
use App\Exceptions\Xendit\XenditMalformedResponseException;
use App\Exceptions\Xendit\XenditRejectedException;
use App\Models\Payment;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class XenditServiceTest extends TestCase
{
    use RefreshDatabase;

    private XenditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new XenditService();
        config(['services.xendit.secret_key' => 'xnd_development_dummy_key_12345']);
        Http::preventStrayRequests();
    }

    private function createDummyPayment(int $amount = 50000, string $status = 'CREATING'): Payment
    {
        return Payment::create([
            'reference_id' => 'PAY-REF-' . uniqid(),
            'amount' => $amount,
            'payment_method' => 'QRIS',
            'status' => $status,
        ]);
    }

    private function validXenditResponse(Payment $payment, array $overrides = []): array
    {
        return array_merge([
            'payment_request_id' => 'pr-qris-test-12345',
            'reference_id' => $payment->reference_id,
            'type' => 'PAY',
            'country' => 'ID',
            'currency' => 'IDR',
            'request_amount' => $payment->amount,
            'capture_method' => 'AUTOMATIC',
            'channel_code' => 'QRIS',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                [
                    'type' => 'PRESENT_TO_CUSTOMER',
                    'descriptor' => 'QR_STRING',
                    'value' => '00020101021226680016ID.CO.QRIS.WWW011893600914000000000002030005204581253033605802ID5913BUMDes Berkah6013Moncongloe Lappara61059055462070703A0163041234',
                ],
            ],
            'created' => '2026-08-13T10:00:00.000Z',
        ], $overrides);
    }

    public function test_1_empty_secret_key_throws_configuration_exception_without_http_request(): void
    {
        config(['services.xendit.secret_key' => '']);
        $payment = $this->createDummyPayment();

        $this->expectException(XenditConfigurationException::class);
        $this->service->createQrisPayment($payment);

        Http::assertNothingSent();
    }

    public function test_2_request_uses_correct_endpoint(): void
    {
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response([], 201)]);
        $payment = $this->createDummyPayment();

        try {
            $this->service->createQrisPayment($payment);
        } catch (XenditMalformedResponseException $e) {
            // Expected due to empty mock response
        }

        Http::assertSent(fn($request) => $request->url() === 'https://api.xendit.co/v3/payment_requests');
    }

    public function test_3_request_uses_correct_http_basic_auth(): void
    {
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response([], 201)]);
        $payment = $this->createDummyPayment();

        try {
            $this->service->createQrisPayment($payment);
        } catch (XenditMalformedResponseException $e) {
            // Expected due to empty mock response
        }

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization') &&
                $request->header('Authorization')[0] === 'Basic ' . base64_encode('xnd_development_dummy_key_12345:');
        });
    }

    public function test_4_header_api_version_is_set_to_2024_11_11(): void
    {
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response([], 201)]);
        $payment = $this->createDummyPayment();

        try {
            $this->service->createQrisPayment($payment);
        } catch (XenditMalformedResponseException $e) {
            // Expected due to empty mock response
        }

        Http::assertSent(fn($request) => $request->hasHeader('api-version', '2024-11-11'));
    }

    public function test_5_payload_fields_and_channel_properties_object_are_correct(): void
    {
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response([], 201)]);
        $payment = $this->createDummyPayment(75000);

        try {
            $this->service->createQrisPayment($payment);
        } catch (XenditMalformedResponseException $e) {
            // Expected due to empty mock response
        }

        Http::assertSent(function ($request) use ($payment) {
            $body = $request->body();
            $data = json_decode($body, true);

            return $request['reference_id'] === $payment->reference_id &&
                $request['type'] === 'PAY' &&
                $request['country'] === 'ID' &&
                $request['currency'] === 'IDR' &&
                $request['request_amount'] === 75000 &&
                $request['capture_method'] === 'AUTOMATIC' &&
                $request['channel_code'] === 'QRIS' &&
                str_contains($body, '"channel_properties":{}') &&
                is_array($data) &&
                isset($data['channel_properties']) &&
                $data['channel_properties'] === [];
        });
    }

    public function test_6_amount_less_than_1_rejected_before_http_request(): void
    {
        $payment = $this->createDummyPayment(0);

        $this->expectException(InvalidArgumentException::class);
        $this->service->createQrisPayment($payment);

        Http::assertNothingSent();
    }

    public function test_7_amount_greater_than_10_million_rejected_before_http_request(): void
    {
        $payment = $this->createDummyPayment(10_000_001);

        $this->expectException(InvalidArgumentException::class);
        $this->service->createQrisPayment($payment);

        Http::assertNothingSent();
    }

    public function test_8_valid_201_response_parsed_successfully(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $result = $this->service->createQrisPayment($payment);

        $this->assertSame('pr-qris-test-12345', $result['payment_request_id']);
        $this->assertSame($payment->reference_id, $result['reference_id']);
        $this->assertSame(50000, $result['request_amount']);
        $this->assertSame('IDR', $result['currency']);
        $this->assertSame('QRIS', $result['channel_code']);
        $this->assertSame('REQUIRES_ACTION', $result['provider_status']);
        $this->assertSame($mock['actions'][0]['value'], $result['qr_string']);
        $this->assertSame('2026-08-13T10:00:00+00:00', $result['provider_created_at']);
        $this->assertSame('2026-08-15T10:00:00+00:00', $result['expires_at']);
    }

    public function test_9_qr_string_not_at_index_0_is_found_by_type_and_descriptor(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, [
            'actions' => [
                ['type' => 'WEBHOOK', 'descriptor' => 'OTHER', 'value' => 'ignore'],
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '000201_TARGET_QR_STRING'],
            ]
        ]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $result = $this->service->createQrisPayment($payment);

        $this->assertSame('000201_TARGET_QR_STRING', $result['qr_string']);
    }

    public function test_10_missing_qr_string_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['actions' => []]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_11_missing_payment_request_id_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['payment_request_id' => null]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_12_reference_id_mismatch_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['reference_id' => 'PAY-REF-MISMATCH']);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_13_request_amount_mismatch_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['request_amount' => 99999]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_14_currency_besides_idr_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['currency' => 'USD']);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_15_channel_code_besides_qris_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['channel_code' => 'OVO']);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_16_http_400_or_422_throws_explicit_rejected_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response([
            'error_code' => 'INVALID_PAYLOAD',
            'message' => 'Channel is inactive',
        ], 422)]);

        $this->expectException(XenditRejectedException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_17_http_401_throws_explicit_rejected_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response([
            'error_code' => 'UNAUTHORIZED',
            'message' => 'Invalid API key',
        ], 401)]);

        $this->expectException(XenditRejectedException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_18_http_500_throws_ambiguous_provider_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['message' => 'Internal server error'], 500)]);

        $this->expectException(XenditAmbiguousException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_19_failed_connection_throws_ambiguous_transport_exception_with_safe_message(): void
    {
        $payment = $this->createDummyPayment(50000);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->service->createQrisPayment($payment);
            $this->fail('Expected XenditAmbiguousException was not thrown.');
        } catch (XenditAmbiguousException $e) {
            $this->assertSame('Koneksi ke Xendit gagal sebelum hasil Payment Request dapat dipastikan.', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function test_20_service_does_not_use_automatic_retry(): void
    {
        $payment = $this->createDummyPayment(50000);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->service->createQrisPayment($payment);
        } catch (XenditAmbiguousException $e) {
            // Expected
        }

        Http::assertSentCount(1);
    }

    public function test_21_payment_status_remains_creating_after_service_success(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->service->createQrisPayment($payment);

        $this->assertSame('CREATING', $payment->fresh()->status);
    }

    public function test_22_payment_status_remains_creating_after_ambiguous_transport_failure(): void
    {
        $payment = $this->createDummyPayment(50000);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        try {
            $this->service->createQrisPayment($payment);
        } catch (XenditAmbiguousException $e) {
            // Expected
        }

        $this->assertSame('CREATING', $payment->fresh()->status);
    }

    public function test_23_created_timestamp_calculates_expires_at_as_created_plus_48_hours(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['created' => '2026-08-13T12:00:00.000Z']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $result = $this->service->createQrisPayment($payment);

        $this->assertSame('2026-08-13T12:00:00+00:00', $result['provider_created_at']);
        $this->assertSame('2026-08-15T12:00:00+00:00', $result['expires_at']);
    }

    public function test_24_malformed_created_timestamp_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['created' => 'invalid-timestamp-string']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_25_status_requires_action_passes(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['status' => 'REQUIRES_ACTION']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $result = $this->service->createQrisPayment($payment);

        $this->assertSame('REQUIRES_ACTION', $result['provider_status']);
    }

    public function test_26_status_pending_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['status' => 'PENDING']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_27_status_succeeded_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['status' => 'SUCCEEDED']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_28_status_accepting_payments_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['status' => 'ACCEPTING_PAYMENTS']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_29_response_type_not_pay_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['type' => 'RECURRING']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_30_response_country_not_id_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['country' => 'SG']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_31_response_capture_method_not_automatic_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['capture_method' => 'MANUAL']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_32_scientific_notation_amount_string_5e4_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['request_amount' => '5e4']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_33_valid_string_whole_integer_amount_50000_dot_00_is_accepted(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['request_amount' => '50000.00']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $result = $this->service->createQrisPayment($payment);

        $this->assertSame(50000, $result['request_amount']);
    }

    public function test_34_valid_float_amount_50000_dot_0_is_accepted(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['request_amount' => 50000.0]);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $result = $this->service->createQrisPayment($payment);

        $this->assertSame(50000, $result['request_amount']);
    }

    public function test_35_fractional_float_amount_50000_dot_5_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment, ['request_amount' => 50000.5]);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_36_simulate_exact_endpoint_url_and_post_method(): void
    {
        $payment = $this->createDummyPayment(50000, 'PENDING');
        $payment->update(['xendit_payment_request_id' => 'pr_sim_36', 'expires_at' => now()->addHours(24)]);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/pr_sim_36/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Payment simulation queued',
            ], 200),
        ]);

        $result = $this->service->simulateQrisPayment($payment);

        Http::assertSent(function ($req) {
            return $req->url() === 'https://api.xendit.co/v3/payment_requests/pr_sim_36/simulate' &&
                $req->method() === 'POST' &&
                $req->header('api-version')[0] === '2024-11-11' &&
                $req['amount'] === 50000;
        });

        $this->assertSame('PENDING', $result['status']);
        $this->assertSame('Payment simulation queued', $result['message']);
    }

    public function test_37_simulate_http_4xx_throws_xendit_rejected_exception(): void
    {
        $payment = $this->createDummyPayment(50000, 'PENDING');
        $payment->update(['xendit_payment_request_id' => 'pr_sim_37', 'expires_at' => now()->addHours(24)]);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/pr_sim_37/simulate' => Http::response(['message' => 'Denied'], 400),
        ]);

        $this->expectException(XenditRejectedException::class);
        $this->service->simulateQrisPayment($payment);
    }

    public function test_38_simulate_http_5xx_throws_xendit_ambiguous_exception(): void
    {
        $payment = $this->createDummyPayment(50000, 'PENDING');
        $payment->update(['xendit_payment_request_id' => 'pr_sim_38', 'expires_at' => now()->addHours(24)]);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/pr_sim_38/simulate' => Http::response([], 500),
        ]);

        $this->expectException(XenditAmbiguousException::class);
        $this->service->simulateQrisPayment($payment);
    }

    public function test_39_simulate_connection_failure_throws_xendit_ambiguous_exception(): void
    {
        $payment = $this->createDummyPayment(50000, 'PENDING');
        $payment->update(['xendit_payment_request_id' => 'pr_sim_39', 'expires_at' => now()->addHours(24)]);

        Http::fake(['https://api.xendit.co/v3/payment_requests/*' => Http::failedConnection()]);

        $this->expectException(XenditAmbiguousException::class);
        $this->service->simulateQrisPayment($payment);
    }

    public function test_40_simulate_malformed_response_status_not_pending_throws_exception(): void
    {
        $payment = $this->createDummyPayment(50000, 'PENDING');
        $payment->update(['xendit_payment_request_id' => 'pr_sim_40', 'expires_at' => now()->addHours(24)]);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/pr_sim_40/simulate' => Http::response([
                'status' => 'SUCCEEDED',
                'message' => 'Already paid',
            ], 200),
        ]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->simulateQrisPayment($payment);
    }

    public function test_41_simulate_missing_message_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000, 'PENDING');
        $payment->update(['xendit_payment_request_id' => 'pr_sim_41', 'expires_at' => now()->addHours(24)]);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/pr_sim_41/simulate' => Http::response([
                'status' => 'PENDING',
            ], 200),
        ]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->simulateQrisPayment($payment);
    }

    public function test_42_simulate_success_does_not_mutate_payment_status_or_paid_at_or_xendit_payment_id(): void
    {
        $payment = $this->createDummyPayment(50000, 'PENDING');
        $payment->update([
            'xendit_payment_request_id' => 'pr_sim_42',
            'xendit_payment_id' => null,
            'paid_at' => null,
            'expires_at' => now()->addHours(24),
        ]);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/pr_sim_42/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated',
            ], 200),
        ]);

        $this->service->simulateQrisPayment($payment);

        $fresh = $payment->fresh();
        $this->assertSame('PENDING', $fresh->status);
        $this->assertNull($fresh->paid_at);
        $this->assertNull($fresh->xendit_payment_id);
    }

    public function test_43_valid_provider_expires_at_is_parsed_into_expires_at(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment);
        $mock['expires_at'] = '2026-08-15T12:00:00Z';
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $res = $this->service->createQrisPayment($payment);

        $this->assertSame('2026-08-15T12:00:00+00:00', $res['expires_at']);
    }

    public function test_44_missing_provider_expires_at_falls_back_to_created_plus_48h(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment);
        unset($mock['expires_at']);
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $res = $this->service->createQrisPayment($payment);

        $expectedFallback = \Illuminate\Support\Carbon::parse($mock['created'])->addHours(48)->toIso8601String();
        $this->assertSame($expectedFallback, $res['expires_at']);
    }

    public function test_45_malformed_provider_expires_at_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment);
        $mock['expires_at'] = 'not-a-valid-timestamp';
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_46_provider_expires_at_in_past_throws_malformed_response_exception(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment);
        $mock['created'] = '2026-08-13T10:00:00Z';
        $mock['expires_at'] = '2026-08-13T09:59:59Z';
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $this->expectException(XenditMalformedResponseException::class);
        $this->service->createQrisPayment($payment);
    }

    public function test_47_timezone_offset_in_provider_expires_at_is_preserved_correctly(): void
    {
        $payment = $this->createDummyPayment(50000);
        $mock = $this->validXenditResponse($payment);
        $mock['created'] = '2026-08-13T10:00:00+08:00';
        $mock['expires_at'] = '2026-08-15T10:00:00+08:00';
        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response($mock, 201)]);

        $res = $this->service->createQrisPayment($payment);

        $this->assertSame('2026-08-15T10:00:00+08:00', $res['expires_at']);
    }
}
