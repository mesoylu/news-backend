<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class NewsFetchException extends Exception
{
    public function __construct($message = "An error occurred while fetching news.", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
