<?php


namespace App\Helpers;


use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BalanceHelper
{

    static public function createOtherTransaction(string $client_id, float $amount, string $reference = '')
    {
        $lock = Cache::lock('process_financials'); //Lock before reading client balances
        $lock->block(3); //If not acquired an Exception will be thrown

        DB::beginTransaction();

        $transaction = new Transaction([
            'type' => $amount >=0 ? Transaction::TYPE_BALANCE_INFLOW : Transaction::TYPE_BALANCE_OUTFLOW,
            'amounts' => [ 'amount' => abs($amount) ],
            'client_id' => $client_id,
            'reference' => $reference
        ]);

        $transaction->save();

        Financials::onTransaction($transaction);

        DB::commit();

        $lock->release();

        return $transaction;
    }

    static public function startBalanceAt(string $client_id, Carbon $date)
    {
        $result = DB::select('SELECT SUM(amount) AS balance FROM client_balance_details WHERE client_id = ? AND period < ? GROUP BY client_id ',
        [$client_id, $date]);

        return count($result) > 0 ? $result[0]->balance : 0;
    }

    static public function endBalanceAt(string $client_id, Carbon $date)
    {
        $result = DB::select('SELECT SUM(amount) AS balance FROM client_balance_details WHERE client_id = ? AND period <= ? GROUP BY client_id ',
            [$client_id, $date]);

        return count($result) > 0 ? $result[0]->balance : 0;
    }

}
