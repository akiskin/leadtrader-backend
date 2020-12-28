<?php

namespace App\Models;

use App\Models\Scopes\BelongsToClient;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class BuyCampaign extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'status',
        'budget',
        'max_price',
        'start',
        'finish',
        'buy_rules'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (BuyCampaign $sellCampaign) {
            $sellCampaign->status_modified_at = Date::now();
        });

        static::updating(function (SellCampaign $sellCampaign) {
            if ($sellCampaign->wasChanged('status')) {
                $sellCampaign->status_modified_at = Date::now();
            }
        });
    }

    protected static function booted()
    {
        parent::booted();

        static::addGlobalScope(new BelongsToClient);
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
