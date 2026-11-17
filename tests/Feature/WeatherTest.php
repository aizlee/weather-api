<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
    }

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
            ->assertExactJson($this->successfulWeatherResponse());

        Http::assertSent(
            fn (Request $request): bool => $request->url()
                === 'https://fake.api.openweathermap.org/geo/1.0/direct?q=Omsk&limit=1&appid=fake-key',
        );

        Http::assertSent(
            fn (Request $request): bool => $request->url()
                === 'https://fake.api.openweathermap.org/data/2.5/weather?lat=54.99&lon=73.37&units=metric&lang=ru&appid=fake-key',
        );
    }

    public function test_reuses_cached_weather_for_equivalent_city_queries(): void
    {
        $this->fakeSuccessfulWeatherResponses();

        $firstResponse = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );
        $secondResponse = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => ' oMsK ',
            ],
        );

        $firstResponse
            ->assertOk()
            ->assertExactJson($this->successfulWeatherResponse());
        $secondResponse
            ->assertOk()
            ->assertExactJson($this->successfulWeatherResponse());
        Http::assertSentCount(2);
    }

    public function test_cache_keeps_different_cities_isolated(): void
    {
        $this->fakeSuccessfulWeatherResponses();

        $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        )->assertOk();
        $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Tomsk',
            ],
        )->assertOk();

        Http::assertSentCount(4);
        Http::assertSent(
            fn (Request $request): bool => $request->url()
                === 'https://fake.api.openweathermap.org/geo/1.0/direct?q=Omsk&limit=1&appid=fake-key',
        );
        Http::assertSent(
            fn (Request $request): bool => $request->url()
                === 'https://fake.api.openweathermap.org/geo/1.0/direct?q=Tomsk&limit=1&appid=fake-key',
        );
    }

    public function test_cache_refreshes_weather_after_expiry(): void
    {
        $this->fakeSuccessfulWeatherResponses();

        $firstResponse = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $this->travel(601)->seconds();

        $secondResponse = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $firstResponse
            ->assertOk()
            ->assertExactJson($this->successfulWeatherResponse());
        $secondResponse
            ->assertOk()
            ->assertExactJson($this->successfulWeatherResponse());
        Http::assertSentCount(4);
    }

    public function test_cache_does_not_store_a_failed_lookup(): void
    {
        Http::fake([
            'https://fake.api.openweathermap.org/geo/1.0/direct*' => Http::sequence()
                ->push([])
                ->push($this->successfulGeocodingPayload()),
            'https://fake.api.openweathermap.org/data/2.5/weather*' => Http::response($this->successfulCurrentWeatherPayload()),
        ]);
        Http::preventStrayRequests();

        $firstResponse = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );
        $secondResponse = $this->json(
            method: 'GET',
            uri: '/api/weather',
            data: [
                'city' => 'Omsk',
            ],
        );

        $firstResponse
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Weather for the requested city was not found.',
            ]);
        $secondResponse
            ->assertOk()
            ->assertExactJson($this->successfulWeatherResponse());
        Http::assertSentCount(3);
        $this->assertDatabaseCount('cache', 1);
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

    public function test_cache_does_not_store_not_found_when_geocoding_returns_no_locations(): void
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
        $this->assertDatabaseCount('cache', 0);
    }

    public function test_cache_does_not_store_geocoding_provider_not_found(): void
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
        $this->assertDatabaseCount('cache', 0);
    }

    public function test_cache_does_not_store_invalid_geocoding_payload_failure(): void
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
        $this->assertDatabaseCount('cache', 0);
    }

    public function test_cache_does_not_store_provider_rejection_failure(): void
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
        $this->assertDatabaseCount('cache', 0);
    }

    public function test_cache_does_not_store_current_weather_provider_failure(): void
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
        $this->assertDatabaseCount('cache', 0);
    }

    public function test_cache_does_not_store_provider_connection_failure(): void
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
        $this->assertDatabaseCount('cache', 0);
    }

    public function test_cache_does_not_store_missing_api_key_failure(): void
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
        $this->assertDatabaseCount('cache', 0);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    #[DataProvider('invalidCityRequests')]
    public function test_validation_failure_bypasses_weather_cache(array $parameters): void
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
        $this->assertDatabaseCount('cache', 0);
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

    public function test_anonymous_weather_lookup_only_writes_to_the_cache_table(): void
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
            ->filter(fn (array $query): bool => preg_match('/^\s*(insert|update|delete|replace|alter|create|drop)\b/i', $query['query']) === 1)
            ->reject(fn (array $query): bool => str_contains($query['query'], '"cache"'));

        $this->assertCount(0, $mutatingQueries);
        $this->assertDatabaseCount('cache', 1);
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

    /**
     * @return array<string, mixed>
     */
    private function successfulWeatherResponse(): array
    {
        return [
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
        ];
    }
}
