<?php


namespace App\DecisionPoints;


class RequestedAmountDecisionPoint extends AbstractDecisionPoint
{
    public function calculate()
    {
        $this->value = (float) $this->infoValueByName('loan_amount');
    }
}
