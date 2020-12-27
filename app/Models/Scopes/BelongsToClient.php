<?php
namespace App\Models\Scopes;

class BelongsToClient implements \Illuminate\Database\Eloquent\Scope
{
    public function apply(\Illuminate\Database\Eloquent\Builder $builder, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return;
        }

        if ($user->client) {
            $builder->where('client_id', '=', $user->client->id);
        }
    }
}
