<?php

namespace App\Http\Resources;

use App\DTO\WeatherDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeatherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WeatherDTO $weather */
        $weather = $this->resource;

        return [
            'city' => $weather->city,
            'country' => $weather->country,
            'coordinates' => [
                'latitude' => $weather->latitude,
                'longitude' => $weather->longitude,
            ],
            'temperature' => $weather->temperature,
            'feels_like' => $weather->feelsLike,
            'humidity' => $weather->humidity,
            'pressure' => $weather->pressure,
            'wind' => [
                'speed' => $weather->windSpeed,
                'direction' => $weather->windDirection,
            ],
            'conditions' => [
                'id' => $weather->conditionId,
                'main' => $weather->conditionMain,
                'description' => $weather->conditionDescription,
                'icon' => $weather->conditionIcon,
            ],
            'observed_at' => $weather->observedAt,
        ];
    }
}
