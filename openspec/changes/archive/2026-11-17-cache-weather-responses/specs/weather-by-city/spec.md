# Spec Delta

## ADDED Requirements

### Requirement: Cache successful weather lookups

The API SHALL cache each successful normalized current-weather result for the configured TTL, defaulting to 600 seconds, and SHALL reuse a fresh cached result without calling OpenWeatherMap.

#### Scenario: First request populates the cache

- **WHEN** a valid city has no fresh cache entry and OpenWeatherMap returns current weather successfully
- **THEN** the API returns the existing HTTP 200 response contract and stores the normalized result until the configured TTL expires

#### Scenario: Repeated request uses a fresh result

- **WHEN** a client requests a city whose successful result remains fresh in the cache
- **THEN** the API returns the cached result using the existing JSON contract without calling either OpenWeatherMap endpoint

#### Scenario: Equivalent city queries share an entry

- **WHEN** clients request city values that differ only by leading or trailing whitespace or letter case
- **THEN** the API treats those values as the same cache identity and reuses the fresh successful result

#### Scenario: Distinct lookups remain isolated

- **WHEN** clients request different normalized cities
- **THEN** the API uses distinct cache identities and does not return one lookup's result for the other

#### Scenario: Expired result is refreshed

- **WHEN** a client requests a city after its cached result has expired
- **THEN** the API performs the existing OpenWeatherMap lookup flow, returns the fresh result, and replaces the expired cache entry

#### Scenario: Failed lookup is not cached

- **WHEN** a lookup fails because the city is unknown, provider configuration is missing, the provider rejects the request, or the provider cannot be reached
- **THEN** the API returns the existing error response and the next valid request attempts the provider lookup again

#### Scenario: Validation failure bypasses caching

- **WHEN** the city query fails the existing request validation
- **THEN** the API returns HTTP 422 without reading or writing a weather cache entry and without calling OpenWeatherMap

## MODIFIED Requirements

### Requirement: Keep weather lookups read-only

The API SHALL perform weather lookups without requiring authentication and SHALL NOT create or update durable weather domain records. Successful normalized results MAY be stored as expiring entries in the configured cache store.

#### Scenario: Anonymous client requests weather

- **WHEN** an unauthenticated client sends a valid weather request
- **THEN** the API processes the request without redirecting to a login page

#### Scenario: Successful lookup does not create persistent records

- **WHEN** a weather lookup completes successfully
- **THEN** the API returns the result without creating or updating a weather model or weather domain table, while permitting an expiring entry in Laravel's configured cache store
