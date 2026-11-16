<?php

namespace App\APIClients;

use App\Contracts\OpenWeatherMapClientInterface;
use App\DTO\OpenWeatherMapCurrentWeatherDTO;
use App\DTO\OpenWeatherMapLocationDTO;
use App\Exceptions\WeatherProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenWeatherMapClient implements OpenWeatherMapClientInterface
{
    /**
     * @var array{
     *     key: string|null,
     *     base_url: string,
     *     geocoding_path: string,
     *     weather_path: string,
     *     language: string,
     *     timeout: int,
     *     retry_times: int,
     *     retry_sleep: int
     * }
     */
    private readonly array $configuration;

    public function __construct()
    {
        /**
         * @var array{
         *     key: string|null,
         *     base_url: string,
         *     geocoding_path: string,
         *     weather_path: string,
         *     language: string,
         *     timeout: int,
         *     retry_times: int,
         *     retry_sleep: int
         * } $configuration
         */
        $configuration = config('services.openweathermap');

        $this->configuration = $configuration;
    }

    public function geocode(string $city): ?OpenWeatherMapLocationDTO
    {
        $payload = $this->get(
            path: $this->configuration['geocoding_path'],
            query: [
                'q' => $city,
                'limit' => 1,
            ],
        );

        if (! is_array($payload) || ! array_is_list($payload)) {
            throw WeatherProviderException::invalidResponse();
        }

        foreach ($payload as $location) {
            if (! is_array($location)) {
                throw WeatherProviderException::invalidResponse();
            }

            $name = $location['name'] ?? null;
            $latitude = $location['lat'] ?? null;
            $longitude = $location['lon'] ?? null;
            $country = $location['country'] ?? null;

            if (! is_string($name) || ! is_numeric($latitude) || ! is_numeric($longitude) || ! is_string($country)) {
                throw WeatherProviderException::invalidResponse();
            }

            return OpenWeatherMapLocationDTO::from([
                'name' => $name,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'country' => $country,
            ]);
        }

        return null;
    }

    public function currentWeather(float $latitude, float $longitude): OpenWeatherMapCurrentWeatherDTO
    {
        $weather = $this->get(
            path: $this->configuration['weather_path'],
            query: [
                'lat' => $latitude,
                'lon' => $longitude,
                'units' => 'metric',
                'lang' => $this->configuration['language'],
            ],
        );

        $main = $weather['main'] ?? [];
        $wind = $weather['wind'] ?? [];
        $condition = $weather['weather'][0] ?? [];

        return OpenWeatherMapCurrentWeatherDTO::from([
            'temperature' => isset($main['temp']) ? (float) $main['temp'] : null,
            'feelsLike' => isset($main['feels_like']) ? (float) $main['feels_like'] : null,
            'humidity' => $main['humidity'] ?? null,
            'pressure' => $main['pressure'] ?? null,
            'windSpeed' => isset($wind['speed']) ? (float) $wind['speed'] : null,
            'windDirection' => $wind['deg'] ?? null,
            'conditionId' => $condition['id'] ?? null,
            'conditionMain' => $condition['main'] ?? null,
            'conditionDescription' => $condition['description'] ?? null,
            'conditionIcon' => $condition['icon'] ?? null,
            'observedAt' => $weather['dt'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function get(string $path, array $query): mixed
    {
        $apiKey = $this->configuration['key'];

        if (empty($apiKey)) {
            throw WeatherProviderException::configuration();
        }

        try {
            $response = Http::baseUrl($this->configuration['base_url'])
                ->acceptJson()
                ->timeout($this->configuration['timeout'])
                ->retry(
                    times: $this->configuration['retry_times'],
                    sleepMilliseconds: $this->configuration['retry_sleep'],
                    throw: false,
                )
                ->get(
                    url: $path,
                    query: [...$query, 'appid' => $apiKey],
                );
        } catch (ConnectionException $exception) {
            throw new WeatherProviderException(previous: $exception);
        }

        if ($response->failed()) {
            throw new WeatherProviderException(response: $response);
        }

        return $response->json();
    }
}
