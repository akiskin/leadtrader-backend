<?php

namespace App\Listeners;

use App\Events\RegisterNewLead;
use App\Jobs\PrepareLead;
use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegisterNewLeadHandler
{
    /**
     * Handle the event.
     *
     * @param  RegisterNewLead  $event
     * @return void
     */
    public function handle(RegisterNewLead $event)
    {
        $lead = new Lead();

        $lead->sellCampaign()->associate($event->sellCampaignId);

        $lead->fill([
            'info' => $event->generalInfo, //save to JSON as is
            'data_secret' => Str::random(32),
            'status' => Lead::STATUS_NEW
        ]);

        $lead->save();

        $fileName = storage_path('app/leads') . '/' . $lead->getKey() . '.zip';

        //Generate ZIP file with private info
        $zip = new \ZipArchive();
        $zip->open($fileName, \ZipArchive::CREATE);
        $zip->addFromString('private.json', json_encode($event->privateInfo));
        $zip->setEncryptionName('private.json', \ZipArchive::EM_AES_256, $lead->data_secret);
        $zip->close();

        $lead->data_path = $fileName;
        $lead->save();


        PrepareLead::dispatch($lead->getKey());
    }
}
