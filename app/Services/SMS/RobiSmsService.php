<?php

namespace App\Services\SMS;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RobiSmsService
{
    private string $tokenUrl;
    private string $username;
    private string $password;
    private string $authHeader;
    private string $senderAddress;

    public function __construct()
    {
        $this->tokenUrl      = config('dcb.robi_sms.token_url');
        $this->username      = config('dcb.robi_sms.username');
        $this->password      = config('dcb.robi_sms.password');
        $this->authHeader    = config('dcb.robi_sms.auth_header');
        $this->senderAddress = config('dcb.robi_sms.sender_address');
    }

    public function send(string $msisdn, string $message, ?string $txnRef = null): bool
    {
        try {
            $token = $this->fetchToken();
            if (!$token) return false;

            $to  = $this->toMsisdn($msisdn);
            $url = 'https://apigate.robi.com.bd/Ext/smsmessaging/v1/outbound/tel:+' . $to . '/requests';

            $smsLog = SmsLog::create([
                'msisdn'  => $to,
                'message' => $message,
                'txn_ref' => $txnRef,
                'url'     => $url,
                'sent_at' => now(),
            ]);

            $notifyUrl = url('/sms-notify/' . $smsLog->id);

            $body = [
                'outboundSMSMessageRequest' => [
                    'address'                => ['tel:+' . $to],
                    'senderAddress'          => $this->senderAddress,
                    'outboundSMSTextMessage' => ['message' => $message],
                    'clientCorrelator'       => 'B2M Technologies Ltd',
                    'receiptRequest'         => [
                        'notifyURL'    => $notifyUrl,
                        'callbackData' => 'bpks-ticket-sms',
                        'senderName'   => 'B2M Technologies Ltd',
                    ],
                ],
            ];

            $response     = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])->asJson()->post($url, $body);

            $responseData = $response->json();

            $statusMessage = $responseData['outboundSMSMessageRequest']['deliveryInfoList']['deliveryInfo'][0]['deliveryStatus']
                ?? 'Unknown';

            $smsLog->update([
                'request_body'   => json_encode($body),
                'response'       => json_encode($responseData),
                'status_message' => $statusMessage,
            ]);

            Log::info('RobiSmsService: sent', ['msisdn' => $to, 'status' => $response->status(), 'delivery' => $statusMessage]);

            return $response->successful();

        } catch (\Throwable $e) {
            Log::error('RobiSmsService: error', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function fetchToken(): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ])->asForm()->post($this->tokenUrl, [
            'grant_type' => 'password',
            'username'   => $this->username,
            'password'   => $this->password,
            'scope'      => 'PRODUCTION',
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('RobiSmsService: token failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    private function toMsisdn(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 11 && str_starts_with($clean, '01')) return '88' . $clean;
        if (strlen($clean) === 10 && str_starts_with($clean, '1'))  return '880' . $clean;
        return $clean;
    }
}
