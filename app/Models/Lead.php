<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class Lead extends Model
{
    use HasUuid;

    protected $fillable = [
        'info',
        'metrics',
        'data_path',
        'data_secret',
        'status'
    ];

    protected $casts = [
        'info' => 'array',
        'metrics' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Lead $lead) {
            $lead->status_modified_at = Date::now();
        });

        static::updating(function (Lead $lead) {
            if ($lead->wasChanged('status')) {
                $lead->status_modified_at = Date::now();
            }
        });
    }

    public function sellCampaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SellCampaign::class);
    }
}
