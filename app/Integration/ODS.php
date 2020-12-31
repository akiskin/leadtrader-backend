<?php
namespace App\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ODS
{
    public static function retrieveSubmissionData(string $documentId): array
    {
        $url = config('integration.ODS.bf_api_url') . "/get-submission/{$documentId}/ARGH?returnDocument=true";

        $response = Http::withHeaders([
            'X-API-KEY' => config('integration.ODS.bf_api_key'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->get($url);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    public static function reprocessRawData(string $data)
    {
        $url = config('integration.ODS.bs_api_url') . '/reprocess_transactions';

        $response = Http::attach('file', $data, 'test.xml')->withHeaders([
            'X-API-KEY' => config('integration.ODS.bs_api_key'),
            'Accept' => 'application/json',
            'X-OUTPUT-VERSION' => '20190901'
        ])->post($url);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

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

            $jsonFileContent = json_decode($zip->getFromIndex($i));
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
}
