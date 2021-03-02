<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyCampaignTotals extends Model
{
    protected $fillable = [
        'buy_campaign_id',
        'amount'
    ];

    protected $casts = [
        'amount' => 'float'
    ];
}
