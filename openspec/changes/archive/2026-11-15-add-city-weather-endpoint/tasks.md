## 1. API and configuration setup

- [x] 1.1 Add `OPENWEATHERMAP_API_KEY`, provider base URL, language, and request timeout settings to `config/services.php` and `.env.example` without exposing a credential.
- [x] 1.2 Add `routes/api.php` and register it in `bootstrap/app.php` with Laravel's API routing and `/api` prefix.
- [x] 1.3 Generate the weather request and controller classes with Artisan, add `App\Contracts\OpenWeatherMapClientInterface`, `App\Services\WeatherService`, and `App\APIClients\OpenWeatherMapClient` in their designated layers, bind the interface in `AppServiceProvider`, and wire a public `GET /api/weather` route.

## 2. OpenWeatherMap integration

- [x] 2.1 Define the `OpenWeatherMapClientInterface` contract for geocoding and current-weather retrieval, then implement it in `App\APIClients\OpenWeatherMapClient` using Laravel's HTTP client with `q`, `limit=1`, `appid`, `lat`, `lon`, `units=metric`, and `lang=ru`.
- [x] 2.2 Implement `App\Services\WeatherService` with a constructor dependency on `OpenWeatherMapClientInterface`; use the interface to orchestrate geocoding, weather retrieval, normalization, response mapping, and provider error translation.
- [x] 2.3 Add input normalization and validation for the required `city` query parameter, keeping the controller thin and delegating the weather operation to `WeatherService`.
- [x] 2.4 Map the OpenWeatherMap response in the Services layer into the stable weather JSON contract, including city/country, coordinates, temperature, feels-like temperature, humidity, pressure, wind, conditions, and observation time.
- [x] 2.5 Implement bounded timeout/retry behavior and map missing configuration, provider authentication/rate-limit failures, not-found cities, and transport failures to the specified JSON status responses without leaking credentials or raw upstream payloads.

## 3. Feature tests

- [x] 3.1 Add PHPUnit feature tests using `Http::fake()` for a successful city lookup, normalized query parameters, response mapping, and optional provider fields.
- [x] 3.2 Add feature tests for missing, blank, non-string, and overlong city values and assert that invalid requests do not call OpenWeatherMap.
- [x] 3.3 Add feature tests for provider 404, authentication/rate-limit errors, transport failures, missing API key, anonymous access, and the absence of persistence.

## 4. Verification

- [x] 4.1 Run `php artisan route:list --path=api/weather` and verify the endpoint is registered without web authentication middleware.
- [x] 4.2 Run `php artisan test --compact` for the weather feature tests, then run `vendor/bin/pint --dirty --format agent` and `phpstan analyse`.
- [x] 4.3 Run `openspec validate --all --json --no-interactive` and document the required `OPENWEATHERMAP_API_KEY` and local request example for deployment verification.
