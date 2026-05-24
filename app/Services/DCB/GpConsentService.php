<?php

namespace App\Services\DCB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GpConsentService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $operatorId;
    private string $merchant;
    private int    $countryCode;
    private string $productId;
    private string $productDesc;
    private string $category;

    public function __construct()
    {
        $this->baseUrl     = (string) config('dcb.grameenphone.base_url');
        $this->username    = (string) config('dcb.grameenphone.username');
        $this->password    = (string) config('dcb.grameenphone.password');
        $this->operatorId  = (string) config('dcb.grameenphone.operator_id');
        $this->merchant    = (string) config('dcb.grameenphone.merchant');
        $this->countryCode = (int)    config('dcb.grameenphone.country_code');
        $this->productId   = (string) config('dcb.grameenphone.product_id');
        $this->productDesc = (string) config('dcb.grameenphone.product_desc');
        $this->category    = (string) config('dcb.grameenphone.category');
    }

    /**
     * Phase 1: Call Telenor DOB consent/prepare API.
     * Returns redirect URL on success.
     */
    public function prepareConsent(string $phone, float $amount, string $txnRef, array $urls): array
    {
        try {
            $msisdn = $this->toMsisdn($phone);

            $payload = [
                'amount'             => $amount,
                'currency'           => 'BDT',
                'msisdn'             => $msisdn,
                'productDescription' => $this->productDesc,
                'urls'               => $urls,
                'operatorId'         => $this->operatorId,
                'merchant'           => $this->merchant,
                'countryCode'        => $this->countryCode,
            ];

            Log::info('GP consent prepare request', ['txn' => $txnRef, 'msisdn' => $msisdn, 'amount' => $amount]);

            $response = Http::timeout(15)
                ->withBasicAuth($this->username, $this->password)
                ->post($this->baseUrl . '/partner/v3/consent/prepare', $payload);

            $body = $response->json();

            Log::info('GP consent prepare response', ['txn' => $txnRef, 'body' => $body]);

            if (($body['resultCode'] ?? '') === 'SUCCESS' && !empty($body['url'])) {
                return [
                    'success'      => true,
                    'redirect_url' => $body['url'],
                    'response'     => json_encode($body),
                ];
            }

            return [
                'success'  => false,
                'reason'   => $body['message'] ?? ($body['resultCode'] ?? 'Consent prepare failed'),
                'response' => json_encode($body),
            ];

        } catch (\Throwable $e) {
            Log::error('GP consent prepare error', ['txn' => $txnRef, 'error' => $e->getMessage()]);
            return [
                'success'  => false,
                'reason'   => 'Connection error: ' . $e->getMessage(),
                'response' => $e->getMessage(),
            ];
        }
    }

    /**
     * Phase 2: Charge the subscriber after consent is approved.
     * Called from the ok callback with consentId + customerReference (acr).
     */
    public function chargePayment(string $acr, string $consentId, string $txnRef, float $amount): array
    {
        try {
            $url = $this->baseUrl . '/partner/payment/v1/' . $acr . '/transactions/amount';

            $payload = [
                'amountTransaction' => [
                    'endUserId'     => $acr,
                    'paymentAmount' => [
                        'chargingInformation' => [
                            'amount'      => (string) $amount,
                            'description' => $this->productDesc,
                            'currency'    => 'BDT',
                        ],
                        'chargingMetaData' => [
                            'purchaseCategoryCode' => $this->productId,
                            'channel'              => 'WEB',
                            'productId'            => $this->productId,
                            'mandateId'            => ['consentId' => $consentId],
                        ],
                    ],
                    'referenceCode'              => $txnRef,
                    'operatorId'                 => $this->merchant,
                    'transactionOperationStatus' => 'Charged',
                ],
            ];

            Log::info('GP charge payment request', ['txn' => $txnRef, 'acr' => $acr, 'amount' => $amount]);

            $response = Http::timeout(15)
                ->withBasicAuth($this->username, $this->password)
                ->post($url, $payload);

            $body = $response->json();

            Log::info('GP charge payment response', ['txn' => $txnRef, 'body' => $body]);

            $status = $body['amountTransaction']['transactionOperationStatus'] ?? null;

            if ($response->successful() && $status === 'Charged') {
                return [
                    'success'    => true,
                    'server_ref' => $body['amountTransaction']['serverReferenceCode'] ?? null,
                    'request'    => json_encode($payload),
                    'response'   => json_encode($body),
                ];
            }

            $errorMsg = $body['requestError']['policyException']['text']
                     ?? ($body['requestError']['serviceException']['text']
                     ?? ($body['message'] ?? 'Charge failed'));

            return [
                'success'  => false,
                'reason'   => $errorMsg,
                'request'  => json_encode($payload),
                'response' => json_encode($body),
            ];

        } catch (\Throwable $e) {
            Log::error('GP charge payment error', ['txn' => $txnRef, 'error' => $e->getMessage()]);
            return [
                'success'  => false,
                'reason'   => 'Connection error: ' . $e->getMessage(),
                'response' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS to subscriber via Telenor DOB SMS API.
     * $acr = customerReference (gp_customer_ref on transaction)
     */
    public function sendSms(string $acr, string $phone, string $message): bool
    {
        try {
            $msisdn = $this->toMsisdn($phone);
            $url    = $this->baseUrl . '/partner/smsmessaging/v2/outbound/tel:+' . $msisdn . '/requests';

            $payload = [
                'outboundSMSMessageRequest' => [
                    'address'       => 'acr:' . $acr,
                    'senderAddress' => 'tel:+' . $msisdn,
                    'messageType'   => 'ARN',
                    'outboundSMSTextMessage' => [
                        'message' => $message,
                    ],
                    'senderName' => 'GP DOB',
                ],
            ];

            Log::info('GP SMS request', ['acr' => $acr, 'msisdn' => $msisdn]);

            $response = Http::timeout(15)
                ->withBasicAuth($this->username, $this->password)
                ->post($url, $payload);

            $body = $response->json();

            Log::info('GP SMS response', ['acr' => $acr, 'body' => $body]);

            return isset($body['outboundSMSMessageRequest']['resourceURL']);

        } catch (\Throwable $e) {
            Log::error('GP SMS error', ['acr' => $acr, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function toMsisdn(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 11 && str_starts_with($clean, '01')) return '88' . $clean;
        if (strlen($clean) === 10 && str_starts_with($clean, '1'))  return '880' . $clean;
        return $clean;
    }
}
