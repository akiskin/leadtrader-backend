<?php


namespace App\DecisionPoints;


use Illuminate\Support\Carbon;

class DaysSinceLastTransactionDecisionPoint extends AbstractDecisionPoint
{
    public function calculate()
    {
        $lastDateAsString = $this->metricValueByName('LAST_TRANS_DATE');
        if (!$lastDateAsString) {
            $this->value = 999;
            return;
        }

        $date = new Carbon($lastDateAsString);
        $this->value = abs($date->diffInDays(now()));
    }
}
