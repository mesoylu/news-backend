<?php

namespace App\Services\News;

use App\Exceptions\NewsFetchException;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class AbstractFetcher implements FetcherInterface
{
    protected string $apikey;
    protected string $serviceUrl;

    public function __construct(string $apikey, string $serviceUrl)
    {
        $this->apikey = $apikey;
        $this->serviceUrl = $serviceUrl;
    }

    public function execute(int $startTimestamp, int $endTimestamp ): void
    {
        $this->fetchArticlesByDate($startTimestamp, $endTimestamp);
    }

    abstract protected function fetchArticlesByDate(int $startTimestamp, int $endTimestamp): void;

    protected function makeRequest(string $endpoint, array $queryParameters, array $headers = [])
    {
        try {
            $response = Http::withHeaders($headers)
                ->withQueryParameters($queryParameters)
                ->get($endpoint);

            if (!$response->ok()) {
                throw new NewsFetchException('Response is not ok: ' . $response->body());
            }

            return $response->json();
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }
}
