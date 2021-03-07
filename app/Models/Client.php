<?php

namespace App\Models;

use App\Helpers\BalanceHelper;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Client extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'reg_number',
        'status',
        'brokerflow_key'
    ];

    public function balance(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientBalanceTotals::class);
    }

    public function balanceMoves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClientBalanceDetails::class);
    }

    public function startBalanceAt(Carbon $date)
    {
        return BalanceHelper::startBalanceAt($this->getKey(), $date);
    }

    public function endBalanceAt(Carbon $date)
    {
        return BalanceHelper::endBalanceAt($this->getKey(), $date);
    }
}
