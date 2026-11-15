<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class WeatherProviderException extends RuntimeException
{
    public function __construct(
        public readonly ?Response $response = null,
        ?string $message = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message ?? __('weather.provider.request_failed'),
            previous: $previous,
        );
    }

    public static function configuration(): self
    {
        return new self(
            message: __('weather.provider.not_configured'),
        );
    }

    public static function invalidResponse(): self
    {
        return new self(
            message: __('weather.provider.invalid_response'),
        );
    }
}
