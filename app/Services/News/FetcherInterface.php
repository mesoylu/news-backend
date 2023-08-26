<?php

namespace App\Services\News;

interface FetcherInterface
{
    public function execute(int $startTimestamp, int $endTimestamp): void;
}
