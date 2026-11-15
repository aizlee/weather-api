<?php

namespace App\DTO;

use App\DTO\Concerns\HasFromFactory;

final readonly class OpenWeatherMapCurrentWeatherDTO
{
    use HasFromFactory;

    private function __construct(
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
        public ?int $observedAt,
    ) {}
}
