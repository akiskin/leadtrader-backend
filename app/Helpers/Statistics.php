<?php


namespace App\Helpers;


use App\Models\BuyCampaign;
use App\Models\Client;
use App\Models\Lead;
use App\Models\SellCampaign;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Statistics
{
    public static function uploadedLeadsForPeriod(Client $client, Carbon $after, Carbon $before)
    {
        $result = DB::select('SELECT
                COUNT(*) as total
            FROM
                leads AS leads
                INNER JOIN sell_campaigns as sell_campaigns ON leads.sell_campaign_id = sell_campaigns.id
                AND sell_campaigns.client_id = ?
            WHERE
                leads.created_at BETWEEN ? AND ?',
            [
                $client->getKey(),
                $after,
                $before
        ]);

        return count($result) > 0 ? $result[0]->total : 0;
    }

    public static function soldLeadsForPeriod(Client $client, Carbon $after, Carbon $before)
    {
        $result = DB::select('SELECT
                COUNT(*) as total_count,
                SUM(price) as total_amount,
                SUM(seller_commission) as total_commission
            FROM
                (
                    SELECT
                        JSON_EXTRACT(transactions.amounts, "$.price") AS price,
                        JSON_EXTRACT(transactions.amounts, "$.seller_commission") AS seller_commission
                    FROM
                        transactions AS transactions
                        INNER JOIN leads AS leads ON transactions.lead_id = leads.id
                        INNER JOIN sell_campaigns AS sell_campaigns ON leads.sell_campaign_id = sell_campaigns.id
                    WHERE
                        sell_campaigns.client_id = ?
                        AND transactions.type = ?
                        AND transactions.created_at BETWEEN ? AND ?
                ) as t',
            [
                $client->getKey(),
                Transaction::TYPE_PURCHASE,
                $after,
                $before
            ]);

        return count($result) > 0 ? [
            'count' => $result[0]->total_count ?? 0,
            'amount' => $result[0]->total_amount ?? 0,
            'commission' => $result[0]->total_commission ?? 0
        ] : [
            'count' => 0,
            'amount' => 0,
            'commission' => 0
        ];
    }

    public static function boughtLeadsForPeriod(Client $client, Carbon $after, Carbon $before)
    {
        $result = DB::select('SELECT
                COUNT(*) as total_count,
                SUM(price) as total_amount,
                SUM(commission) as total_commission
            FROM
                (
                    SELECT
                        JSON_EXTRACT(transactions.amounts, "$.price") AS price,
                        JSON_EXTRACT(transactions.amounts, "$.buyer_commission") AS commission
                    FROM
                        transactions AS transactions
                        INNER JOIN buy_campaigns AS buy_campaigns ON transactions.buy_campaign_id = buy_campaigns.id
                    WHERE
                        buy_campaigns.client_id = ?
                        AND transactions.type = ?
                        AND transactions.created_at BETWEEN ? AND ?
                ) as t',
            [
                $client->getKey(),
                Transaction::TYPE_PURCHASE,
                $after,
                $before
            ]);

        return count($result) > 0 ? [
            'count' => $result[0]->total_count ?? 0,
            'amount' => $result[0]->total_amount ?? 0,
            'commission' => $result[0]->total_commission ?? 0
        ] : [
            'count' => 0,
            'amount' => 0,
            'commission' => 0
        ];
    }


    public static function soldLeadsForSellCampaign(SellCampaign $sellCampaign, Carbon $after, Carbon $before)
    {
        $result = DB::select('SELECT
                COUNT(*) as total_count,
                SUM(price) as total_amount,
                SUM(seller_commission) as total_commission
            FROM
                (
                    SELECT
                        JSON_EXTRACT(transactions.amounts, "$.price") AS price,
                        JSON_EXTRACT(transactions.amounts, "$.seller_commission") AS seller_commission
                    FROM
                        transactions AS transactions
                        INNER JOIN leads AS leads ON transactions.lead_id = leads.id
                    WHERE
                        leads.sell_campaign_id = ?
                        AND transactions.type = ?
                        AND transactions.created_at BETWEEN ? AND ?
                ) as t',
            [
                $sellCampaign->getKey(),
                Transaction::TYPE_PURCHASE,
                $after,
                $before
            ]);

        return count($result) > 0 ? [
            'count' => $result[0]->total_count ?? 0,
            'amount' => $result[0]->total_amount ?? 0,
            'commission' => $result[0]->total_commission ?? 0
        ] : [
            'count' => 0,
            'amount' => 0,
            'commission' => 0
        ];
    }

    public static function sellCampaignStatistics(SellCampaign $sellCampaign, Carbon $after, Carbon $before)
    {
        $result = DB::select('SELECT
                status,
                COUNT(*) as total
            FROM
                leads as leads
            WHERE
                leads.sell_campaign_id = ?
                AND leads.created_at BETWEEN ? AND ?
            GROUP BY
                leads.status',
            [
                $sellCampaign->getKey(),
                $after,
                $before
            ]);

        $stats = collect($result)->keyBy('status');

        $uploaded = $stats->reduce(function ($carry, $item) {
            return $carry + $item->total;
        }, 0);

        $rejected = $stats->filter(fn($el) => $el->status > Lead::PREPARED && $el->status < Lead::SOLD)->reduce(function ($carry, $item) {
            return $carry + $item->total;
        }, 0);

        $selling = $stats->filter(fn($el) => $el->status == Lead::SELLING_NO_CURRENT_MATCH)->reduce(function ($carry, $item) {
            return $carry + $item->total;
        }, 0);

        $retired = $stats->filter(fn($el) => $el->status == Lead::NOT_SOLD_NO_MATCH)->reduce(function ($carry, $item) {
            return $carry + $item->total;
        }, 0);

        return [
            'uploaded' => $uploaded,
            'rejected' => $rejected,
            'selling' => $selling,
            'retired' => $retired,
        ];
    }

    public static function boughtLeadsForBuyCampaign(BuyCampaign $buyCampaign, Carbon $after, Carbon $before)
    {
        $result = DB::select('SELECT
                COUNT(*) as total_count,
                SUM(price) as total_amount,
                SUM(buyer_commission) as total_commission
            FROM
                (
                    SELECT
                        JSON_EXTRACT(transactions.amounts, "$.price") AS price,
                        JSON_EXTRACT(transactions.amounts, "$.buyer_commission") AS buyer_commission
                    FROM
                        transactions AS transactions
                    WHERE
                        transactions.buy_campaign_id = ?
                        AND transactions.type = ?
                        AND transactions.created_at BETWEEN ? AND ?
                ) as t',
            [
                $buyCampaign->getKey(),
                Transaction::TYPE_PURCHASE,
                $after,
                $before
            ]);

        return count($result) > 0 ? [
            'count' => $result[0]->total_count ?? 0,
            'amount' => $result[0]->total_amount ?? 0,
            'commission' => $result[0]->total_commission ?? 0
        ] : [
            'count' => 0,
            'amount' => 0,
            'commission' => 0
        ];
    }
}
