<?php

namespace App\Services;

use App\Contracts\OpenWeatherMapClientInterface;
use App\DTO\OpenWeatherMapCurrentWeatherDTO;
use App\DTO\OpenWeatherMapLocationDTO;
use App\DTO\WeatherDTO;
use App\Exceptions\WeatherProviderException;
use App\Exceptions\WeatherServiceException;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;

/**
 * @phpstan-type WeatherData array{
 *     city: string,
 *     country: string,
 *     latitude: float,
 *     longitude: float,
 *     temperature: float|null,
 *     feelsLike: float|null,
 *     humidity: int|null,
 *     pressure: int|null,
 *     windSpeed: float|null,
 *     windDirection: int|null,
 *     conditionId: int|null,
 *     conditionMain: string|null,
 *     conditionDescription: string|null,
 *     conditionIcon: string|null,
 *     observedAt: string|null
 * }
 */
readonly class WeatherService
{
    private const string CACHE_KEY_PREFIX = 'weather:';

    public function __construct(
        private OpenWeatherMapClientInterface $weatherClient,
        private Repository $cache,
        #[Config('services.openweathermap.cache_ttl')]
        private int $cacheTtl,
    ) {}

    public function getCurrentWeather(string $city): WeatherDTO
    {
        /** @var WeatherData $weather */
        $weather = $this->cache->remember(
            key: $this->cacheKey($city),
            ttl: $this->cacheTtl,
            callback: fn (): array => $this->retrieveCurrentWeather($city),
        );

        return WeatherDTO::from($weather);
    }

    /**
     * @return WeatherData
     */
    private function retrieveCurrentWeather(string $city): array
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

    private function cacheKey(string $city): string
    {
        return Str::of($city)
            ->trim()
            ->lower()
            ->prepend(self::CACHE_KEY_PREFIX)
            ->toString();
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

    /**
     * @return WeatherData
     */
    private function mapWeather(OpenWeatherMapLocationDTO $location, OpenWeatherMapCurrentWeatherDTO $weather): array
    {
        $observedAt = $weather->observedAt !== null
            ? CarbonImmutable::createFromTimestampUTC($weather->observedAt)->toIso8601String()
            : null;

        return [
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
        ];
    }
}
