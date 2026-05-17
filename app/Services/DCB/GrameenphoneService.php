<?php

namespace App\Services\DCB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrameenphoneService implements DCBInterface
{
    private string $baseUrl;
    private string $appId;
    private string $password;
    private string $serviceId;

    public function __construct()
    {
        $this->baseUrl   = config('dcb.grameenphone.base_url');
        $this->appId     = config('dcb.grameenphone.app_id');
        $this->password  = config('dcb.grameenphone.password');
        $this->serviceId = config('dcb.grameenphone.service_id');
    }

    public function charge(string $phone, float $amount, string $txnRef): array
    {
        try {
            $msisdn = $this->normalizeMsisdn($phone);

            $response = Http::timeout(15)
                ->withBasicAuth($this->appId, $this->password)
                ->post($this->baseUrl . '/subscription/charge', [
                    'serviceId'  => $this->serviceId,
                    'msisdn'     => $msisdn,
                    'amount'     => $amount,
                    'externalId' => $txnRef,
                ]);

            $body = $response->json();
            Log::info('GP DCB response', ['ref' => $txnRef, 'body' => $body]);

            // Adjust field names to match actual GP DSDP API response
            if ($response->successful() && ($body['status'] ?? '') === 'SUCCESS') {
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
            Log::error('GP DCB error', ['ref' => $txnRef, 'error' => $e->getMessage()]);
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
