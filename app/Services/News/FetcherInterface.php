<?php

namespace App\Services\News;

interface FetcherInterface
{
    public function execute(): void;
}
