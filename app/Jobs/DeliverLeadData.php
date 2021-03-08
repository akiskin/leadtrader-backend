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
use Throwable;

class DeliverLeadData implements ShouldQueue, ShouldBeUnique
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

        if ($lead->status <> Lead::SOLD) {
            return;
        }

        LeadProcessing::deliver($lead);
    }
}
