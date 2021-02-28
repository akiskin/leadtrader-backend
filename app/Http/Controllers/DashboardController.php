<?php

namespace App\Http\Controllers;

use App\Helpers\Statistics;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class DashboardController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function tats(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            abort(403);
        }

        if (!$user->client) {
            abort(403);
        }

        $client = $user->client;


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

    public function dashboard()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            abort(403);
        }

        if (!$user->client) {
            abort(403);
        }

        $client = $user->client;


        $today = now()->endOfDay();
        $startOfYear = now()->startOfYear();
        $startOfMonth = now()->startOfMonth();

        $returnData = [
            'uploaded' => [
                'ytd' => [
                    'count' => Statistics::uploadedLeadsForPeriod($client, $startOfYear, $today),
                ],
                'mtd' => [
                    'count' => Statistics::uploadedLeadsForPeriod($client, $startOfMonth, $today),
                ],
            ],
            'sold' => [
                'ytd' => Statistics::soldLeadsForPeriod($client, $startOfYear, $today),
                'mtd' => Statistics::soldLeadsForPeriod($client, $startOfMonth, $today),
            ],
            'bought' => [
                'ytd' => Statistics::boughtLeadsForPeriod($client, $startOfYear, $today),
                'mtd' => Statistics::boughtLeadsForPeriod($client, $startOfMonth, $today),
            ],
        ];

        return response()->json($returnData);
    }
}
