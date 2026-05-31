<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = ['ticket_no', 'series', 'sale_tier', 'phone', 'operator', 'status', 'sell_price', 'sold_at'];

    protected $casts = [
        'sold_at' => 'datetime',
        'sell_price' => 'decimal:2',
        'status' => 'integer',
    ];

    public function scopeUnsold($query)
    {
        return $query->where('status', 0);
    }

    public function scopeSold($query)
    {
        return $query->where('status', 1);
    }
}
