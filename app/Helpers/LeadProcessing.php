<?php


namespace App\Helpers;


use App\Integration\ODS;
use App\Jobs\SellLead;
use App\Models\BuyCampaign;
use App\Models\Lead;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeadProcessing
{
    public static function prepare(Lead $lead)
    {
        if (!Arr::exists($lead->info, 'documentId')) {
            $lead->status = Lead::PREPARED_ERROR_NODOCID;
            $lead->save();
            return; //unrecoverable error, no need to retry job
        }

        $documentId = $lead->info['documentId'];

        try {
            $rawData = ODS::retrieveSubmissionData($documentId);
        } catch (\Exception $exception) {
            //TODO make different exceptions, as some might be recoverable, some - not
            //TODO logging
            $lead->status = Lead::PREPARED_ERROR_NORAWDATA;
            $lead->save();
            return;
        }

        try {
            $reprocessedRawData = ODS::reprocessRawData(json_encode($rawData));
        } catch (\Exception $exception) {
            //TODO make different exceptions, as some might be recoverable, some - not
            //TODO logging
            $lead->status = Lead::PREPARED_ERROR_REPROCESSING;
            $lead->save();
            return;
        }


        $decisioningData = ODS::extractDecisioningData($reprocessedRawData);


        //Save rawData files inside existing ZIP file
        $zip = new \ZipArchive();
        $zip->open($lead->data_path);
        $zip->addFromString('ods_original.json', json_encode($rawData));
        $zip->setEncryptionName('ods_original.json', \ZipArchive::EM_AES_256, $lead->data_secret);

        $zip->addFromString('ods_reprocessed.json', json_encode($reprocessedRawData));
        $zip->setEncryptionName('ods_reprocessed.json', \ZipArchive::EM_AES_256, $lead->data_secret);
        $zip->close();


        $lead->metrics = $decisioningData;

        $lead->status = Lead::PREPARED;
        $lead->save();

    }

    public static function sell(Lead $lead, bool $retryIfUnmatched = true)
    {
        //Save NOW as first sell try date (needed for limiting total tries)
        if (!Arr::exists($lead->info, 'sell_first_try_date')) {
            $lead->info = array_merge($lead->info, ['sell_first_try_date' => now()]);
        }


        $match = LeadMatching::findBestCandidate($lead);

        if ($match) {

            $prices = self::calculatePriceAndDerivatives($lead, $match);

            try {
                self::recordPurchase($lead, $match, $prices);
            } catch (\Exception $exception) {
                //TODO what to do here????
                return;
            }

            //TODO Send notifications (if needed)

            return;
        }

        //Max time to sell reached?
        $firstTryDate = new Carbon($lead->info['sell_first_try_date']);
        $maxSellPeriodReached = $firstTryDate->addDays($lead->sellCampaign->expiration)->lessThanOrEqualTo(now());

        if (!$retryIfUnmatched || $maxSellPeriodReached) {
            $lead->status = Lead::NOT_SOLD_NO_MATCH;
            $lead->save();
            return;
        }

        //Plan another iteration an hour later
        $lead->status = Lead::SELLING_NO_CURRENT_MATCH;
        $lead->save();
        SellLead::dispatch($lead->getKey())->delay(now()->addMinutes(60));
    }

    public static function calculatePriceAndDerivatives(Lead $lead, BuyCampaign $buyCampaign)
    {
        $price = $buyCampaign->max_price;
        $sellerCommission = round($price * 0.1, 2); //TODO config per client?
        $buyerCommission = round($price * 0.1, 2); //TODO config per client?

        return [
            'price' => $buyCampaign->max_price,
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
                'amounts' => $amounts
            ]);

            $transaction->lead()->associate($lead);
            $transaction->buyCampaign()->associate($buyCampaign);

            $transaction->save();

            //TODO update stats, balances, etc

        });
    }

    public static function leadExportData(Lead $lead): array
    {
        $returnData = [
            'id' => $lead->id,
        ];

        if (Arr::exists($lead->info, 'document_id')) {
            $returnData['document_id'] = $lead->info['document_id'];
        }

        if (Arr::exists($lead->info, 'loan_purpose')) {
            $returnData['loan_purpose'] = $lead->info['loan_purpose'];
        }

        if (Arr::exists($lead->info, 'loan_amount')) {
            $returnData['loan_amount'] = $lead->info['loan_amount'];
        }



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




        return $returnData;
    }
}
