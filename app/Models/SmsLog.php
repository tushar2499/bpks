<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'msisdn', 'message', 'txn_ref', 'url',
        'request_body', 'response', 'status_message', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
