<?php

namespace App\Services\News;

use App\Exceptions\NewsFetchException;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Http;
use Throwable;

class NewsapiFetcher extends AbstractFetcher
{
    const SOURCE_PER_REQUEST = 20;
    const PAGE_SIZE = 100;
    protected array $sources = array();

    /**
     * Fetches the news from NewsAPI from the selected sources
     * In order to simplify the service, language is limited to English
     */
    protected function fetchArticlesByDate(int $startTimestamp, int $endTimestamp): void
    {
        try {
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
                    $result = $this->makeRequest($endpoint, $queryParameters, $header);
                    if (!array_key_exists('status', $result) || $result['status'] !== 'ok') {
                        throw new NewsFetchException('Result for fetching news does not have ok status: ' . $result);
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
        } catch (Throwable $e) {
            report($e);
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
                throw new NewsFetchException('Response for fetching sources is not ok: ' . $response->body());
            }
            $result = $response->json();
            if (!array_key_exists('status', $result) || $result['status'] !== 'ok') {
                throw new NewsFetchException('Result for fetching sources does not have ok status: ' . $response->body());
            }
            foreach ($result['sources'] as $source) {
                $this->sources[$source['id']] = array(
                    'name' => $source['name'],
                    'category' => $source['category']
                );
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function saveToDB(array $articles)
    {
        try {
            foreach ($articles as $article) {
                try {
                    $sourceId = $article['source']['id'];
                    if (!array_key_exists($sourceId, $this->sources)) {
                        throw new NewsFetchException('Source id not found in sources array. ' . $sourceId);
                    }

                    $authors = $this->parseAuthors($article['author']);
                    $categories = $this->parseCategories($sourceId);

                    $sourceData = array(
                        'name' => $this->sources[$sourceId]['name'],
                        'source_id' => $sourceId,
                        'api_name' => 'newsapi',
                    );

                    $articleData = array(
                        'title' => $article['title'],
                        'description' => $article['description'],
                        'article_url' => $article['url'],
                        'image_url' => $article['urlToImage'],
                        'published_at' => date('Y-m-d H:i:s', strtotime($article['publishedAt'])),
                    );

                    $this->articleRepository->saveArticle($sourceData, $articleData, $authors, $categories);
                } catch (Throwable $t) {
                    report($t);
                    continue;
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function parseAuthors(?string $authors): array
    {
        try {
            if (str_contains($authors, ' and ') || str_contains($authors, ', ')) {
                $authors = str_replace([' and ', ', '], '|', $authors);
            }
            return array_filter(explode('|', $authors));
        } catch (Throwable) {
            return array();
        }
    }

    private function parseCategories(string $sourceId): array
    {
        try {
            $categories = array(
                $this->sources[$sourceId]['category']
            );

            return array_filter($categories);
        } catch (Throwable) {
            return array();
        }
    }
}
