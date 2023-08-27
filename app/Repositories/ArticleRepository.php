<?php

namespace App\Repositories;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

class ArticleRepository
{
    public function saveArticle(array $sourceData, array $articleData, array $authors, array $categories): void
    {
        try {
            $source = Source::firstOrCreate($sourceData);

            $article = Article::create([
                'source_id' => $source->id,
                'title' => $articleData['title'],
                'description' => $articleData['description'],
                'article_url' => $articleData['article_url'],
                'image_url' => $articleData['image_url'],
                'published_at' => $articleData['published_at'],
            ]);

            $authorIds = [];
            foreach ($authors as $authorName) {
                $author = Author::firstOrCreate(['name' => $authorName]);
                $authorIds[] = $author->id;
            }
            $article->authors()->sync($authorIds);

            $categoryIds = [];
            foreach ($categories as $categoryName) {
                $category = Category::firstOrCreate(['name' => $categoryName]);
                $categoryIds[] = $category->id;
            }
            $article->categories()->sync($categoryIds);
        } catch (UniqueConstraintViolationException $e) {
            // handle duplicate entries if needed
        } catch (Throwable $e) {
            report($e);
            // handle other exceptions if needed
        }
    }
}
