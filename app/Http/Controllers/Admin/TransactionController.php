<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\BalanceHelper;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;


class TransactionController extends BaseController
{

    public function store(Request $request)
    {
        $client_id = $request->input('client_id');
        $amount = $request->input('amount');
        $reference = $request->input('reference');

        $transaction = BalanceHelper::createOtherTransaction($client_id, $amount, $reference);

        return $transaction->toArray();
    }
}
