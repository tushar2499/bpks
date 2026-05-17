<?php

namespace App\Services\DCB;

use Illuminate\Support\Facades\Log;

class RobiConsentService
{
    private string $consentUrl;
    private string $spId;
    private string $serviceId;
    private string $password;

    public function __construct()
    {
        $this->consentUrl = config('dcb.robi.consent_url');
        $this->spId       = config('dcb.robi.sp_id');
        $this->serviceId  = config('dcb.robi.service_id');
        $this->password   = config('dcb.robi.sp_password');
    }

    /**
     * Build Robi WAP consent redirect URL.
     *
     * @param  string $phone        Customer phone in 01XXXXXXXXX format
     * @param  string $txnRef       Our transaction reference
     * @param  string $callbackUrl  URL Robi redirects the customer back to
     * @return array{nonce: string, created: string, payload: array, consent_url: string, amount_poisha: int}
     */
    public function buildConsentUrl(string $phone, string $txnRef, string $callbackUrl): array
    {
        $date         = date('Y-m-d');
        $time         = date('H:i:s');
        $nonce        = $this->nonce($date, $time);
        $created      = $date . 'T' . $time . 'Z';
        $amountPoisha = config('dcb.robi.dcb_amount'); // base amount excl. VAT/SD/SC
        $digestInput  = $nonce . $amountPoisha . $created . $this->password;
        $digest       = base64_encode(hash('sha256', $digestInput, true));
        $msisdn       = $this->toMsisdn($phone);

        Log::debug('Robi consent digest', [
            'spid'        => $this->spId,
            'serviceId'   => $this->serviceId,
            'nonce'       => $nonce,
            'created'     => $created,
            'amount'      => $amountPoisha,
            'msisdn'      => $msisdn,
            'txnRef'      => $txnRef,
            'digestInput' => $digestInput,
            'digest'      => $digest,
        ]);

        $payload = [
            'spid'           => $this->spId,
            'passwordDigest' => $digest,
            'nonce'          => $nonce,
            'created'        => $created,
            'transactionId'  => $txnRef,
            'msisdn'         => $msisdn,
            'callbackURL'    => $callbackUrl,
            'action'         => 3,
            'amount'         => $amountPoisha,
            'serviceId'      => $this->serviceId,
            'language'       => 'en',
        ];

        return [
            'nonce'         => $nonce,
            'created'       => $created,
            'payload'       => $payload,
            'consent_url'   => $this->consentUrl . '?' . http_build_query($payload),
            'amount_poisha' => $amountPoisha,
        ];
    }

    private function nonce(string $date, string $time): string
    {
        $rand     = mt_rand(1000, 9999);
        $datetime = str_replace(['-', ':', ' '], '', $date . $time);
        return $datetime . $rand;
    }

    private function toMsisdn(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 11 && str_starts_with($clean, '01')) return '88' . $clean;
        if (strlen($clean) === 10 && str_starts_with($clean, '1'))  return '880' . $clean;
        return $clean;
    }
}
