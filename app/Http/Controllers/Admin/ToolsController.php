<?php

namespace App\Http\Controllers\Admin;

use App\Models\BuyCampaign;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class ToolsController extends BaseController
{
    public function releaseLock()
    {
        Cache::lock('process_financials')->forceRelease();

        return response()->noContent();
    }

    public function activeBuyCampaigns()
    {
         $campaigns = BuyCampaign::query()
             ->withoutGlobalScopes()
             ->with('client.balance')
             ->with('totals')
             ->with('product')
             ->where('status', '=', BuyCampaign::STATUS_ACTIVE)
             ->where('max_price', '>', 0)
             ->where(function($q) {
                 $q->whereDate('start', '<=', now())->orWhereNull('start');
             })
             ->where(function($q) {
                 $q->whereDate('finish', '>=', now())->orWhereNull('finish');
             })
             ->whereHas('client.balance', fn($q) => $q->where('amount', '>', 0))
             ->where(function($q) {
                 $q->whereHas('totals', fn($q) => $q->where(DB::raw('buy_campaigns.budget - buy_campaign_totals.amount'), '>', 0))->orWhereDoesntHave('totals');
             })->get();

         return \App\Http\Resources\BuyCampaign::collection($campaigns);
    }
}
