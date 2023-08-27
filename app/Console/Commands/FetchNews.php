<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\News\FetcherInterface;

class FetchNews extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Fetch news data from different sources';

    public function handle()
    {
        // iterate through each configured fetcher
        foreach (config('news-services') as $sourceConfig) {
            $fetcherClassName = $sourceConfig['className'];

            // ensure the fetcher class implements the FetcherInterface
            if (!class_exists($fetcherClassName) || !in_array(FetcherInterface::class, class_implements($fetcherClassName))) {
                $this->error('Invalid fetcher class: ' . $fetcherClassName);
                continue;
            }

            // create the timestamps for the date interval
            // note: newsapi free version serves the news with 24 hours delay
            $now = now();
            $startTimestamp = $now->copy()->subDays(2)->startOfDay()->timestamp;
            $endTimestamp = $now->copy()->subDays(1)->endOfDay()->timestamp;

            // instantiate the fetcher class and execute
            $fetcher = new $fetcherClassName($sourceConfig['apikey'], $sourceConfig['serviceUrl']);
            $fetcher->execute($startTimestamp, $endTimestamp); // Set appropriate start and end timestamps
        }

        $this->info('News fetching completed.');
    }
}
