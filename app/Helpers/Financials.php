<?php

namespace App\Helpers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Financials
{
    static public function onTransaction(Transaction $transaction)
    {
        //Warning: Locks and transactions MUST be managed before calling this
        //Note: this function MUST be called within active db transaction, where Transaction has been persisted

        //Get current _details (if any)
        //Delete current _details
        //Write to _details table(s)
        //Update final number on _totals table(s), adjusted for old details


        if ($transaction->type === Transaction::TYPE_PURCHASE) {
            $transaction_id = $transaction->getKey();
            $timestamp = $transaction->created_at->format('Y-m-d H:i:s.u');

            //Get target amounts - trans type specific
            $seller_id = $transaction->lead->sellCampaign->client->getKey();
            $seller_amount = $transaction->amounts['seller_total'];
            $seller_commission = $transaction->amounts['seller_commission'];

            $buyer_id = $transaction->buyCampaign->client->getKey();
            $buyer_amount = -1 * $transaction->amounts['buyer_total'];
            $buyer_commission = $transaction->amounts['buyer_commission'];

            //Find out previous details and their amounts
            $old_details = DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->select(['client_id', 'amount'])->get();
            $old_seller_amount = $old_details->where('client_id', $seller_id)->sum('amount') ?? 0;
            $old_buyer_amount = $old_details->where('client_id', $buyer_id)->sum('amount') ?? 0;
            $seller_totals_change = $seller_amount - $old_seller_amount;
            $buyer_totals_change = $buyer_amount - $old_buyer_amount;


            //Write new data generation
            DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->delete();

            self::writeDetails($transaction_id, $timestamp, $seller_id, $seller_amount, $seller_commission);
            self::writeDetails($transaction_id, $timestamp, $buyer_id, $buyer_amount, $buyer_commission, $transaction->buyCampaign->getKey());

            if ($seller_totals_change !== 0.0) {
                self::writeTotals($seller_id, $seller_totals_change);
            }

            if ($buyer_totals_change !== 0.0) {
                self::writeTotals($buyer_id, $buyer_totals_change);
                self::writeBuyCampaignTotals($transaction->buyCampaign->getKey(), -1 * $buyer_totals_change);
            }

        } elseif ($transaction->type === Transaction::TYPE_BALANCE_INFLOW || $transaction->type === Transaction::TYPE_BALANCE_OUTFLOW) {
            $transaction_id = $transaction->getKey();
            $timestamp = $transaction->created_at->format('Y-m-d H:i:s.u');


            //Get target amounts - trans type specific
            $client_id = $transaction->client_id;
            $amount = $transaction->type === Transaction::TYPE_BALANCE_INFLOW ? $transaction->amounts['amount'] : -1*$transaction->amounts['amount'];

            //Find out previous details and their amounts
            $old_details = DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->select(['client_id', 'amount'])->get();
            $old_amount = $old_details->where('client_id', $client_id)->sum('amount') ?? 0; //TODO if client changed?

            $totals_change = $amount - $old_amount;

            //Write new data generation
            DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->delete();

            self::writeDetails($transaction_id, $timestamp, $client_id, $amount);

            if ($totals_change !== 0.0) {
                self::writeTotals($client_id, $totals_change);
            }

        }

    }

    static public function onTransactionDelete(string $transaction_id)
    {
        Cache::lock('process_financials')->block(3, function () use ($transaction_id) {

            //Read current details
            //Delete details
            //Update totals by -1*details amount

            $old_details = DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->select(['client_id', 'amount', 'buy_campaign_id'])->get();

            DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->delete();

            $old_details->each(function($detail) {
                self::writeTotals($detail->client_id, -1 * (float) $detail->amount);

                if ($detail->buy_campaign_id) {
                    self::writeBuyCampaignTotals($detail->buy_campaign_id, $detail->amount);
                }
            });
        });
    }

    static public function recalculateAllClientBalanceTotals()
    {
        //Note: this is technical function
        Cache::lock('process_financials')->block(5, function () {
            DB::beginTransaction();

            //DB::table('client_balance_totals')->truncate(); //This triggers mysql's auto-commit. Need to avoid it
            DB::statement('DELETE FROM client_balance_totals');

            DB::statement('INSERT INTO client_balance_totals (client_id, amount) SELECT client_id, SUM(amount) FROM client_balance_details GROUP BY client_id');

            DB::statement('DELETE FROM buy_campaign_totals');

            DB::statement('INSERT INTO buy_campaign_totals (buy_campaign_id, amount) SELECT buy_campaign_id, SUM(-1 * amount) FROM client_balance_details WHERE buy_campaign_id IS NOT NULL GROUP BY buy_campaign_id');

            DB::commit();
        });
    }

    //Internal implementation

    static public function writeDetails($transaction_id, $period, $client_id, $amount, $commission, $buy_campaign_id = null)
    {
        DB::table('client_balance_details')->insert([
            'transaction_id' => $transaction_id,
            'period' => $period,
            'client_id' => $client_id,
            'amount' => $amount,
            'commission' => $commission,
            'buy_campaign_id' => $buy_campaign_id,
        ]);
    }

    static public function writeTotals($client_id, $amount)
    {
        $affected = DB::table('client_balance_totals')->where('client_id', '=', $client_id)->increment('amount', $amount);
        if ($affected === 0) {
            DB::table('client_balance_totals')->insert([
                'client_id' => $client_id,
                'amount' => $amount
            ]);
        }
    }

    static public function writeBuyCampaignTotals($buy_campaign_id, $amount)
    {
        $affected = DB::table('buy_campaign_totals')->where('buy_campaign_id', '=', $buy_campaign_id)->increment('amount', $amount);
        if ($affected === 0) {
            DB::table('buy_campaign_totals')->insert([
                'buy_campaign_id' => $buy_campaign_id,
                'amount' => $amount
            ]);
        }
    }
}
