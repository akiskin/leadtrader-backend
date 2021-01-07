<?php

namespace App\Models;

use App\Models\Scopes\BelongsToClient;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class SellCampaign extends Model
{
    use HasUuid;

    protected $fillable = [
        'status',
        'stop_price',
        'expiration'
    ];

    protected $casts = [
        'stop_price' => 'float',
        'expiration' => 'int'
    ];

    const STATUS_UNKNOWN = 0;
    const STATUS_ACTIVE = 10;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (SellCampaign $sellCampaign) {
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

    public function leads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function currentlySelling(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function dispatchSellJobs()
    {
        //TODO This should be called when campaign is started/unpaused
        //Get all leads in PREPARED -> dispatch Sell job
    }
}
