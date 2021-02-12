<?php


namespace App\Helpers;


use App\Models\Transaction;
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

}
