# Design

## Context

See `proposal.md` for motivation and `specs/weather-by-city/spec.md` for observable behavior.

`WeatherService::getCurrentWeather()` currently performs geocoding, retrieves current conditions, maps the two provider DTOs into `WeatherDTO`, and lets `WeatherServiceException` propagate to the existing application exception renderer. The controller and `WeatherResource` already form a stable HTTP boundary. Laravel's default cache store is `database`, the cache table already exists, and `cache.serializable_classes` is `false`.

The implementation therefore needs to cache at the service boundary without caching HTTP responses, provider DTOs, exceptions, or PHP objects that the configured cache security policy will refuse to unserialize.

## Goals / Non-Goals

**Goals:**

- Make repeated equivalent city lookups avoid both provider requests while a successful result is fresh.
- Keep cache behavior explicit, configurable, backend-independent, and contained in `WeatherService`.
- Preserve `WeatherDTO` as the service result and `WeatherResource` as the response adapter.
- Keep cached data safe for the existing database store and `serializable_classes=false` policy.
- Make hit, miss, key isolation, expiry, and failure behavior deterministic in tests.

**Non-Goals:**

- Caching rendered JSON or adding cache metadata/headers to the API response.
- Adding stale-while-revalidate, background refresh, cache warming, tags, or atomic stampede prevention.
- Creating a weather table, model, repository, cache abstraction, or provider-specific caching inside `OpenWeatherMapClient`.

## Decisions

### 1. Cache in `WeatherService`, around the complete lookup

Inject Laravel's `Illuminate\Contracts\Cache\Repository` into `WeatherService` alongside `OpenWeatherMapClientInterface`. The public `getCurrentWeather()` method will use the repository's `remember` operation around the existing geocode, current-weather, exception translation, and mapping flow.

This placement makes one entry represent the complete application result and prevents both upstream calls on a hit. It keeps controllers and Resources transport-only and prevents provider-aware cache behavior from leaking into `OpenWeatherMapClient`.

Alternatives considered:

- Controller or HTTP-response caching was rejected because it couples caching to JSON serialization and bypasses the existing Service/DTO boundary.
- Caching geocoding and current weather independently was rejected because it introduces two TTLs, partial-hit behavior, and more invalidation complexity without a current requirement.
- A project-specific cache interface was rejected because Laravel already supplies a stable cache contract and container binding.

### 2. Cache a scalar application payload and reconstruct `WeatherDTO`

The cache value will be an explicitly shaped array of scalar and nullable values matching the `WeatherDTO::from()` attributes. On both a cache hit and miss, the public service method reconstructs and returns `WeatherDTO` through its existing factory.

The provider-facing methods continue to consume `OpenWeatherMapLocationDTO` and `OpenWeatherMapCurrentWeatherDTO`; raw provider arrays never enter the Service. The mapping method produces the cache-safe application payload before the final DTO is created.

Caching `WeatherDTO` directly was rejected because the project's configured cache stores deserialize with `allowed_classes=false`. A scalar payload works with database, array, file, Redis, and other Laravel stores without weakening the global unserialization policy. Caching the Resource array was rejected because that would move HTTP field naming into the Service layer.

### 3. Use a readable canonical cache key

Build the key from the `weather:` namespace and the city after Unicode-aware trimming and lowercasing. For example, ` Omsk ` becomes `weather:omsk`.

The city is already public request data and validation limits its length. Keeping it readable is simpler than hashing it and makes cache inspection easier. The API key and other provider configuration remain excluded from the cache key.

### 4. Use Laravel `remember` with a configurable positive TTL

Add `cache_ttl` to `services.openweathermap`, sourced from `OPENWEATHERMAP_CACHE_TTL` and defaulting to `600` seconds. Use this declared config directly in the Service and document it in `.env.example` and `.env.testing`.

Laravel `remember` stores a value only when the callback completes successfully. Existing `WeatherServiceException` paths therefore remain uncached without additional catch-and-delete logic. A subsequent request retries the provider naturally.

`Cache::flexible` was rejected for this change because serving stale weather and refreshing after the response would add a second freshness window and deferred-failure semantics not requested by the specification. Indefinite caching was rejected because current weather must remain bounded in age.

### 5. Preserve the external contract and update persistence assertions

No controller, Request, Resource, route, exception message, status code, or frontend type changes are needed. Cached and uncached responses pass through the same `WeatherDTO` and `WeatherResource` path.

The existing test that rejects every mutating SQLite query must be updated: database-backed cache writes are now intentional. It should continue proving that no weather model/domain table is created or mutated while allowing writes to Laravel's `cache` table. Cache-focused tests clear cache state between cases and verify provider request counts, response equality, canonical key behavior, isolation, expiry, and failure retries.

## Backend, Frontend, Database, and Deployment Impact

- **Laravel backend:** Only `WeatherService` orchestration and weather configuration change. `OpenWeatherMapClient`, its contract, controller, Request, Resource, DTO response contract, and exception rendering remain unchanged.
- **Inertia/React frontend:** No code or behavior changes. The page continues to call the same named API route and consume the same JSON envelope.
- **Database/cache:** No migration is required. With the default store, successful scalar payloads are transient rows in the existing `cache` table and expire through Laravel's cache semantics. No durable weather table or model is introduced.
- **Deployment:** Existing cache infrastructure is sufficient. `OPENWEATHERMAP_CACHE_TTL` is optional and defaults to 600 seconds; deployments should set a positive value appropriate to freshness and quota requirements. Configuration cache must be rebuilt through the normal deployment process.
- **Authentication/security:** The endpoint remains public. Cache keys contain only the normalized public city input and no credentials, cached payloads contain only fields already exposed by the public response, and the global `serializable_classes=false` protection remains unchanged.

## Risks / Trade-offs

- **[Concurrent misses can still duplicate provider calls]** → Keep the first version on Laravel's simple `remember` path; add lock-based stampede protection only if production traffic demonstrates a need.
- **[Short-lived data may be up to the TTL old]** → Default to 600 seconds and make the value environment-configurable.
- **[Provider configuration changes can coexist with old entries]** → Accept the bounded TTL and introduce explicit invalidation only if response semantics change in the future.
- **[Database cache writes change the meaning of the old read-only test]** → Assert absence of weather domain persistence rather than absence of all cache infrastructure writes.
- **[Malformed cached values could violate the expected array shape]** → Keep entries short-lived, add static array-shape annotations, and verify reconstruction with feature tests and Larastan.

## Migration Plan

1. Add the TTL configuration and environment examples.
2. Add cache orchestration and the cache-safe payload mapping in `WeatherService`.
3. Update and extend weather feature tests, then run focused and complete quality gates.
4. Deploy using the existing cache store and rebuild Laravel configuration cache as usual.

Rollback removes the service cache wrapper and TTL setting. Existing `weather:*` entries require no destructive flush; they are unreachable after rollback and expire automatically within the configured TTL.
