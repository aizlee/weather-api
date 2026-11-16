<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    public function test_returns_current_weather_for_a_trimmed_city_name(): void
    {
        $this->fakeSuccessfulWeatherResponses();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => ' Omsk ',
            ],
        );

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'city' => 'Omsk',
                    'country' => 'RU',
                    'coordinates' => [
                        'latitude' => 54.99,
                        'longitude' => 73.37,
                    ],
                    'temperature' => -5.2,
                    'feels_like' => -10.1,
                    'humidity' => 75,
                    'pressure' => 1012,
                    'wind' => [
                        'speed' => 4.5,
                        'direction' => null,
                    ],
                    'conditions' => [
                        'id' => 800,
                        'main' => 'Clear',
                        'description' => 'ясно',
                        'icon' => '01d',
                    ],
                    'observed_at' => '2023-11-14T22:13:20+00:00',
                ],
            ]);

        Http::assertSent(
            fn (Request $request): bool => $request->url()
                === 'https://fake.api.openweathermap.org/geo/1.0/direct?q=Omsk&limit=1&appid=fake-key',
        );

        Http::assertSent(
            fn (Request $request): bool => $request->url()
                === 'https://fake.api.openweathermap.org/data/2.5/weather?lat=54.99&lon=73.37&units=metric&lang=ru&appid=fake-key',
        );
    }

    public function test_returns_current_weather_with_null_optional_fields(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::response($this->successfulGeocodingPayload()),
            'https://fake.api.openweathermap.org/data/2.5/weather*' => Http::response([]),
        ]);
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'city' => 'Omsk',
                    'country' => 'RU',
                    'coordinates' => [
                        'latitude' => 54.99,
                        'longitude' => 73.37,
                    ],
                    'temperature' => null,
                    'feels_like' => null,
                    'humidity' => null,
                    'pressure' => null,
                    'wind' => [
                        'speed' => null,
                        'direction' => null,
                    ],
                    'conditions' => [
                        'id' => null,
                        'main' => null,
                        'description' => null,
                        'icon' => null,
                    ],
                    'observed_at' => null,
                ],
            ]);
    }

    public function test_returns_not_found_when_geocoding_returns_no_locations(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::response([]),
            'https://fake.api.openweathermap.org/data/2.5/weather*' => Http::response([]),
        ]);
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Unknown',
            ],
        );

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Weather for the requested city was not found.',
            ]);

        Http::assertSentCount(1);
    }

    public function test_returns_not_found_when_geocoding_provider_returns_not_found(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::response(
                status: Response::HTTP_NOT_FOUND,
            ),
        ]);
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Unknown',
            ],
        );

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Weather for the requested city was not found.',
            ]);
    }

    public function test_returns_unavailable_when_geocoding_provider_returns_invalid_payload(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::response([
                'unexpected' => true,
            ]),
        ]);
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $response
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Weather service is currently unavailable.',
            ]);
    }

    public function test_returns_unavailable_when_provider_rejects_the_request(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::response($this->successfulGeocodingPayload()),
            'https://fake.api.openweathermap.org/data/2.5/weather*' => Http::response(
                body: [],
                status: Response::HTTP_TOO_MANY_REQUESTS,
            ),
        ]);
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $response
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Weather service is currently unavailable.',
            ]);
    }

    public function test_returns_unavailable_when_current_weather_provider_returns_not_found(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::response($this->successfulGeocodingPayload()),
            'https://fake.api.openweathermap.org/data/2.5/weather*' => Http::response(
                status: Response::HTTP_NOT_FOUND,
            ),
        ]);
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $response
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Weather service is currently unavailable.',
            ]);
    }

    public function test_returns_unavailable_when_provider_connection_fails(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::failedConnection(),
        ]);
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $response
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Weather service is currently unavailable.',
            ]);
    }

    public function test_returns_unavailable_when_api_key_is_missing(): void
    {
        config(['services.openweathermap.key' => null]);

        Http::fake();
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $response
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Weather service is currently unavailable.',
            ]);
        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    #[DataProvider('invalidCityRequests')]
    public function test_returns_unprocessable_for_invalid_city(array $parameters): void
    {
        Http::fake();
        Http::preventStrayRequests();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: $parameters,
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('city');
        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidCityRequests(): array
    {
        return [
            'missing city' => [[]],
            'blank city' => [['city' => '   ']],
            'non-string city' => [['city' => ['Omsk']]],
            'overlong city' => [['city' => str_repeat('a', 256)]],
        ];
    }

    public function test_anonymous_weather_lookup_does_not_write_to_the_database(): void
    {
        $this->fakeSuccessfulWeatherResponses();
        DB::enableQueryLog();

        $response = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $response->assertOk();
        $mutatingQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => preg_match('/^\s*(insert|update|delete|replace|alter|create|drop)\b/i', $query['query']) === 1);

        $this->assertCount(0, $mutatingQueries);
        DB::disableQueryLog();
    }

    private function fakeSuccessfulWeatherResponses(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::response($this->successfulGeocodingPayload()),
            'https://fake.api.openweathermap.org/data/2.5/weather*' => Http::response($this->successfulCurrentWeatherPayload()),
        ]);
        Http::preventStrayRequests();
    }

    /**
     * @return list<array{name: string, lat: float, lon: float, country: string}>
     */
    private function successfulGeocodingPayload(): array
    {
        return [
            [
                'name' => 'Omsk',
                'lat' => 54.99,
                'lon' => 73.37,
                'country' => 'RU',
            ],
        ];
    }

    /**
     * @return array{
     *     main: array{temp: float, feels_like: float, humidity: int, pressure: int},
     *     wind: array{speed: float},
     *     weather: list<array{id: int, main: string, description: string, icon: string}>,
     *     dt: int
     * }
     */
    private function successfulCurrentWeatherPayload(): array
    {
        return [
            'main' => [
                'temp' => -5.2,
                'feels_like' => -10.1,
                'humidity' => 75,
                'pressure' => 1012,
            ],
            'wind' => [
                'speed' => 4.5,
            ],
            'weather' => [[
                'id' => 800,
                'main' => 'Clear',
                'description' => 'ясно',
                'icon' => '01d',
            ]],
            'dt' => 1700000000,
        ];
    }
}
