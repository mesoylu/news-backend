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
        return 'guardian';
//        $api = new GuardianFetcher(1, 2);
//        $api->execute();
    }

    public function nyt()
    {
        return 'nyt';
//        $api = new NYTFetcher(1, 2);
//        $api->execute();
    }

    public function newsapi()
    {
        return 'newsapi';
//        $api = new NewsapiFetcher(1, 2);
//        $api->execute();
    }
}
