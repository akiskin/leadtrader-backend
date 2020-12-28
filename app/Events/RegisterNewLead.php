<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegisterNewLead
{
    use Dispatchable, SerializesModels;

    public string $sellCampaignId;

    public array $generalInfo;

    public array $privateInfo;

    /**
     * Create a new event instance.
     *
     * @param string $sellCampaignId
     * @param array $generalInfo
     * @param array $privateInfo
     */
    public function __construct(string $sellCampaignId, array $generalInfo, array $privateInfo)
    {
        $this->sellCampaignId = $sellCampaignId;
        $this->generalInfo = $generalInfo;
        $this->privateInfo = $privateInfo;
    }

}
