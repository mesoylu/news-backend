<?php

namespace App\Services\News;

use App\Exceptions\NewsFetchException;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

class NYTFetcher extends AbstractFetcher
{
    const PAGE_SIZE = 10;

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
                // API has a rate limit of 5 request per minute
                sleep(12);
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
        try {
            foreach ($articles as $article) {
                try {
                    $authors = $this->parseAuthors($article['byline']['original']);
                    $categories = $this->parseCategories($article['keywords']);

                    $source = Source::firstOrCreate([
                        'name' => 'The New York Times',
                        'source_id' => 'nytimes',
                        'api_name' => 'nyt',
                    ]);

                    $createdArticle = Article::create([
                        'source_id' => $source->id,
                        'title' => $article['headline']['main'] ?? $article['abstract'],
                        'description' => $article['lead_paragraph'],
                        'article_url' => $article['web_url'],
                        'image_url' => $article['multimedia'][0]['url'] ?? '',
                        'published_at' => date('Y-m-d H:i:s', strtotime($article['pub_date'])),
                    ]);

                    $authorIds = [];
                    foreach ($authors as $authorName) {
                        $author = Author::firstOrCreate(['name' => $authorName]);
                        $authorIds[] = $author->id;
                    }
                    $createdArticle->authors()->sync($authorIds);

                    $categoryIds = [];
                    foreach ($categories as $categoryName) {
                        $category = Category::firstOrCreate(['name' => $categoryName]);
                        $categoryIds[] = $category->id;
                    }
                    $createdArticle->categories()->sync($categoryIds);
                } catch (UniqueConstraintViolationException $t) {
                    continue;
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
            if (str_starts_with($authors, 'By ')) {
                $authors = substr($authors, 3);
            }
            if (str_contains($authors, ' and ') || str_contains($authors, ', ')) {
                $authors = str_replace([' and ', ', '], '|', $authors);
            }
            return array_filter(explode('|', $authors));
        } catch (Throwable) {
            return array();
        }
    }

    private function parseCategories(?array $keywords): array
    {
        try {
            $categories = array();
            foreach ($keywords as $keyword) {
                if (array_key_exists('name', $keyword) && $keyword['name'] == 'subject') {
                    $categories[] = $keyword['value'] ?? null;
                }
            }
            return array_filter($categories);
        } catch (Throwable) {
            return array();
        }
    }
}
