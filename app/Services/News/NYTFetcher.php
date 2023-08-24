<?php

namespace App\Services\News;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NYTFetcher
{
    const PAGE_SIZE = 10;
    protected string $apikey;
    protected string $serviceUrl;

    public function __construct()
    {
        $this->apikey = config('services.nyt.apikey');
        $this->serviceUrl = config('services.nyt.serviceUrl');
    }

    public function execute()
    {
        $this->fetchEverythingByDate(1692717292, 1692803692);
    }

    private function fetchEverythingByDate(int $startTimestamp, int $endTimestamp)
    {
        try {
            // limiting the request count in order to preserve free usage limit
            $endpoint = $this->serviceUrl . 'search/v2/articlesearch.json';

            $queryParameters = array(
                'api-key' => $this->apikey,
                'begin_date' => date('Ymd', $startTimestamp),
                'end_date' => date('Ymd', $endTimestamp),
                'page' => 0, // page numbers start from 0
            );


            do {
                $response = Http::withQueryParameters($queryParameters)
                    ->get($endpoint);
                if (!$response->ok()) {
                    throw new \Exception('Response for fetching news is not ok: ' . $response->body());
                }
                $result = $response->json();
                if (!array_key_exists('status', $result) || $result['status'] !== 'OK') {
                    throw new \Exception('Result for fetching news does not have ok status: ' . $response->body());
                }
                $metaData = $result['response']['meta'] ?? array();
                if (empty($metaData)) {
                    throw new \Exception('Meta data is absent on response: ' . $response->body());
                }
                // in case of hits is absent, PAGE_SIZE is used as fallback
                $totalResults = $metaData['hits'] ?? self::PAGE_SIZE;
                // since pages start from 0, floor method is used for getting totalPage value
                $totalPage = floor($totalResults / self::PAGE_SIZE);

                $articles = $result['response']['docs'] ?? array();
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
