<?php

namespace App\Jobs;

use App\Helpers\LeadProcessing;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SellLead implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $leadId;

    public $tries = 3;

    public $timeout = 30;

    public function __construct(string $leadId)
    {
        $this->leadId = $leadId;
    }

    public function uniqueId(): string
    {
        return $this->leadId;
    }

    public function tags(): array
    {
        return ['lead:'.$this->leadId];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }

    public function handle()
    {
        $lead = Lead::find($this->leadId);

        if (!$lead) {
            return;
        }

        if ($lead->status < Lead::PREPARED || $lead->status >= Lead::SOLD) {
            return; //already processed or discarded?
        }


        //try {
            LeadProcessing::sell($lead);
        //} catch (\Exception $exception) {
            //TODO In some cases we want to ->release() this, in some ->fail() based on Exception
        //}
    }
}
