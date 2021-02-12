<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;


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
}
