<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RechargeImport extends Model
{
    protected $fillable = [
        'source_file', 'trx_time', 'msisdn', 'invoice_no', 'dob_msisdn',
        'dob_amount', 'sof_status', 'ers_status', 'dob_status', 'remarks',
        'ticket_count', 'ticket_status', 'txn_ref',
    ];

    protected $casts = [
        'trx_time' => 'datetime',
    ];
}
