<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegisterNewLead
{
    use Dispatchable, SerializesModels;

    public string $sellCampaignId;

    public array $generalInfo;

    /**
     * Create a new event instance.
     *
     * @param string $sellCampaignId
     * @param array $generalInfo
     */
    public function __construct(string $sellCampaignId, array $generalInfo)
    {
        $this->sellCampaignId = $sellCampaignId;
        $this->generalInfo = $generalInfo;
    }

}
