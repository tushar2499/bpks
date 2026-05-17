<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'txn_ref', 'msisdn', 'step', 'data', 'note',
    ];

    protected $casts = [
        'data'       => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(string $txnRef, string $msisdn, string $step, mixed $data = null, ?string $note = null): void
    {
        static::create([
            'txn_ref' => $txnRef,
            'msisdn'  => $msisdn,
            'step'    => $step,
            'data'    => $data,
            'note'    => $note,
        ]);
    }
}
