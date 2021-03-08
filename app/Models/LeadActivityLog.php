<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class LeadActivityLog extends Model
{
    protected $fillable = [
        'lead_id',
        'action',
        'message'
    ];

    protected $casts = [
        'action' => 'integer',
        'message' => 'json'
    ];

    const RAW_DATA_RETRIEVAL = 10;
    const SELL = 20;
    const DELIVERY = 30;

    public function lead(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public static function persist(string $lead_id, int $activity, array $message)
    {
        $log = new LeadActivityLog();
        $log->fill([
            'lead_id' => $lead_id,
            'action' => $activity,
            'message' => $message,
        ]);

        $log->save();
    }
}
