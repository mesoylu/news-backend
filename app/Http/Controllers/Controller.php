<?php

namespace App\Http\Controllers;

use App\Services\News\GuardianFetcher;
use App\Services\News\NewsapiFetcher;
use App\Services\News\NYTFetcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function guardian()
    {
        $api = new GuardianFetcher();
        $api->execute();
    }

    public function nyt()
    {
        $api = new NYTFetcher();
        $api->execute();
    }

    public function newsapi()
    {
        $api = new NewsapiFetcher();
        $api->execute();
    }
}
