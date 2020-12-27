<?php

namespace App\Http\Controllers;

use App\Models\SellCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellCampaignController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return \App\Http\Resources\SellCampaign::collection(SellCampaign::all());
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
     * Display the specified resource.
     *
     * @param  \App\Models\SellCampaign  $sellCampaign
     * @return \Illuminate\Http\Response
     */
    public function show(SellCampaign $sellCampaign)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SellCampaign  $sellCampaign
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SellCampaign $sellCampaign)
    {
        //
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
}
