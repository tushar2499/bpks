<?php

/**
 * Robi DCB WAP Consent Request — Standalone Test
 * Run: php robi_consent_test.php
 */

// ── Config ────────────────────────────────────────────────────────────────────
$SP_ID       = ''; // Robi SP ID (e.g. 123456)
$SP_PASSWORD = ''; // Robi SP Password (e.g. abcdef123456)
$SERVICE_ID  = ''; // Robi Service ID (e.g. 654321)
$CONSENT_URL = 'https://dsdpwap.robi.com.bd/store/wapconfirm';
$DCB_AMOUNT  = 0; // poisha

// ── Test Input ────────────────────────────────────────────────────────────────
$phone       = '01815920898'; // replace with real MSISDN like 018XXXXXXXX 0r 88016XXXXXXXX
$txnRef      = 'TXN-' . time();
$callbackUrl = 'https://yourdomain.com/robi/callback';

// ── Helpers ───────────────────────────────────────────────────────────────────
function toMsisdn(string $phone): string
{
    $clean = preg_replace('/\D/', '', $phone);
    if (strlen($clean) === 11 && str_starts_with($clean, '01')) return '88' . $clean;
    if (strlen($clean) === 10 && str_starts_with($clean, '1'))  return '880' . $clean;
    return $clean;
}

function makeNonce(string $date, string $time): string
{
    $rand     = mt_rand(1000, 9999);
    $datetime = str_replace(['-', ':', ' '], '', $date . $time);
    return $datetime . $rand;
}

// ── Build Consent URL ─────────────────────────────────────────────────────────
$date    = date('Y-m-d');
$time    = date('H:i:s');
$nonce   = makeNonce($date, $time);
$created = $date . 'T' . $time . 'Z';
$msisdn  = toMsisdn($phone);

$digestInput = $nonce . $DCB_AMOUNT . $created . $SP_PASSWORD;
$digest      = base64_encode(hash('sha256', $digestInput, true));

$payload = [
    'spid'           => $SP_ID,
    'passwordDigest' => $digest,
    'nonce'          => $nonce,
    'created'        => $created,
    'transactionId'  => $txnRef,
    'msisdn'         => $msisdn,
    'callbackURL'    => $callbackUrl,
    'action'         => 3,
    'amount'         => $DCB_AMOUNT,
    'serviceId'      => $SERVICE_ID,
    'language'       => 'en',
];

$consentUrl = $CONSENT_URL . '?' . http_build_query($payload);

header('Location: ' . $consentUrl);
exit;
