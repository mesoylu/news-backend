<?php

namespace App\Services\News;

use App\Exceptions\NewsFetchException;
use Throwable;

class GuardianFetcher extends AbstractFetcher
{

    const PAGE_SIZE = 200;

    public function execute(int $startTimestamp, int $endTimestamp ): void
    {
        $this->fetchArticlesByDate($startTimestamp, $endTimestamp);
    }

    protected function fetchArticlesByDate(int $startTimestamp, int $endTimestamp): void
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
                $result = $this->makeRequest($endpoint, $queryParameters);
                if (!array_key_exists('response', $result)) {
                    throw new NewsFetchException('Result for fetching news does not have the key "response": ' . $result);
                }
                $result = $result['response'];
                if (!array_key_exists('status', $result) || $result['status'] !== 'ok') {
                    throw new NewsFetchException('Result for fetching news does not have ok status: ' . $result);
                }
                $totalPage = $result['pages'] ?? 1;
                $articles = $result['results'] ?? array();
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
