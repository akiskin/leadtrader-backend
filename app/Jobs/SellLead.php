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
use Throwable;

class SellLead implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $leadId;

    public int $tries = 3;

    public int $timeout = 30;

    public function backoff()
    {
        return [3, 9, 27];
    }

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

        if ($lead->status === Lead::PREPARED || $lead->status === Lead::SELLING_NO_CURRENT_MATCH) {
            LeadProcessing::sell($lead);
        }
    }

    public function failed(Throwable $exception)
    {
        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }

        //If Lead's status didn't change, then we want to re-create a sell job for later...
        $lead = Lead::find($this->leadId);

        if (!$lead) {
            return;
        }

        if ($lead->status === Lead::PREPARED || $lead->status === Lead::SELLING_NO_CURRENT_MATCH) {
            SellLead::dispatch($this->leadId)->delay(now()->addMinutes(60));
        }
    }
}
