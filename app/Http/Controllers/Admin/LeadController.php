<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lead;
use App\Models\Scopes\BelongsToClient;
use Illuminate\Routing\Controller as BaseController;


class LeadController extends BaseController
{
    public function inspect(Lead $lead)
    {
        return [
            'raw' => $lead,
            'sellCampaign' => $lead->sellCampaign()->withoutGlobalScope(BelongsToClient::class)->with('client')->first(),
            'transaction' => $lead->transactions()->with(['buyCampaign' => function ($q) {$q->withoutGlobalScope(BelongsToClient::class)->with('client');}])->first()
        ];
    }
}
