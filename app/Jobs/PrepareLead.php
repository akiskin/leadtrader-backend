<?php

namespace App\Jobs;

use App\Helpers\LeadProcessing;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareLead implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $leadId;

    public $tries = 5;

    public $timeout = 120;

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

    public function handle()
    {
        $lead = Lead::find($this->leadId);

        if (!$lead) {
            return;
        }

        if ($lead->status >= Lead::PREPARED) {
            return; //already processed or discarded?
        }

        try {
            LeadProcessing::prepare($lead);
        } catch (\Exception $exception) {
            //TODO In some cases we want to ->release() this, in some ->fail() based on Exception
        }

        $lead->refresh();

        if ($lead->status == Lead::PREPARED && $lead->sellCampaign->currentlySelling()) {
            SellLead::dispatch($lead->getKey());
        }
    }
}
