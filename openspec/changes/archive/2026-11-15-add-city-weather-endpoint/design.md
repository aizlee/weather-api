## Context

The application is a Laravel 13 Inertia/React starter with web routes only; it does not currently expose an API or integrate with an external weather provider. The change introduces a read-only public endpoint that translates a city name into coordinates through OpenWeatherMap's Geocoding API and then requests current weather by coordinates. The provider requires an API key, supports direct geocoding by `q`, and supports metric units through `units=metric` and localized descriptions through `lang`.

## Goals / Non-Goals

**Goals:**

- Expose `GET /api/weather?city={city}` through Laravel's API routing.
- Keep the OpenWeatherMap API key and provider URL in environment-backed configuration.
- Isolate provider-specific payloads behind an application service and return a stable JSON contract.
- Validate input, apply bounded HTTP timeouts/retries, and translate provider failures into predictable API responses.
- Test the endpoint with Laravel HTTP fakes and without network access.

**Non-Goals:**

- Persisting weather data, adding scheduled refreshes, or introducing a weather database table.
- Adding forecasts, city IDs, persistence, or a frontend weather page.
- Adding authentication, rate limiting, or a new external package for this read-only endpoint.

## Decisions

1. **Use a dedicated API route file.** Add `routes/api.php` and register it through `bootstrap/app.php` so the endpoint receives API middleware and the `/api` prefix without coupling JSON behavior to the existing Inertia web routes. The alternative, adding an `/api` route to `routes/web.php`, would inherit browser middleware and blur the boundary between page and API traffic.

2. **Separate business logic from provider access.** Add `App\Services\WeatherService` directly under `app/Services` as the application service that validates the normalized input assumptions, orchestrates geocoding and current-weather retrieval, maps provider data into the application contract, and translates provider failures. Define `App\Contracts\OpenWeatherMapClientInterface` under `app/Contracts`, implement it with `App\APIClients\OpenWeatherMapClient` under `app/APIClients`, and bind the interface to the implementation in `AppServiceProvider`. The controller should only receive the validated request, delegate to `WeatherService`, and return the HTTP response.

3. **Resolve city names before requesting weather.** Use `OpenWeatherMapClient` to call OpenWeatherMap's Direct Geocoding API with the normalized city name and `limit=1`, then call the Current Weather API with the returned `lat` and `lon`. This follows the provider's recommendation to use coordinates and avoids relying on its deprecated built-in city-name weather lookup. The resolved city, country, and coordinates are returned so clients can see which location was selected.

4. **Use Laravel's HTTP client.** Implement `App\APIClients\OpenWeatherMapClient` behind `OpenWeatherMapClientInterface` using the framework HTTP facade with a short timeout and limited retry policy for both upstream calls. This avoids a new runtime dependency, allows `WeatherService` to depend on an abstraction, and supports deterministic `Http::fake()` tests. The provider base URL, geocoding path, weather path, and API key are read from `config/services.php` rather than from request code.

5. **Return an application-owned response shape.** `WeatherService` maps only the fields needed by the API contract: resolved city/country, coordinates, temperature, feels-like temperature, humidity, pressure, wind speed, weather conditions, and observation time. Returning either provider payload directly would expose an unstable external contract to clients.

6. **Translate failures at the service boundary.** Laravel validation returns its normal 422 JSON response. An empty geocoding result becomes 404 for an unknown city; provider authentication or rate-limit failures and missing local configuration become a controlled 503 response; timeouts and transport failures in either call also become 503. `WeatherService` performs this translation so the controller remains transport-focused. Provider API keys and raw upstream responses are never returned to clients.

7. **Do not cache or persist in the first version.** Each request resolves the city and obtains current data from OpenWeatherMap. Caching can be added later with an explicit freshness and invalidation requirement once usage patterns are known.

## Risks / Trade-offs

- **[Provider availability]** The endpoint depends on OpenWeatherMap availability and quotas → use bounded timeouts, limited retries, clear 503 responses, and mocked tests.
- **[Ambiguous city names]** Geocoding may return multiple locations with the same name → request one result, accept the provider's first match in this version, and expose the resolved city, country, and coordinates; support explicit country codes or city IDs in a later change.
- **[Public traffic]** An unauthenticated endpoint can consume the provider quota → leave rate limiting out of scope for this proposal but keep the route isolated so Laravel rate limiting can be added without changing the client contract.
- **[Missing configuration]** A missing API key would otherwise produce opaque upstream errors → validate configuration at request time and return a controlled service-unavailable response.

## Migration Plan

No database migration is required. Add `OPENWEATHERMAP_API_KEY` and optional provider URL/locale settings to deployment environments, deploy the route and service, then verify the endpoint with a known city. Rollback consists of removing the route/service changes; environment variables may remain unused.

## Open Questions

- Should the public endpoint receive a dedicated rate limit before production traffic is enabled?
- Should a later version accept an explicit country code or city ID to disambiguate duplicate city names?
