<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlinkNotifyLog extends Model
{
    protected $fillable = [
        'blink_txn_id', 'txn_ref', 'status', 'charge_amount', 'payload', 'matched',
    ];

    protected $casts = [
        'charge_amount' => 'decimal:2',
    ];
}
