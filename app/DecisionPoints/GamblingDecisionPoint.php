<?php


namespace App\DecisionPoints;


class GamblingDecisionPoint extends AbstractDecisionPoint
{

    public function calculate()
    {
        $this->value = $this->metricValueByName('gambling'); //It will be smth like DM0XX later
    }

}
