<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (!isset($model->attributes[$model->getKeyName()])) {
                $model->{$model->getKeyName()} = \Illuminate\Support\Str::orderedUuid()->toString();
            }
        });
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getIncrementing(): bool
    {
        return false;
    }
}
