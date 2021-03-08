<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'reference',
        'amounts',
        'type',
        'lead_id',
        'buy_campaign_id',
        'client_id'
    ];

    protected $casts = [
        'reference' => 'array',
        'amounts' => 'array',
        'type' => 'int'
    ];

    const TYPE_PURCHASE = 10;
    const TYPE_BALANCE_INFLOW = 20;
    const TYPE_BALANCE_OUTFLOW = 30; //TODO better naming

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public function lead(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function buyCampaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BuyCampaign::class);
    }

    public function buyCampaignForce()
    {
        return $this->buyCampaign()->withoutGlobalScopes()->first();
    }

    public function getBuyCampaignForceAttribute() {
        return $this->buyCampaignForce();
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
