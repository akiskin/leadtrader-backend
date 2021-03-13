<?php


namespace App\DecisionPoints;

class DaysSinceLeadUploadDecisionPoint extends AbstractDecisionPoint
{
    public function calculate()
    {
        $date = $this->lead->created_at;
        $this->value = abs($date->diffInDays(now()));
    }
}
