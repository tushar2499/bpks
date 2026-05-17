<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketBatch extends Model
{
    protected $fillable = ['prefix', 'start_number', 'count', 'created_by'];
}
