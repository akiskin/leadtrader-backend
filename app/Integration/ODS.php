<?php
namespace App\Integration;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ODS
{
    public static function retrieveSubmissionData(string $documentId): array | null
    {
        $url = config('integration.ODS.bf_api_url') . "/get-submission/{$documentId}/ARGH?returnDocument=true";

        $response = Http::timeout(30)->withHeaders([
            'X-API-KEY' => config('integration.ODS.bf_api_key'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->get($url);

        if (!$response->successful()) {
            throw new \Exception($response->body(), $response->status());
        }

        return $response->json();
    }

    public static function reprocessRawData(string $data, $extension = 'json')
    {
        $url = config('integration.ODS.bs_api_url') . '/reprocess_transactions';

        $response = Http::attach('file', $data, 'test.' . $extension)->timeout(60)->withHeaders([
            'X-API-KEY' => config('integration.ODS.bs_api_key'),
            'Accept' => 'application/json',
            'X-OUTPUT-VERSION' => '20190901'
        ])->post($url);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }


        $response = $response->json();

        $errors = $response['errors'];
        if (count($errors) > 0) {
            throw new \Exception(json_encode($errors));
        }

        $userToken = $response['user_token'];
        if (!$userToken) {
            throw new \Exception('No user token in response');
        }

        $zipFileContent = self::retrieveFiles($userToken);

        $tempName = Str::uuid()->toString() . '.zip';

        Storage::put($tempName, $zipFileContent);
        $fullPath = Storage::path($tempName);

        $zip = new \ZipArchive();
        $zip->open($fullPath);

        $totalFiles = $zip->count();
        $jsonFileContent = [];
        for ($i = 0; $i < $totalFiles; $i++) {
            $filename = $zip->statIndex($i)['name'];
            if (!Str::contains($filename, '.json')) {
                continue;
            }

            $jsonFileContent = json_decode($zip->getFromIndex($i), true);
        }

        unlink($fullPath);

        return $jsonFileContent;
    }

    public static function retrieveFiles(string $userToken)
    {
        $url = config('integration.ODS.bs_api_url') . '/files';

        $response = Http::withHeaders([
            'X-API-KEY' => config('integration.ODS.bs_api_key'),
            'Accept' => 'application/json'
        ])->get($url, [
            'user_token' => $userToken
        ]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->body();

    }

    public static function extractDecisioningData(array $rawData): array
    {
        $returnData = [
            'decisionPoints' => []
        ];


        //Extract DMs
        if (Arr::exists($rawData, 'decisionMetrics')) {
            foreach ($rawData['decisionMetrics'] as $singleDm) {
                $value = null;

                if ($singleDm['type'] === 'money' || $singleDm['type'] === 'integer') {
                    $value = floatval($singleDm['value']);
                } else {
                    if (Str::contains($singleDm['value'], 'Once off')) {
                        $value = floatval(Str::between($singleDm['value'], '$', '(Once'));
                    }
                }


                if ($value === null) {
                    continue;
                }
                $returnData['decisionPoints'][] = [
                    'id' => $singleDm['id'],
                    'value' => $value
                ];
            }
        }

        //Get last trans date
        $lastTransactionDate = Carbon::now();
        $anyTransactionPresent = false;

        foreach ($rawData['banks'] as $bankData) {
            if (!Arr::exists($bankData, 'bankAccounts')) {
                continue;
            }

            foreach ($bankData['bankAccounts'] as $account) {
                if (!Arr::exists($account, 'transactions')) {
                    continue;
                }

                if (count($account['transactions']) == 0) {
                    continue;
                }

                $anyTransactionPresent = true;

                $localLatest = Carbon::createFromFormat('Y-m-d', $account['transactions'][0]['date']);
                if ($localLatest->lessThan($lastTransactionDate)) {
                    $lastTransactionDate = $localLatest;
                }
            }
        }

        if ($anyTransactionPresent) {
            $returnData['decisionPoints'][] = [
                'id' => 'LAST_TRANS_DATE',
                'value' => $lastTransactionDate
            ];
        }

        return $returnData;
    }

    public static function sendDataViaBankStatements(string $documentId, string $referralCode, string $brokerflowKey)
    {
        $url = config('integration.ODS.bf_api_url') . "/get-submission/{$documentId}/{$referralCode}";

        $response = Http::timeout(30)->withHeaders([
            'X-API-KEY' => $brokerflowKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->get($url);

        if (!$response->successful()) {
            throw new \Exception($response->body(), $response->status());
        }
    }
}
