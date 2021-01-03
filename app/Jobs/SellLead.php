<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SellLead implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $leadId;

    public function __construct(string $leadId)
    {
        $this->leadId = $leadId;
    }

    public function tags(): array
    {
        return ['lead:'.$this->leadId];
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping()];
    }

    public function handle()
    {
        $lead = Lead::findOrFail($this->leadId);

        $currentIteration = Arr::has($lead->info, 'sell_iteration') ? $lead->info['sell_iteration'] : 0;

        if ($currentIteration < 3) {
            $lead->status = Lead::SELLING_NO_CURRENT_MATCH;
            $lead->info = array_merge($lead->info, ['sell_iteration' => $currentIteration + 1]);
            $lead->save();

            SellLead::dispatch($this->leadId)->delay(now()->addSeconds(20));
            return;
        }

        $lead->status = Lead::NOT_SOLD_NO_MATCH;
        $lead->save();

    }
}
