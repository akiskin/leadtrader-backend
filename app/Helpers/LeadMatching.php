<?php

namespace App\Helpers;

use App\DecisionPoints\GamblingDecisionPoint;
use App\Models\BuyCampaign;
use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class LeadMatching
{
    public static function findBestCandidate(Lead $lead): BuyCampaign | null
    {
        //Don't sell back to lead's owner - see #3 param
        $matches = self::fetchMatchingBuyCampaigns($lead->sellCampaign->product->getKey(), $lead->sellCampaign->stop_price, $lead->sellCampaign->client->getKey());

        Log::critical('matched: ' . count($matches));

        if ($matches->count() === 0) {
            return null;
        }

        $decisionPoints = self::calculateDecisionPoints($lead);

        return $matches->sortByDesc('max_price')->first(function (BuyCampaign $buyCampaign) use ($decisionPoints) {
            return $buyCampaign->buy_rules ? self::resolveBuyRules($buyCampaign->buy_rules, $decisionPoints) : true;
        });
    }

    public static function fetchMatchingBuyCampaigns(string $productId, float $maxPrice, string | false $excludeClientById = false): \Illuminate\Database\Eloquent\Collection|array
    {
        //TODO check campaign's (and client's?) budget left
        return BuyCampaign::query()
            ->where('status', '=', BuyCampaign::ACTIVE)
            ->where('product_id', '=', $productId)
            ->where('max_price', '>=', $maxPrice)
            ->when($excludeClientById, function ($q) use ($excludeClientById) {
                $q->where('client_id', '<>', $excludeClientById);
            })->get();
    }

    public static function calculateDecisionPoints(Lead $lead): array
    {
        return [
            'gambling' => new GamblingDecisionPoint($lead)
        ];
    }

    public static function resolveBuyRules(array $buyRules, array $decisionPoints): bool
    {
        //buyRules: <[name: string, operator: string, value: any]>[]
        //Assume it is currently one level, treat it as AND-group
        return self::resolveRulesInAndGroup($buyRules, $decisionPoints);
    }

    private static function resolveRulesInAndGroup(array $buyRules, array $decisionPoints): bool
    {
        $answer = true;

        foreach ($buyRules as $rule) {
            $result = self::resolveOneBuyRule($rule[0], $rule[1], $rule[2], $decisionPoints);
            if (!$result) {
                $answer = false;
                break; //no need to calc others
            }
        }

        return $answer;
    }

    private static function resolveOneBuyRule($name, $operator, $value, array $decisionPoints): bool
    {
        if (!Arr::exists($decisionPoints, $name)) {
            //TODO log or Exception?
            return false;
        }

        return $decisionPoints[$name]->compare($operator, $value);
    }
}
