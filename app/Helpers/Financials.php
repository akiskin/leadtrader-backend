<?php

namespace App\Helpers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Financials
{
    static public function onTransaction(Transaction $transaction)
    {
        //Note: this function MUST be called within active db transaction, where Transaction has been persisted

        //Acquire locks on _totals table(s)
        //Get current _details (if any)
        //Delete current _details
        //Write to _details table(s)
        //Update final number on _totals table(s), adjusted for old details
        //Release locks

        Cache::lock('process_financials')->block(3, function () use ($transaction) {

            if ($transaction->type === Transaction::TYPE_PURCHASE) {
                $seller_id = $transaction->lead->sellCampaign->client->getKey();
                $seller_amount = $transaction->amounts['seller_total'];


                $buyer_id = $transaction->buyCampaign->client->getKey();
                $buyer_amount = -1 * $transaction->amounts['buyer_total'];



                $transaction_id = $transaction->getKey();
                $timestamp = $transaction->created_at->format('Y-m-d H:i:s.u');

                $old_details = DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->select(['client_id', 'amount'])->get();
                $old_seller_amount = $old_details->where('client_id', $seller_id)->sum('amount') ?? 0;
                $old_buyer_amount = $old_details->where('client_id', $buyer_id)->sum('amount') ?? 0;

                $seller_totals_change = $seller_amount - $old_seller_amount;
                $buyer_totals_change = $buyer_amount - $old_buyer_amount;


                DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->delete();

                self::writeDetails($transaction_id, $timestamp, $seller_id, $seller_amount);
                self::writeDetails($transaction_id, $timestamp, $buyer_id, $buyer_amount);

                if ($seller_totals_change !== 0.0) {
                    self::writeTotals($seller_id, $seller_totals_change);
                }

                if ($buyer_totals_change !== 0.0) {
                    self::writeTotals($buyer_id, $buyer_totals_change);
                }



            }


        });
    }

    static public function onTransactionDelete(string $transaction_id)
    {
        Cache::lock('process_financials')->block(3, function () use ($transaction_id) {

            //Read current details
            //Delete details
            //Update totals by -1*details amount

            $old_details = DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->select(['client_id', 'amount'])->get();

            DB::table('client_balance_details')->where('transaction_id', '=', $transaction_id)->delete();

            $old_details->each(function($detail) {
                self::writeTotals($detail->client_id, -1 * (float) $detail->amount);
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

            DB::commit();
        });
    }

    //Internal implementation

    static public function writeDetails($transaction_id, $period, $client_id, $amount)
    {
        DB::table('client_balance_details')->insert([
            'transaction_id' => $transaction_id,
            'period' => $period,
            'client_id' => $client_id,
            'amount' => $amount
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






}
