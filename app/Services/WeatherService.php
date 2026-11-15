<?php

namespace App\Services;

use App\Contracts\OpenWeatherMapClientInterface;
use App\DTO\OpenWeatherMapCurrentWeatherDTO;
use App\DTO\OpenWeatherMapLocationDTO;
use App\DTO\WeatherDTO;
use App\Exceptions\WeatherProviderException;
use App\Exceptions\WeatherServiceException;
use Carbon\CarbonImmutable;

readonly class WeatherService
{
    public function __construct(
        private OpenWeatherMapClientInterface $weatherClient,
    ) {}

    public function getCurrentWeather(string $city): WeatherDTO
    {
        $location = $this->resolveLocation($city);

        try {
            $weather = $this
                ->weatherClient
                ->currentWeather(
                    latitude: $location->latitude,
                    longitude: $location->longitude,
                );
        } catch (WeatherProviderException $exception) {
            throw WeatherServiceException::unavailable($exception);
        }

        return $this->mapWeather(
            location: $location,
            weather: $weather,
        );
    }

    private function resolveLocation(string $city): OpenWeatherMapLocationDTO
    {
        try {
            $location = $this->weatherClient->geocode($city);
        } catch (WeatherProviderException $exception) {
            if ($exception->response?->notFound()) {
                throw WeatherServiceException::notFound($exception);
            }

            throw WeatherServiceException::unavailable($exception);
        }

        if ($location === null) {
            throw WeatherServiceException::notFound();
        }

        return $location;
    }

    private function mapWeather(OpenWeatherMapLocationDTO $location, OpenWeatherMapCurrentWeatherDTO $weather): WeatherDTO
    {
        $observedAt = $weather->observedAt !== null
            ? CarbonImmutable::createFromTimestampUTC($weather->observedAt)->toIso8601String()
            : null;

        return WeatherDTO::from([
            'city' => $location->name,
            'country' => $location->country,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'temperature' => $weather->temperature,
            'feelsLike' => $weather->feelsLike,
            'humidity' => $weather->humidity,
            'pressure' => $weather->pressure,
            'windSpeed' => $weather->windSpeed,
            'windDirection' => $weather->windDirection,
            'conditionId' => $weather->conditionId,
            'conditionMain' => $weather->conditionMain,
            'conditionDescription' => $weather->conditionDescription,
            'conditionIcon' => $weather->conditionIcon,
            'observedAt' => $observedAt,
        ]);
    }
}
