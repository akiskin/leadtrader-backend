<?php


namespace App\DecisionPoints;


class PostcodeDecisionPoint extends AbstractDecisionPoint
{
    public function calculate()
    {
        $this->value = $this->infoValueByName('postcode');
    }

    public function compare(string $operator, $value): bool
    {
        if ($operator === '=') {
            return $this->value == $value;
        }

        return false;
    }
}
