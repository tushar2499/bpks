<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'txn_ref', 'ticket_id', 'ticket_ids', 'phone', 'operator', 'amount', 'qty',
        'status', 'dcb_txn_id', 'dcb_response', 'failure_reason', 'confirmed_at',
        'nonce', 'consent_url', 'consent_payload', 'consent_initiated_at',
        'gp_consent_id', 'gp_customer_ref', 'gp_charge_request',
    ];

    protected $casts = [
        'confirmed_at'         => 'datetime',
        'consent_initiated_at' => 'datetime',
        'amount'               => 'decimal:2',
        'consent_payload'      => 'array',
        'ticket_ids'           => 'array',
        'qty'                  => 'integer',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function consentLogs()
    {
        return $this->hasMany(\App\Models\ConsentLog::class, 'txn_ref', 'txn_ref');
    }

    public function smsLog()
    {
        return $this->hasOne(\App\Models\SmsLog::class, 'txn_ref', 'txn_ref');
    }
}
