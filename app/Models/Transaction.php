<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'txn_ref', 'ticket_id', 'phone', 'operator', 'amount',
        'status', 'dcb_txn_id', 'dcb_response', 'failure_reason', 'confirmed_at',
        'nonce', 'consent_url', 'consent_payload', 'consent_initiated_at',
    ];

    protected $casts = [
        'confirmed_at'         => 'datetime',
        'consent_initiated_at' => 'datetime',
        'amount'               => 'decimal:2',
        'consent_payload'      => 'array',
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
