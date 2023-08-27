<?php

namespace App\Services\News;

use App\Exceptions\NewsFetchException;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

class GuardianFetcher extends AbstractFetcher
{
    const PAGE_SIZE = 200;

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
        try {
            foreach ($articles as $article) {
                try {
                    $authors = $this->parseAuthors($article['tags']);
                    $categories = $this->parseCategories($article['tags']);

                    $source = Source::firstOrCreate([
                        'name' => 'The Guardian',
                        'source_id' => 'guardian',
                        'api_name' => 'guardian',
                    ]);

                    $createdArticle = Article::create([
                        'source_id' => $source->id,
                        'title' => $article['webTitle'],
                        'description' => $article['fields']['trailText'] ?? '',
                        'article_url' => $article['webUrl'],
                        'image_url' => $article['fields']['thumbnail'] ?? '',
                        'published_at' => date('Y-m-d H:i:s', strtotime($article['webPublicationDate'])),
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

    private function parseAuthors(?array $tags): array
    {
        try {
            $authors = array();
            foreach ($tags as $tag) {
                if (array_key_exists('type', $tag) && $tag['type'] == 'contributor') {
                    $authors[] = $tag['webTitle'] ?? null;
                }
            }
            return array_filter($authors);
        } catch (Throwable) {
            return array();
        }
    }

    private function parseCategories(?array $tags): array
    {
        try {
            $categories = array();
            foreach ($tags as $tag) {
                if (array_key_exists('type', $tag) && $tag['type'] == 'keyword') {
                    $categories[] = $tag['webTitle'] ?? null;
                }
            }
            return array_filter($categories);
        } catch (Throwable) {
            return array();
        }
    }
}
