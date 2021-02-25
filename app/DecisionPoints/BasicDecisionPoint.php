<?php


namespace App\DecisionPoints;


use App\Models\Lead;

class BasicDecisionPoint extends AbstractDecisionPoint
{
    private $dm;

    public function __construct(Lead $lead, string $dm)
    {
        $this->dm = $dm;
        $this->lead = $lead;
        $this->calculate();
    }

    public function calculate()
    {
        $this->value = $this->metricValueByName($this->dm); //TODO test for "once off" strings
    }

}
