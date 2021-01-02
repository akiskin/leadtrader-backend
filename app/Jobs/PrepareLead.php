<?php

namespace App\Jobs;

use App\Integration\ODS;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class PrepareLead implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $leadId;

    public function __construct(string $leadId)
    {
        $this->leadId = $leadId;
    }

    public function uniqueId()
    {
        return $this->leadId;
    }

    public function handle()
    {
        $lead = Lead::findOrFail($this->leadId);

        if ($lead->status != Lead::STATUS_NEW) { //TODO: enum with statuses
            return; //already processed or discarded?
        }


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
}
