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
        'buy_rules',
        'product_id'
    ];

    protected $casts = [
        'buy_rules' => 'array',
        'max_price' => 'float',
        'budget' => 'float',
    ];

    const STATUS_NEW = 0;
    const STATUS_ACTIVE = 10;
    const STATUS_PAUSED = 20;
    const STATUS_ARCHIVED = 30;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (BuyCampaign $buyCampaign) {
            $buyCampaign->status_modified_at = Date::now();
        });

        static::updating(function (BuyCampaign $buyCampaign) {
            if ($buyCampaign->wasChanged('status')) {
                $buyCampaign->status_modified_at = Date::now();
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

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transactionsWithLeads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->transactions()->with('lead');
    }

    public function totals(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BuyCampaignTotals::class);
    }
}
