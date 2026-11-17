# Proposal

## Why

Repeated requests for the same city currently perform both OpenWeatherMap calls every time, increasing response latency and consuming provider quota for data that changes relatively slowly. Caching successful lookups for a short, configurable period will reduce upstream traffic while preserving the existing public API contract.

## What Changes

- Cache successful current-weather results in the `WeatherService` layer by a readable key derived from the normalized city.
- Use Laravel's configured cache store and a configurable TTL, defaulting to 600 seconds.
- Return a fresh cached result without calling either OpenWeatherMap endpoint; refresh the cache through the existing provider flow after expiry.
- Cache only successful normalized weather data. Validation failures, unknown cities, provider failures, transport failures, and missing configuration remain uncached.
- Preserve the existing `GET /api/weather` route, request validation, JSON resource shape, status codes, authentication behavior, and React/Inertia consumer behavior.
- Allow transient weather payloads in the configured cache store while continuing to prohibit durable weather domain records.

## Non-Goals

- Adding forecasts, scheduled refreshes, stale-while-revalidate behavior, manual per-city invalidation endpoints, or a new cache backend.
- Caching error responses or bypassing the existing OpenWeatherMap client, DTO mapping, Resource, or exception handling.
- Changing route rate limits, authentication, the response schema, or frontend behavior.

## Acceptance Criteria

- Two equivalent city queries within the configured TTL return the same response while OpenWeatherMap is called only for the first lookup.
- City key normalization is case-insensitive and whitespace-insensitive, while different cities do not share entries.
- A request after expiry performs a new provider lookup and replaces the cached result.
- Failed lookups are retried on the next request rather than served from cache.
- Existing endpoint success, validation, not-found, unavailable, anonymous-access, and response-contract tests continue to pass.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `weather-by-city`: Add bounded caching semantics for successful current-weather lookups and clarify that transient cache entries are allowed while durable weather persistence remains prohibited.

## Impact

- **Backend:** `App\Services\WeatherService` gains cache lookup/write orchestration around its existing provider flow. The controller, Request, Resource, API client contract, and API client remain unchanged.
- **Configuration:** `config/services.php`, `.env.example`, and `.env.testing` gain an OpenWeatherMap cache TTL setting with a 600-second default.
- **Data:** No migration is required; the existing Laravel cache table supports the default database cache store. Cached values expire automatically and are not weather domain records.
- **API and authentication:** No route, middleware, status-code, or JSON-contract changes.
- **Frontend:** No Inertia or React changes.
- **Dependencies and deployment:** No new package or service. Deployments may override the TTL and must continue to provide a working Laravel cache store.
