<?php

namespace App\Services\Blink;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlinkService
{
    private const BASE_URL = 'http://sdp.b2mwap.com/blink/blink_sdp';
    private const KEYWORD  = 'BPKS';

    public function requestOtp(string $phone, int $qty = 1): array
    {
        $msisdn = $this->toMsisdn($phone);
        $url    = self::BASE_URL . '/DOB/ondemand/bpks_request.php';

        try {
            $response = Http::timeout(15)->get($url, [
                'keyword' => self::KEYWORD,
                'msisdn'  => $msisdn,
                'qty'     => $qty,
            ]);

            $raw  = trim($response->body());
            $json = json_decode($raw, true);
            Log::info('Blink requestOtp', ['msisdn' => $msisdn, 'response' => $raw]);

            $responseCode  = $json['responseCode']  ?? null;
            $transectionId = $json['transectionId'] ?? null;

            return [
                'success'       => $responseCode === '0' || $responseCode === 0,
                'transectionId' => $transectionId,
                'raw'           => $raw,
            ];
        } catch (\Throwable $e) {
            Log::error('Blink requestOtp failed', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);
            return ['success' => false, 'transectionId' => null, 'raw' => $e->getMessage()];
        }
    }

    public function submitOtp(string $phone, string $otp): array
    {
        $msisdn = $this->toMsisdn($phone);
        $url    = self::BASE_URL . '/DOB/ondemand/consent_request.php';

        try {
            $response = Http::timeout(15)->get($url, [
                'keyword' => self::KEYWORD,
                'msisdn'  => $msisdn,
                'otp'     => $otp,
            ]);

            $raw = trim($response->body());
            Log::info('Blink submitOtp', ['msisdn' => $msisdn, 'response' => $raw]);

            return [
                'success' => $raw === '0',
                'raw'     => $raw,
            ];
        } catch (\Throwable $e) {
            Log::error('Blink submitOtp failed', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);
            return ['success' => false, 'raw' => $e->getMessage()];
        }
    }

    public function sendSms(string $phone, string $message, ?string $txnRef = null): bool
    {
        $msisdn = $this->toMsisdn($phone);
        $url    = self::BASE_URL . '/DOB/send_sms.php';

        $smsLog = SmsLog::updateOrCreate(
            ['txn_ref' => $txnRef],
            [
                'msisdn'  => $msisdn,
                'message' => $message,
                'url'     => $url,
                'sent_at' => now(),
            ]
        );

        try {
            $response = Http::timeout(15)->get($url, [
                'keyword' => self::KEYWORD,
                'msisdn'  => $msisdn,
                'msg'     => $message,
            ]);

            $raw     = trim($response->body());
            $success = $raw === '0';

            Log::info('Blink sendSms', ['msisdn' => $msisdn, 'response' => $raw]);

            $smsLog->update([
                'request_body'   => json_encode(['keyword' => self::KEYWORD, 'msisdn' => $msisdn]),
                'response'       => $raw,
                'status_message' => $success ? 'Sent' : 'Failed (response: ' . $raw . ')',
            ]);

            return $success;
        } catch (\Throwable $e) {
            Log::error('Blink sendSms failed', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);

            $smsLog->update([
                'response'       => $e->getMessage(),
                'status_message' => 'Exception',
            ]);

            return false;
        }
    }

    // Normalize to 880XXXXXXXXXX format
    private function toMsisdn(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        if (strlen($clean) === 11 && str_starts_with($clean, '0')) {
            return '880' . substr($clean, 1);
        }

        if (strlen($clean) === 10 && str_starts_with($clean, '1')) {
            return '880' . $clean;
        }

        if (str_starts_with($clean, '880')) {
            return $clean;
        }

        return $clean;
    }
}
