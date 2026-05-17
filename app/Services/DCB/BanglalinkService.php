<?php

namespace App\Services\DCB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BanglalinkService implements DCBInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $serviceId;

    public function __construct()
    {
        $this->baseUrl      = config('dcb.banglalink.base_url');
        $this->clientId     = config('dcb.banglalink.client_id');
        $this->clientSecret = config('dcb.banglalink.client_secret');
        $this->serviceId    = config('dcb.banglalink.service_id');
    }

    public function charge(string $phone, float $amount, string $txnRef): array
    {
        try {
            $msisdn = $this->normalizeMsisdn($phone);

            $response = Http::timeout(15)->post($this->baseUrl . '/charge', [
                'clientId'     => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'serviceId'    => $this->serviceId,
                'msisdn'       => $msisdn,
                'amount'       => $amount,
                'externalId'   => $txnRef,
            ]);

            $body = $response->json();
            Log::info('BL DCB response', ['ref' => $txnRef, 'body' => $body]);

            // Adjust field names to match actual Banglalink API response
            if ($response->successful() && ($body['statusCode'] ?? '') === '200') {
                return [
                    'success'        => true,
                    'dcb_txn_id'     => $body['transactionId'] ?? null,
                    'response'       => json_encode($body),
                    'failure_reason' => null,
                ];
            }

            return [
                'success'        => false,
                'dcb_txn_id'     => null,
                'response'       => json_encode($body),
                'failure_reason' => $body['message'] ?? 'Charge failed',
            ];

        } catch (\Throwable $e) {
            Log::error('BL DCB error', ['ref' => $txnRef, 'error' => $e->getMessage()]);
            return [
                'success'        => false,
                'dcb_txn_id'     => null,
                'response'       => $e->getMessage(),
                'failure_reason' => 'Connection error',
            ];
        }
    }

    private function normalizeMsisdn(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 10) return '880' . $clean;
        if (strlen($clean) === 11) return '88' . $clean;
        return $clean;
    }
}
