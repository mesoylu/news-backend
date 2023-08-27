<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     *
     * @param Throwable $exception
     * @return void
     * @throws Exception|Throwable
     */
    public function report(Throwable $exception)
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipAddress = '127.0.0.1';
        }
        Log::error(
            sprintf(
                "%s: %s in %s:%d %s",
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $ipAddress
            )
        );
    }
}
