<?php

namespace App\DTO;

use App\DTO\Concerns\HasFromFactory;

final readonly class WeatherDTO
{
    use HasFromFactory;

    private function __construct(
        public string $city,
        public string $country,
        public float $latitude,
        public float $longitude,
        public ?float $temperature,
        public ?float $feelsLike,
        public ?int $humidity,
        public ?int $pressure,
        public ?float $windSpeed,
        public ?int $windDirection,
        public ?int $conditionId,
        public ?string $conditionMain,
        public ?string $conditionDescription,
        public ?string $conditionIcon,
        public ?string $observedAt,
    ) {}
}
