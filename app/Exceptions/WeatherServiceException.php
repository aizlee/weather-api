<?php

namespace App\Exceptions;

use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

class WeatherServiceException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }

    public static function notFound(?Throwable $previous = null): self
    {
        return new self(
            statusCode: Response::HTTP_NOT_FOUND,
            message: __('weather.not_found'),
            previous: $previous,
        );
    }

    public static function unavailable(?Throwable $previous = null): self
    {
        return new self(
            statusCode: Response::HTTP_SERVICE_UNAVAILABLE,
            message: __('weather.unavailable'),
            previous: $previous,
        );
    }
}
