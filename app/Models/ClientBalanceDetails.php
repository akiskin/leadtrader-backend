<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientBalanceDetails extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'transaction_id',
        'period',
        'client_id',
        'buy_campaign_id',
        'amount',
        'commission',
    ];

    protected $casts = [
        'period' => 'datetime',
        'amount' => 'float',
        'commission' => 'float',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
