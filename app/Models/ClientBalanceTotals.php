<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientBalanceTotals extends Model
{
    protected $fillable = [
        'client_id',
        'amount'
    ];

    protected $casts = [
        'amount' => 'float'
    ];
}
