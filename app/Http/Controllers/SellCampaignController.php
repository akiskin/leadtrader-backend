<?php

namespace App\Http\Controllers;

use App\Helpers\Statistics;
use App\Http\Resources\LeadForSeller;
use App\Models\Lead;
use App\Models\SellCampaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellCampaignController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        //return \App\Http\Resources\SellCampaign::collection(SellCampaign::all());
        return \App\Http\Resources\SellCampaign::collection(
            SellCampaign::withCount(['leads as leads_total', 'leads as leads_sold' => function (Builder $query) {
                $query->where('status', '=', Lead::SOLD);
        }])->get());
    }

    public function store(Request $request): \App\Http\Resources\SellCampaign
    {
        $user = Auth::user();
        $client = $user->client;
        $productId = $request->get('product_id');

        $campaign = new SellCampaign();
        $campaign->fill($request->all());

        $campaign->client()->associate($client);
        $campaign->product()->associate($productId);

        $campaign->save();

        \App\Http\Resources\SellCampaign::withoutWrapping();
        return \App\Http\Resources\SellCampaign::make($campaign);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SellCampaign  $sellcampaign
     */
    public function update(Request $request, SellCampaign $sellcampaign)
    {
        $sellcampaign->fill($request->all());
        $sellcampaign->save();

        \App\Http\Resources\SellCampaign::withoutWrapping();
        return \App\Http\Resources\SellCampaign::make($sellcampaign);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SellCampaign  $sellCampaign
     * @return \Illuminate\Http\Response
     */
    public function destroy(SellCampaign $sellCampaign)
    {
        //
    }

    public function details(Request $request, SellCampaign $sellCampaign)
    {
        $base = \App\Http\Resources\SellCampaign::make($sellCampaign)->toArray($request);


        $start = $sellCampaign->created_at;
        $end = now()->endOfDay();

        return response()->json([
            'general' => $base,
            'stats' => [
                'sold' => Statistics::soldLeadsForSellCampaign($sellCampaign, $start, $end)
            ],
        ]);
    }

    public function leads(SellCampaign $sellCampaign)
    {
        return LeadForSeller::collection($sellCampaign->leadsWithTransactions);
    }
}
