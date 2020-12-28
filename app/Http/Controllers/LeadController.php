<?php

namespace App\Http\Controllers;

use App\Events\RegisterNewLead;
use App\Models\SellCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        //Check if sell campaign belongs to current user

        $sellCampaignId = $request->input('sell_campaign');
        if (!$sellCampaignId) {
            abort(400, 'Missing Sell Campaign');
        }

        $sellCampaign = SellCampaign::find($sellCampaignId); //Note: search within BelongsToClient scope automatically
        if (!$sellCampaign) {
            abort(400, 'Missing Sell Campaign');
        }


        RegisterNewLead::dispatch($sellCampaignId, [], []);

        return response('', 201);
    }

    public function bulk(Request $request)
    {
        $sellCampaignId = $request->input('sell_campaign');
        if (!$sellCampaignId) {
            abort(400, 'Missing Sell Campaign');
        }

        $sellCampaign = SellCampaign::find($sellCampaignId); //Note: search within BelongsToClient scope automatically
        if (!$sellCampaign) {
            abort(400, 'Missing Sell Campaign');
        }

        $leads = $request->input('lead_data');

        //TODO validation

        $processingResults = [];
        foreach ($leads as $index => $leadData) {

            $info = [];
            $private = [];

            $acceptedKeys = [
                "document_id" => [ "required" => true, "private" => false ],
                "loan_purpose" => [ "required" => true, "private" => false ],
                "loan_amount" => [ "required" => true, "private" => false ],

                "first_name" => [ "required" => true, "private" => true ],
                "last_name" => [ "required" => true, "private" => true ],
                "address" => [ "required" => true, "private" => true ],
                "phone" => [ "required" => true, "private" => true ],
                "email" => [ "required" => true, "private" => true ],
            ];

            foreach ($acceptedKeys as $key => $options) {

                $exists = Arr::exists($leadData, $key);
                //TODO required check

                $value = Arr::get($leadData, $key);

                if ($options['private']) {
                    $private[$key] = $value;
                } else {
                    $info[$key] = $value;
                }
            }


            RegisterNewLead::dispatch($sellCampaignId, $info, $private);
            //TODO error handling


            $processingResults[$index] = [
                'success' => true,
            ];
        }



        return response($processingResults);
    }
}
