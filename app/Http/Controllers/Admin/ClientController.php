<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;


class ClientController extends BaseController
{
    public function index()
    {
        return \App\Http\Resources\Admin\Client::collection(Client::with('balance')->get());
    }

    public function show(Client $client)
    {
        \App\Http\Resources\Admin\Client::withoutWrapping();
        return \App\Http\Resources\Admin\Client::make($client->load('balance'));
    }

    public function update(Request $request, Client $client)
    {
        //TODO ?
        return \App\Http\Resources\Admin\Client::make($client);
    }


    public function dashboard(Client $client)
    {
        return [
            'currentBalance' => $client->balance->amount
        ];
    }

    public function tats(Request $request, Client $client)
    {
        $after = $request->input('after');
        $before = $request->input('before');

        $after = $after ? Carbon::parse($after) : Carbon::create(2000, 1,1);
        $before = $before ? Carbon::parse($before) : now();

        $moves = $client->balanceMoves()->with('transaction')
            ->where('period', '>=', $after)
            ->where('period', '<=', $before)
            ->get();

        return [
            'startBalance' => (float) $client->startBalanceAt($after),
            'transactions' => $moves,
            'endBalance' => (float) $client->endBalanceAt($before)
        ];

    }
}
