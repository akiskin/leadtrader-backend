<?php


namespace App\Helpers;


use App\Integration\ODS;
use App\Jobs\DeliverLeadData;
use App\Jobs\SellLead;
use App\Models\BuyCampaign;
use App\Models\Lead;
use App\Models\LeadActivityLog;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeadProcessing
{
    public static function prepare(Lead $lead)
    {
        if (!Arr::exists($lead->info, 'document_id')) {
            $lead->status = Lead::PREPARED_ERROR_NODOCID;
            $lead->save();
            return; //unrecoverable error, no need to retry job
        }

        $documentId = $lead->info['document_id'];

        try {
            $reprocessedRawData = ODS::retrieveSubmissionData($documentId);

            if ($reprocessedRawData === null) {
                throw new \Exception('ODS did not return JSON');
            }

            LeadActivityLog::persist($lead->getKey(), LeadActivityLog::RAW_DATA_RETRIEVAL, [
                'status' => 200,
                'documentId' => $documentId,
            ]);

        } catch (\Exception $exception) {

            LeadActivityLog::persist($lead->getKey(), LeadActivityLog::RAW_DATA_RETRIEVAL, [
                'status' => $exception->getCode(),
                'body' => $exception->getMessage(),
                'documentId' => $documentId,
            ]);

            $lead->status = Lead::PREPARED_ERROR_NORAWDATA;
            $lead->save();
            return;
        }

        /*
        try {
            $reprocessedRawData = ODS::reprocessRawData(json_encode($rawData));
        } catch (\Exception $exception) {
            $lead->status = Lead::PREPARED_ERROR_REPROCESSING;
            $lead->save();
            return;
        }
        */


        try {
            $decisioningData = ODS::extractDecisioningData($reprocessedRawData);

            /*
             * Simplify - don't store raw data on our side as delivery is going through BF+BS

            //Save rawData files inside existing ZIP file
            $zip = new \ZipArchive();
            $zip->open($lead->data_path);
            $zip->addFromString('ods_original.json', json_encode($reprocessedRawData));
            $zip->setEncryptionName('ods_original.json', \ZipArchive::EM_AES_256, $lead->data_secret);
            $zip->close();
            */

            $lead->metrics = $decisioningData;

            $lead->status = Lead::PREPARED;
            $lead->save();
        } catch (\Exception $exception) {
            $lead->status = Lead::PREPARED_ERROR_POSTPROCESSING;
            $lead->save();
            return;
        }

    }

    public static function sell(Lead $lead, bool $retryIfUnmatched = true)
    {
        //Save NOW as first sell try date (needed for limiting total tries)
        if (!Arr::exists($lead->info, 'sell_first_try_date')) {
            $lead->info = array_merge($lead->info, ['sell_first_try_date' => now()]);
        }

        $lock = Cache::lock('process_financials', 15); //Lock before reading client balances
        $lock->block(3); //If not acquired an Exception will be thrown

        $match = LeadMatching::findBestCandidate($lead);

        if ($match) {
            $prices = self::calculatePriceAndDerivatives($lead, $match);

            self::recordPurchase($lead, $match, $prices);

            $lock->release();

            //Everything else after lock is released

            LeadActivityLog::persist($lead->getKey(), LeadActivityLog::SELL, [
                'status' => Lead::SOLD,
                'buyCampaign' => $match->getKey(),
                'rules' => $match->buy_rules
            ]);

            DeliverLeadData::dispatch($lead->getKey());

            return;
        }

        $lock->release(); //Don't need it for any further actions

        //Max time to sell reached?
        $firstTryDate = new Carbon($lead->info['sell_first_try_date']);
        $maxSellPeriodReached = $firstTryDate->addDays($lead->sellCampaign->expiration)->lessThanOrEqualTo(now());

        if (!$retryIfUnmatched || $maxSellPeriodReached) {
            $lead->status = Lead::NOT_SOLD_NO_MATCH;
            $lead->save();

            LeadActivityLog::persist($lead->getKey(), LeadActivityLog::SELL, [
                'status' => Lead::NOT_SOLD_NO_MATCH,
            ]);

            return;
        }

        //Plan another iteration an hour later
        $lead->status = Lead::SELLING_NO_CURRENT_MATCH;
        $lead->save();

        LeadActivityLog::persist($lead->getKey(), LeadActivityLog::SELL, [
            'status' => Lead::SELLING_NO_CURRENT_MATCH,
        ]);

        SellLead::dispatch($lead->getKey())->delay(now()->addMinutes(60));
    }

    public static function calculatePriceAndDerivatives(Lead $lead, BuyCampaign $buyCampaign)
    {
        $price = $buyCampaign->max_price;
        $sellerCommission = round($price * 0.1, 2); //TODO config per client?
        $buyerCommission = round($price * 0.1, 2); //TODO config per client?

        return [
            'price' => $price,
            'seller_commission' => $sellerCommission,
            'seller_total' => round($price - $sellerCommission, 2),
            'buyer_commission' => $buyerCommission,
            'buyer_total' => round($price + $buyerCommission, 2)
        ];
    }

    public static function recordPurchase(Lead $lead, BuyCampaign $buyCampaign, $amounts)
    {
        DB::transaction(function () use ($lead, $buyCampaign, $amounts) {

            //Update lead
            $lead->status = Lead::SOLD;
            $lead->save();


            //Create transaction
            $transaction = new Transaction([
                'type' => Transaction::TYPE_PURCHASE,
                'amounts' => $amounts
            ]);

            $transaction->lead()->associate($lead);
            $transaction->buyCampaign()->associate($buyCampaign);

            $transaction->save();

            //Update stats, balances, etc
            Financials::onTransaction($transaction);

        });
    }

    public static function leadExportData(Lead $lead): array
    {
        $returnData = [
            'id' => $lead->id,
        ];

        $requiredFields = [
            "document_id",
            "loan_purpose",
            "loan_amount",
            "first_name",
            "last_name",
            "gender",
            "postcode",
            "address",
            "phone",
            "email",
        ];

        foreach ($requiredFields as $fieldName) {
            if (Arr::exists($lead->info, $fieldName)) {
                $returnData[$fieldName] = $lead->info[$fieldName];
            }
        }

        /*
         * Simplify - don't use local files
        if ($lead->data_path && is_string($lead->data_path)) {

            //Save rawData files inside existing ZIP file
            $zip = new \ZipArchive();
            if ($zip->open($lead->data_path) === TRUE) {

                if ($zip->locateName('private.json') !== false) {

                    $options = [
                        'zip' => [
                            'password' => $lead->data_secret
                        ]
                    ];

                    $context = stream_context_create($options);
                    $privateString = file_get_contents('zip://' . $lead->data_path . '#private.json', false, $context);
                    if ($privateString) {
                        $privateData = json_decode($privateString, true);

                        $returnData = array_merge($returnData, $privateData);
                    }

                }
                $zip->close();
            }

        }
        */

        return $returnData;
    }

    public static function deliver(Lead $lead)
    {
        //$buyer = $lead->transaction->buyCampaign->client;
        $buyer = $lead->transaction->buyCampaignForce->client;

        if (!$buyer || !$buyer->brokerflow_key ) {
            //TODO: write delivery/activity log
            return;
        }

        $documentId = $lead->documentId();
        $referralCode = "ldmrkt:1:" . $lead->getKey();

        try {
            ODS::sendDataViaBankStatements($documentId, $referralCode, $buyer->brokerflow_key);

            LeadActivityLog::persist($lead->getKey(), LeadActivityLog::DELIVERY, [
                'status' => 200,
                'key' => $buyer->brokerflow_key
            ]);

        } catch (\Exception $exception) {

            LeadActivityLog::persist($lead->getKey(), LeadActivityLog::DELIVERY, [
                'status' => $exception->getCode(),
                'body' => $exception->getMessage(),
                'key' => $buyer->brokerflow_key,
            ]);

            throw $exception; //for the job
        }

    }

}
