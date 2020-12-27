<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'reg_number',
        'status'
    ];
}
