<?php

namespace App\Helpers;

use App\DecisionPoints\BasicDecisionPoint;
use App\DecisionPoints\DaysSinceLastTransactionDecisionPoint;
use App\DecisionPoints\DaysSinceLeadUploadDecisionPoint;
use App\DecisionPoints\GenderDecisionPoint;
use App\DecisionPoints\PostcodeDecisionPoint;
use App\DecisionPoints\RequestedAmountDecisionPoint;
use App\Models\BuyCampaign;
use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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
        return BuyCampaign::query()
            ->with('client.balance')
            ->with('totals')
            ->where('status', '=', BuyCampaign::STATUS_ACTIVE)
            ->where('product_id', '=', $productId)
            ->where('max_price', '>=', $maxPrice)
            ->where(function($q) {
                $q->whereDate('start', '<=', now())->orWhereNull('start');
            })
            ->where(function($q) {
                $q->whereDate('finish', '>=', now())->orWhereNull('finish');
            })
            ->whereHas('client.balance', fn($q) => $q->where('amount', '>=', $maxPrice))
            ->where(function($q) use($maxPrice) {
                $q->whereHas('totals', fn($q) => $q->where(DB::raw('buy_campaigns.budget - buy_campaign_totals.amount'), '>=', $maxPrice))->orWhereDoesntHave('totals');

            })
            ->when($excludeClientById, function ($q) use ($excludeClientById) {
                $q->where('client_id', '<>', $excludeClientById);
            })->get();
    }

    public static function calculateDecisionPoints(Lead $lead): array
    {
        return [
            //'gambling' => new GamblingDecisionPoint($lead), //test one
            'daysSinceLastTransaction' => new DaysSinceLastTransactionDecisionPoint($lead),
            'daysSinceLeadUpload' => new DaysSinceLeadUploadDecisionPoint($lead),
            'gender' => new GenderDecisionPoint($lead),
            'postcode' => new PostcodeDecisionPoint($lead),
            'requestedAmount' => new RequestedAmountDecisionPoint($lead),
            'DM004' => new BasicDecisionPoint($lead, 'DM004'),
            'DM005' => new BasicDecisionPoint($lead, 'DM005'),
            'CF003' => new BasicDecisionPoint($lead, 'CF003'),
            'DM001' => new BasicDecisionPoint($lead, 'DM001'),
            'DM003' => new BasicDecisionPoint($lead, 'DM003'),
            'MN007' => new BasicDecisionPoint($lead, 'MN007'),
            'CF010' => new BasicDecisionPoint($lead, 'CF010'),
            'CF005' => new BasicDecisionPoint($lead, 'CF005'),
            'DM006' => new BasicDecisionPoint($lead, 'DM006'),
            'CF004' => new BasicDecisionPoint($lead, 'CF004'),
            'CF008' => new BasicDecisionPoint($lead, 'CF008'),
            'CF009' => new BasicDecisionPoint($lead, 'CF009'),
            'CF012' => new BasicDecisionPoint($lead, 'CF012'),
            'DM012' => new BasicDecisionPoint($lead, 'DM012'),
            'LT007' => new BasicDecisionPoint($lead, 'LT007'),
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
