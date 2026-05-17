<?php

namespace App\Services\DCB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RobiService implements DCBInterface
{
    private string $baseUrl;
    private string $spId;
    private string $spPassword;
    private string $serviceId;

    public function __construct()
    {
        $this->baseUrl   = config('dcb.robi.base_url');
        $this->spId      = config('dcb.robi.sp_id');
        $this->spPassword = config('dcb.robi.sp_password');
        $this->serviceId = config('dcb.robi.service_id');
    }

    public function charge(string $phone, float $amount, string $txnRef): array
    {
        try {
            $msisdn = $this->normalizeMsisdn($phone); // e.g. 8801XXXXXXXXX

            $response = Http::timeout(15)->post($this->baseUrl . '/charge', [
                'spId'       => $this->spId,
                'spPassword' => $this->spPassword,
                'serviceId'  => $this->serviceId,
                'msisdn'     => $msisdn,
                'amount'     => (int) ($amount * 100), // paise/poisha
                'externalId' => $txnRef,
            ]);

            $body = $response->json();
            Log::info('Robi DCB response', ['ref' => $txnRef, 'body' => $body]);

            // Adjust field names to match actual Robi API response
            if ($response->successful() && isset($body['resultCode']) && $body['resultCode'] === '0') {
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
                'failure_reason' => $body['resultDesc'] ?? 'Charge failed',
            ];

        } catch (\Throwable $e) {
            Log::error('Robi DCB error', ['ref' => $txnRef, 'error' => $e->getMessage()]);
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
        if (strlen($clean) === 10) return '880' . $clean;       // 1XXXXXXXXX → 8801XXXXXXXXX
        if (strlen($clean) === 11) return '88' . $clean;        // 01XXXXXXXXX → 8801XXXXXXXXX
        return $clean;
    }
}
