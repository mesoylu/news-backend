<?php

namespace App\Services\News;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use function Laravel\Prompts\warning;

class NewsapiFetcher
{
    const SOURCE_PER_REQUEST = 20;
    const PAGE_SIZE = 100;
    protected string $apikey;
    protected string $serviceUrl;
    protected array $sources = array();

    public function __construct()
    {
        $this->apikey = config('services.newsapi.apikey');
        $this->serviceUrl = config('services.newsapi.serviceUrl');
    }

    public function execute()
    {
        $this->fetchEverythingByDate(1692717292, 1692803692);
    }

    /**
     * Fetches the news from NewsAPI from the selected sources
     * In order to simplify the service, language is limited to English
     */
    private function fetchEverythingByDate(int $startTimestamp, int $endTimestamp)
    {
        try {
            // limiting the request count in order to preserve free usage limit
            $requestCount = 0;
            $this->fetchSources();
            $endpoint = $this->serviceUrl . 'everything';

            $queryParameters = array(
                'apiKey' => $this->apikey,
                'from' => date('Y-m-d\TH:i:s', $startTimestamp),
                'to' => date('Y-m-d\TH:i:s', $endTimestamp),
                'language' => 'en',
                'pageSize' => self::PAGE_SIZE, // default value
                'page' => 1,
                'sources' => '', // up to 20 sources per request
            );

            $header = array(
                'x-api-key' => $this->apikey
            );

            $sourceCount = count($this->sources);
            for ($i = 0; $i < $sourceCount; $i++) {
                $queryParameters['sources'] = implode(',',
                    array_keys(array_slice(
                        $this->sources,
                        $i * self::SOURCE_PER_REQUEST,
                        self::SOURCE_PER_REQUEST
                    )));
                do {
                    $response = Http::withHeaders($header)
                        ->withQueryParameters($queryParameters)
                        ->get($endpoint);
                    if (!$response->ok()) {
                        throw new \Exception('Response for fetching news is not ok: ' . $response->body());
                    }
                    $result = $response->json();
                    if (!array_key_exists('status', $result) || $result['status'] !== 'ok') {
                        throw new \Exception('Result for fetching news does not have ok status: ' . $response->body());
                    }
                    // in case of totalResults is absent, PAGE_SIZE is used as fallback
                    $totalResults = $result['totalResults'] ?? self::PAGE_SIZE;
                    // since pages start from 1, ceil method is used for getting totalPage value
                    $totalPage = ceil($totalResults / self::PAGE_SIZE);
                    $articles = $result['articles'] ?? array();
                    $this->saveToDB($articles);
                    $queryParameters['page']++;
                } while ($queryParameters['page'] <= $totalPage);
                $queryParameters['page'] = 1;
            }
        } catch (\Throwable) {

        }
    }

    /**
     * Fetches the sources available on NewsAPI
     * In order to simplify the service, language is limited to English
     */
    private function fetchSources(): void
    {
        try {
            $endpoint = $this->serviceUrl . 'sources';

            $queryParameters = array(
                'language' => 'en'
            );

            $header = array(
                'x-api-key' => $this->apikey
            );

            $response = Http::withHeaders($header)
                ->withQueryParameters($queryParameters)
                ->get($endpoint);
            if (!$response->ok()) {
                throw new \Exception('Response for fetching sources is not ok: ' . $response->body());
            }
            $result = $response->json();
            if (!array_key_exists('status', $result) || $result['status'] !== 'ok') {
                throw new \Exception('Result for fetching sources does not have ok status: ' . $response->body());
            }
            foreach ($result['sources'] as $source) {
                $this->sources[$source['id']] = array(
                    'name' => $source['name'],
                    'category' => $source['category']
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function saveToDB(array $articles)
    {
        // do something
    }
}
