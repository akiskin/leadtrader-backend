<?php

namespace App\Listeners;

use App\Events\RegisterNewLead;
use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
            'data_path' => json_encode($event->privateInfo), //this is temporary. Will be in encrypted s3 file
            'data_secret' => Str::random(32),
            'status' => 1 //TODO: enum with statuses
        ]);

        $lead->save();

        //TODO dispatch event/job for future processing

    }
}
