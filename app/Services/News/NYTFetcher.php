<?php

namespace App\Services\News;

use App\Exceptions\NewsFetchException;
use Throwable;

class NYTFetcher extends AbstractFetcher
{
    const PAGE_SIZE = 10;

    public function execute(int $startTimestamp, int $endTimestamp): void
    {
        $this->fetchArticlesByDate($startTimestamp, $endTimestamp);
    }

    protected function fetchArticlesByDate(int $startTimestamp, int $endTimestamp): void
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
                $result = $this->makeRequest($endpoint, $queryParameters);
                if (!array_key_exists('status', $result) || $result['status'] !== 'OK') {
                    throw new NewsFetchException('Result for fetching news does not have ok status: ' . $result);
                }
                $metaData = $result['response']['meta'] ?? array();
                if (empty($metaData)) {
                    throw new NewsFetchException('Meta data is absent on response: ' . $result);
                }
                // in case of hits is absent, PAGE_SIZE is used as fallback
                $totalResults = $metaData['hits'] ?? self::PAGE_SIZE;
                // since pages start from 0, floor method is used for getting totalPage value
                $totalPage = floor($totalResults / self::PAGE_SIZE);

                $articles = $result['response']['docs'] ?? array();
                $this->saveToDB($articles);
                $queryParameters['page']++;
            } while ($queryParameters['page'] <= $totalPage);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function saveToDB(array $articles)
    {
        // do something
    }
}
