<?php

namespace App\DTO;

use App\DTO\Concerns\HasFromFactory;

final readonly class OpenWeatherMapLocationDTO
{
    use HasFromFactory;

    private function __construct(
        public string $name,
        public float $latitude,
        public float $longitude,
        public string $country,
    ) {}
}
