<?php

namespace App\Services\News;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuardianFetcher
{

    const PAGE_SIZE = 200;
    protected string $apikey;
    protected string $serviceUrl;

    public function __construct()
    {
        $this->apikey = config('services.guardian.apikey');
        $this->serviceUrl = config('services.guardian.serviceUrl');
    }

    public function execute()
    {
        $this->fetchEverythingByDate(1692717292, 1692803692);
    }

    private function fetchEverythingByDate(int $startTimestamp, int $endTimestamp)
    {
        try {
            // limiting the request count in order to preserve free usage limit
            $endpoint = $this->serviceUrl . 'search';

            $queryParameters = array(
                'api-key' => $this->apikey,
                'from-date' => date('Y-m-d', $startTimestamp),
                'to-date' => date('Y-m-d', $endTimestamp),
                'page' => 1,
                'page-size' => self::PAGE_SIZE,
                'show-fields' => 'trailText,thumbnail',
                'show-tags' => 'keyword,contributor',
            );


            do {
                $response = Http::withQueryParameters($queryParameters)
                    ->get($endpoint);
                if (!$response->ok()) {
                    throw new \Exception('Response for fetching news is not ok: ' . $response->body());
                }
                $result = $response->json();
                if (!array_key_exists('response', $result)) {
                    throw new \Exception('Result for fetching news does not have the key "response": ' . $response->body());
                }
                $result = $result['response'];
                if (!array_key_exists('status', $result) || $result['status'] !== 'ok') {
                    throw new \Exception('Result for fetching news does not have ok status: ' . $response->body());
                }
                $totalPage = $result['pages'] ?? 1;
                $articles = $result['results'] ?? array();
                $this->saveToDB($articles);
                $queryParameters['page']++;
                die;
            } while ($queryParameters['page'] <= $totalPage);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function saveToDB(array $articles)
    {
        Log::info($articles);
        // do something
    }
}
