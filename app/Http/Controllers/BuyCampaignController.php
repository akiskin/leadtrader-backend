<?php

namespace App\Http\Controllers;

use App\Helpers\LeadProcessing;
use App\Http\Resources\TransactionForBuyer;
use App\Models\BuyCampaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BuyCampaignController extends Controller
{
    public function index()
    {
        return \App\Http\Resources\BuyCampaign::collection(BuyCampaign::all());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $client = $user->client;
        $productId = $request->input('product_id');

        $campaign = new BuyCampaign();
        $campaign->fill($request->all());

        $campaign->client()->associate($client);
        $campaign->product()->associate($productId);

        $campaign->save();

        \App\Http\Resources\BuyCampaign::withoutWrapping();
        return \App\Http\Resources\BuyCampaign::make($campaign);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BuyCampaign  $buyCampaign
     * @return \Illuminate\Http\Response
     */
    public function show(BuyCampaign $buyCampaign)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BuyCampaign  $buyCampaign
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BuyCampaign $buyCampaign)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BuyCampaign  $buyCampaign
     * @return \Illuminate\Http\Response
     */
    public function destroy(BuyCampaign $buyCampaign)
    {
        //
    }

    public function leads(BuyCampaign $buyCampaign): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return TransactionForBuyer::collection($buyCampaign->transactionsWithLeads);
    }

    public function leadsForExport(Request $request, BuyCampaign $buyCampaign): \Illuminate\Http\JsonResponse
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $transactions = $buyCampaign->transactionsWithLeads()
            ->when($start, function(Builder $q) use ($start) {
                $q->where('created_at', '>=', Carbon::parse($start));
            })
            ->when($end, function(Builder $q) use ($end) {
                $q->where('created_at', '<=', Carbon::parse($end));
            })
            ->get();

        $returnData = [];

        foreach ($transactions as $transaction) {
            $returnData[] = ['purchase_date' => $transaction->created_at] + LeadProcessing::leadExportData($transaction->lead);
        }

        return response()->json($returnData);
    }

}
