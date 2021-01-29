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

    const STATUS_UNASSIGNED = 0;
    const STATUS_NEW = 1;
    const PREPARED = 100;
    const PREPARED_ERROR_NODOCID = 101;
    const PREPARED_ERROR_NORAWDATA = 102;
    const PREPARED_ERROR_REPROCESSING = 103;
    const PREPARED_ERROR_POSTPROCESSING = 103;

    const SOLD = 200;
    const NOT_SOLD_NO_MATCH = 201;
    const SELLING_NO_CURRENT_MATCH = 210;


    protected static function boot()
    {
        parent::boot();

        static::creating(function (Lead $lead) {
            $lead->status_modified_at = Date::now();
        });

        static::updating(function (Lead $lead) {
            if ($lead->isDirty('status')) {
                $lead->status_modified_at = Date::now();
            }
        });
    }

    public function sellCampaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SellCampaign::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getTransactionAttribute()
    {
        return $this->transactions()->first();
    }
}
