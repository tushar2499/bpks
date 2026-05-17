<?php

namespace App\Services\DCB;

interface DCBInterface
{
    /**
     * Initiate a charge request.
     * Returns ['success' => bool, 'dcb_txn_id' => string|null, 'response' => string, 'failure_reason' => string|null]
     */
    public function charge(string $phone, float $amount, string $txnRef): array;
}
