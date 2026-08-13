<?php

namespace App\Services;

use App\Exceptions\Xendit\XenditAmbiguousException;
use App\Exceptions\Xendit\XenditConfigurationException;
use App\Exceptions\Xendit\XenditMalformedResponseException;
use App\Exceptions\Xendit\XenditRejectedException;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class XenditService
{
    private const BASE_URL = 'https://api.xendit.co/v3/payment_requests';
    private const API_VERSION = '2024-11-11';
    private const HTTP_CONNECT_TIMEOUT_SECONDS = 5;
    private const HTTP_TIMEOUT_SECONDS = 15;
    private const EXPECTED_QRIS_CREATE_STATUS = 'REQUIRES_ACTION';

    public function createQrisPayment(Payment $payment): array
    {
        $secretKey = config('services.xendit.secret_key');
        if (!is_string($secretKey) || trim($secretKey) === '') {
            throw new XenditConfigurationException('Xendit Secret Key belum dikonfigurasi.');
        }

        if ($payment->status !== 'CREATING') {
            throw new InvalidArgumentException("Status payment harus CREATING untuk membuat QRIS Xendit (status saat ini: {$payment->status}).");
        }

        if ($payment->payment_method !== 'QRIS') {
            throw new InvalidArgumentException("Metode pembayaran harus QRIS (metode saat ini: {$payment->payment_method}).");
        }

        if (!is_string($payment->reference_id) || trim($payment->reference_id) === '') {
            throw new InvalidArgumentException('Reference ID payment tidak boleh kosong.');
        }

        $amount = (int) $payment->amount;
        if ($amount < 1 || $amount > 10_000_000) {
            throw new InvalidArgumentException("Nominal pembayaran QRIS harus antara Rp1 dan Rp10.000.000 (nominal saat ini: {$amount}).");
        }

        $payload = [
            'reference_id' => $payment->reference_id,
            'type' => 'PAY',
            'country' => 'ID',
            'currency' => 'IDR',
            'request_amount' => $amount,
            'capture_method' => 'AUTOMATIC',
            'channel_code' => 'QRIS',
            'channel_properties' => (object) [],
            'description' => 'Payment Ref: ' . $payment->reference_id,
        ];

        try {
            $verifySsl = (bool) config('services.xendit.ssl_verify', true);
            $response = Http::withBasicAuth(trim($secretKey), '')
                ->withHeaders([
                    'api-version' => self::API_VERSION,
                    'Accept' => 'application/json',
                ])
                ->withOptions(['verify' => $verifySsl])
                ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::HTTP_TIMEOUT_SECONDS)
                ->post(self::BASE_URL, $payload);
        } catch (ConnectionException $e) {
            throw new XenditAmbiguousException('Koneksi ke Xendit gagal sebelum hasil Payment Request dapat dipastikan.', 0, $e);
        }

        $status = $response->status();

        if ($status >= 400 && $status < 500) {
            $json = $response->json() ?? [];
            $errorCode = is_array($json) && isset($json['error_code']) ? (string) $json['error_code'] : null;
            $message = is_array($json) && isset($json['message']) ? (string) $json['message'] : 'Request ditolak oleh Xendit';
            throw new XenditRejectedException("Xendit menolak pembuatan pembayaran [HTTP {$status}]: {$message}", $status, $errorCode);
        }

        if ($status >= 500) {
            throw new XenditAmbiguousException("Xendit server error [HTTP {$status}]. Outcome pembuatan pembayaran belum dipastikan.");
        }

        if ($status !== 201) {
            throw new XenditMalformedResponseException("Xendit mengembalikan HTTP status {$status} (expected 201 Created).");
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new XenditMalformedResponseException('Response Xendit bukan berupa JSON object yang valid.');
        }

        if (empty($data['payment_request_id']) || !is_string($data['payment_request_id'])) {
            throw new XenditMalformedResponseException('Field payment_request_id tidak ditemukan pada response Xendit.');
        }

        if (!isset($data['reference_id']) || (string) $data['reference_id'] !== $payment->reference_id) {
            $receivedRef = $data['reference_id'] ?? 'null';
            throw new XenditMalformedResponseException("Reference ID response ({$receivedRef}) mismatch dengan payment lokal ({$payment->reference_id}).");
        }

        if (!isset($data['type']) || (string) $data['type'] !== 'PAY') {
            $receivedType = $data['type'] ?? 'null';
            throw new XenditMalformedResponseException("Type response ({$receivedType}) bukan PAY.");
        }

        if (!isset($data['country']) || (string) $data['country'] !== 'ID') {
            $receivedCountry = $data['country'] ?? 'null';
            throw new XenditMalformedResponseException("Country response ({$receivedCountry}) bukan ID.");
        }

        if (!isset($data['capture_method']) || (string) $data['capture_method'] !== 'AUTOMATIC') {
            $receivedCapture = $data['capture_method'] ?? 'null';
            throw new XenditMalformedResponseException("Capture method response ({$receivedCapture}) bukan AUTOMATIC.");
        }

        $normalizedAmount = $this->validateAndNormalizeAmount($data['request_amount'] ?? null, $amount);

        if (!isset($data['currency']) || (string) $data['currency'] !== 'IDR') {
            $receivedCurr = $data['currency'] ?? 'null';
            throw new XenditMalformedResponseException("Currency response ({$receivedCurr}) bukan IDR.");
        }

        if (!isset($data['channel_code']) || (string) $data['channel_code'] !== 'QRIS') {
            $receivedChan = $data['channel_code'] ?? 'null';
            throw new XenditMalformedResponseException("Channel code response ({$receivedChan}) bukan QRIS.");
        }

        if (!isset($data['status']) || (string) $data['status'] !== self::EXPECTED_QRIS_CREATE_STATUS) {
            $receivedStatus = $data['status'] ?? 'null';
            throw new XenditMalformedResponseException("Status provider response ({$receivedStatus}) mismatch dengan expected status (" . self::EXPECTED_QRIS_CREATE_STATUS . ').');
        }

        if (empty($data['created']) || !is_string($data['created'])) {
            throw new XenditMalformedResponseException('Field created timestamp pada response Xendit tidak ditemukan.');
        }

        try {
            $createdCarbon = Carbon::parse($data['created']);
        } catch (Throwable $e) {
            throw new XenditMalformedResponseException('Format created timestamp pada response Xendit tidak valid.');
        }

        $qrString = null;
        $actions = is_array($data['actions'] ?? null) ? $data['actions'] : [];
        foreach ($actions as $action) {
            if (
                is_array($action) &&
                ($action['type'] ?? null) === 'PRESENT_TO_CUSTOMER' &&
                ($action['descriptor'] ?? null) === 'QR_STRING' &&
                !empty($action['value']) &&
                is_string($action['value'])
            ) {
                $qrString = $action['value'];
                break;
            }
        }

        if (is_null($qrString)) {
            throw new XenditMalformedResponseException('Action QR_STRING (PRESENT_TO_CUSTOMER) tidak ditemukan pada response Xendit.');
        }

        $expiresAtCarbon = null;
        if (isset($data['expires_at']) && is_string($data['expires_at']) && trim($data['expires_at']) !== '') {
            try {
                $parsedExpiresAt = Carbon::parse($data['expires_at']);
                if ($parsedExpiresAt->isBefore($createdCarbon)) {
                    throw new XenditMalformedResponseException('Field expires_at pada response Xendit tidak boleh di masa lalu dibanding created timestamp.');
                }
                $expiresAtCarbon = $parsedExpiresAt;
            } catch (Throwable $e) {
                if ($e instanceof XenditMalformedResponseException) {
                    throw $e;
                }
                throw new XenditMalformedResponseException('Format expires_at timestamp pada response Xendit tidak valid.');
            }
        }

        if ($expiresAtCarbon === null) {
            $expiresAtCarbon = $createdCarbon->copy()->addHours(48);
        }

        return [
            'payment_request_id' => (string) $data['payment_request_id'],
            'reference_id' => (string) $data['reference_id'],
            'request_amount' => $normalizedAmount,
            'currency' => (string) $data['currency'],
            'channel_code' => (string) $data['channel_code'],
            'provider_status' => (string) $data['status'],
            'qr_string' => $qrString,
            'provider_created_at' => $createdCarbon->toIso8601String(),
            'expires_at' => $expiresAtCarbon->toIso8601String(),
            'raw_response' => $data,
        ];
    }

    public function simulateQrisPayment(Payment $payment): array
    {
        $secretKey = config('services.xendit.secret_key');
        if (!is_string($secretKey) || trim($secretKey) === '') {
            throw new XenditConfigurationException('Xendit Secret Key belum dikonfigurasi.');
        }

        if ($payment->status !== 'PENDING') {
            throw new InvalidArgumentException("Status payment harus PENDING untuk mensimulasikan pembayaran (status saat ini: {$payment->status}).");
        }

        if ($payment->payment_method !== 'QRIS') {
            throw new InvalidArgumentException("Metode pembayaran harus QRIS (metode saat ini: {$payment->payment_method}).");
        }

        if (empty($payment->xendit_payment_request_id) || !is_string($payment->xendit_payment_request_id)) {
            throw new InvalidArgumentException('Xendit Payment Request ID tidak boleh kosong.');
        }

        $amount = (int) $payment->amount;
        if ($amount < 1 || $amount > 10_000_000) {
            throw new InvalidArgumentException("Nominal pembayaran QRIS harus antara Rp1 dan Rp10.000.000 (nominal saat ini: {$amount}).");
        }

        if ($payment->expires_at !== null && $payment->expires_at->isPast()) {
            throw new InvalidArgumentException('Pembayaran QRIS sudah kadaluarsa (stale pending) dan tidak dapat disimulasikan.');
        }

        $url = self::BASE_URL . '/' . $payment->xendit_payment_request_id . '/simulate';
        $payload = [
            'amount' => $amount,
        ];

        try {
            $verifySsl = (bool) config('services.xendit.ssl_verify', true);
            $response = Http::withBasicAuth(trim($secretKey), '')
                ->withHeaders([
                    'api-version' => self::API_VERSION,
                    'Accept' => 'application/json',
                ])
                ->withOptions(['verify' => $verifySsl])
                ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::HTTP_TIMEOUT_SECONDS)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new XenditAmbiguousException('Koneksi ke Xendit gagal saat mencoba simulasi pembayaran.', 0, $e);
        }

        $status = $response->status();

        if ($status >= 400 && $status < 500) {
            $json = $response->json() ?? [];
            $errorCode = is_array($json) && isset($json['error_code']) ? (string) $json['error_code'] : null;
            $message = is_array($json) && isset($json['message']) ? (string) $json['message'] : 'Simulasi ditolak oleh Xendit';
            throw new XenditRejectedException("Xendit menolak simulasi pembayaran [HTTP {$status}]: {$message}", $status, $errorCode);
        }

        if ($status >= 500) {
            throw new XenditAmbiguousException("Xendit server error [HTTP {$status}]. Outcome simulasi pembayaran belum dipastikan.");
        }

        if ($status !== 200) {
            throw new XenditMalformedResponseException("Xendit mengembalikan HTTP status {$status} untuk simulasi (expected 200 OK).");
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new XenditMalformedResponseException('Response simulasi Xendit bukan berupa JSON object yang valid.');
        }

        if (!isset($data['status']) || (string) $data['status'] !== 'PENDING') {
            $receivedStatus = $data['status'] ?? 'null';
            throw new XenditMalformedResponseException("Status response simulasi ({$receivedStatus}) bukan PENDING.");
        }

        if (empty($data['message']) || !is_string($data['message'])) {
            throw new XenditMalformedResponseException('Field message pada response simulasi Xendit tidak ditemukan atau kosong.');
        }

        return [
            'status' => 'PENDING',
            'message' => (string) $data['message'],
        ];
    }

    private function validateAndNormalizeAmount(mixed $rawAmount, int $expectedAmount): int
    {
        if (is_int($rawAmount)) {
            if ($rawAmount !== $expectedAmount) {
                throw new XenditMalformedResponseException("Request amount response ({$rawAmount}) mismatch dengan payment lokal ({$expectedAmount}).");
            }
            return $rawAmount;
        }

        if (is_float($rawAmount)) {
            if (!is_finite($rawAmount) || $rawAmount < 0 || floor($rawAmount) !== $rawAmount) {
                throw new XenditMalformedResponseException("Field request_amount float ({$rawAmount}) mengandung nilai desimal/invalid.");
            }
            $intVal = (int) $rawAmount;
            if ($intVal !== $expectedAmount) {
                throw new XenditMalformedResponseException("Request amount response ({$rawAmount}) mismatch dengan payment lokal ({$expectedAmount}).");
            }
            return $intVal;
        }

        if (is_string($rawAmount)) {
            if (!preg_match('/^\d+(?:\.0+)?$/', $rawAmount)) {
                throw new XenditMalformedResponseException("Format string request_amount response ({$rawAmount}) invalid atau mengandung nilai desimal/karakter non-digit.");
            }
            $integerStr = explode('.', $rawAmount)[0];
            $intVal = (int) $integerStr;
            if ($intVal !== $expectedAmount) {
                throw new XenditMalformedResponseException("Request amount response ({$rawAmount}) mismatch dengan payment lokal ({$expectedAmount}).");
            }
            return $intVal;
        }

        throw new XenditMalformedResponseException('Field request_amount pada response Xendit tidak valid.');
    }
}
