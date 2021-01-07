<?php


namespace App\DecisionPoints;


use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

abstract class AbstractDecisionPoint
{
    protected Lead $lead;
    protected $value;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
        $this->calculate();
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    abstract public function calculate();

    public function compare(string $operator, $value): bool
    {
        if ($operator === '<') {
            return ($this->value === null) ? true : ($this->value < $value);
        }

        if ($operator === '<=') {
            return ($this->value === null) ? true : ($this->value <= $value);
        }

        if ($operator === '>') {
            return ($this->value === null) ? false : ($this->value > $value);
        }

        if ($operator === '>=') {
            return ($this->value === null) ? false : ($this->value >= $value);
        }

        if ($operator === '=') {
            return $this->value == $value;
        }

        return false;
    }

    // HELPERS

    protected function metricValueByName(string $metricName)
    {
        if (!Arr::has($this->lead->metrics, 'decisionPoints')) {
            return null;
        }

        $metric = Arr::first($this->lead->metrics['decisionPoints'], function ($dp) use ($metricName) {
            return $dp['id'] === $metricName;
        });


        if ($metric) {
            return $metric['value'];
        } else {
            return null;
        }
    }
}
