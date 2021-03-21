<?php


namespace App\DecisionPoints;


class GenderDecisionPoint extends AbstractDecisionPoint
{
    public function calculate()
    {
        $this->value = $this->infoValueByName('gender');
    }

    public function compare(string $operator, $value): bool
    {
        if ($operator === '=') {
            return $this->value == $value;
        }

        return false;
    }
}
