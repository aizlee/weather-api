# Tasks

## 1. Configuration

- [x] 1.1 Add `OPENWEATHERMAP_CACHE_TTL=600` to `.env.example` and `.env.testing`, expose it as the integer `services.openweathermap.cache_ttl` defaulting to 600 seconds, keep the existing OpenWeatherMap configuration shape accurate, and verify the resolved value with `php artisan config:show services.openweathermap`.

## 2. Service caching and behavioral coverage

- [x] 2.1 Update `WeatherService` to inject Laravel's cache repository, derive a canonical key from the Unicode-normalized city, cache an explicitly annotated scalar payload through `remember`, and reconstruct `WeatherDTO` through `WeatherDTO::from`; verify the changed service passes `vendor/bin/phpstan analyse app/Services/WeatherService.php --memory-limit=512M`.
- [x] 2.2 Add a feature test proving the first lookup returns the existing exact JSON contract and a repeated equivalent city lookup within the TTL makes no additional provider requests; rerun that test with `php artisan test --compact tests/Feature/WeatherTest.php --filter=test_reuses_cached_weather_for_equivalent_city_queries`.
- [x] 2.3 Add focused tests proving letter-case/whitespace normalization shares an entry while different cities remain isolated; rerun each changed test with `php artisan test --compact tests/Feature/WeatherTest.php --filter=cache`.
- [x] 2.4 Add focused tests proving an expired entry triggers a fresh provider lookup and not-found, unavailable, connection, missing-key, and validation failures are never cached; verify with `php artisan test --compact tests/Feature/WeatherTest.php --filter=cache` and provider request-count assertions.
- [x] 2.5 Update the read-only persistence test to permit expiring writes to Laravel's `cache` table while still rejecting weather domain persistence, clear weather cache state between affected tests, and verify `php artisan test --compact tests/Feature/WeatherTest.php` passes without changing the route, controller, Request, Resource, API client, response schema, or frontend.
- [x] 2.6 Simplify cache identity to depend only on the normalized city, remove unused language and units dependencies and their speculative test coverage, and rerun the cache-focused tests, Pint, PHPStan, and strict OpenSpec validation.
- [x] 2.7 Replace the hashed versioned cache key with the readable `weather:<normalized-city>` key, update the change artifacts, and rerun cache-focused tests, Pint, PHPStan, and strict OpenSpec validation.

## 3. Integration verification

- [x] 3.1 Run `php artisan test --compact tests/Feature/WeatherTest.php tests/Feature/Web/WeatherPageTest.php` and verify cached and uncached API responses remain compatible with the public Inertia page.
- [x] 3.2 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse app/Services/WeatherService.php tests/Feature/WeatherTest.php --memory-limit=512M`, resolving all project errors while preserving named multiline arguments.
- [x] 3.3 Run `composer ci:check` and `/home/avardugin/.nvm/versions/node/v22.16.0/bin/openspec validate cache-weather-responses --strict --json --no-interactive`; verify the full PHP, TypeScript, lint, test, and OpenSpec gates pass without adding dependencies or migrations.

## Workflow follow-up

- Review the configured TTL against production freshness and provider-quota requirements.
- Archive the OpenSpec change after implementation and review are complete.
