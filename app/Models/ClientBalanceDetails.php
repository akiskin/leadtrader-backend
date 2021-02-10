<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientBalanceDetails extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'transaction_id',
        'period',
        'client_id',
        'amount'
    ];

    protected $casts = [
        'period' => 'timestamp',
        'amount' => 'float'
    ];
}
