# weather-by-city Specification

## Purpose

TBD: Define the purpose of the weather-by-city capability.

## Requirements

### Requirement: Retrieve current weather by city

The API SHALL expose a public `GET /api/weather` endpoint that accepts a required `city` query parameter and returns current weather data for the resolved city from OpenWeatherMap.

#### Scenario: Successful weather lookup

- **WHEN** a client sends `GET /api/weather?city=Omsk` and OpenWeatherMap returns current weather data
- **THEN** the API responds with HTTP 200 and a JSON object containing the resolved city and country, coordinates, temperature, feels-like temperature, humidity, pressure, wind speed, weather conditions, and observation time

#### Scenario: City name is normalized before lookup

- **WHEN** a client sends a city value with leading or trailing whitespace
- **THEN** the API trims the value before validation and sends the normalized city name as the Geocoding API's `q` parameter

#### Scenario: City name includes a country qualifier

- **WHEN** a client sends a city value such as `Paris,FR`
- **THEN** the API passes the qualified value to the Geocoding API and returns the resolved city and country in the response

### Requirement: Validate the city query

The API SHALL reject requests whose `city` query parameter is missing, empty, non-string, or longer than the configured maximum before making an upstream request.

#### Scenario: City is missing

- **WHEN** a client sends `GET /api/weather` without a `city` parameter
- **THEN** the API responds with HTTP 422 using Laravel's JSON validation format and identifies `city` as required

#### Scenario: City is empty or whitespace only

- **WHEN** a client sends `GET /api/weather?city=%20%20%20`
- **THEN** the API responds with HTTP 422 and does not call OpenWeatherMap

#### Scenario: City exceeds the maximum length

- **WHEN** a client sends a city value longer than the configured maximum length
- **THEN** the API responds with HTTP 422 and does not call OpenWeatherMap

### Requirement: Request and map OpenWeatherMap data

The integration SHALL first call OpenWeatherMap's Direct Geocoding API with the configured API key, the normalized city as `q`, and `limit=1`, then call the Current Weather API with the resolved `lat` and `lon`, `units=metric`, and the configured language defaulting to `ru`; it SHALL map provider fields into the application-owned response contract.

#### Scenario: City name is resolved before weather lookup

- **WHEN** the Geocoding API returns a location for a valid city name
- **THEN** the API uses that location's latitude and longitude for the Current Weather API request and returns the resolved city, country, and coordinates

#### Scenario: Provider request uses configured parameters

- **WHEN** a valid weather request is processed
- **THEN** the upstream requests include the configured base URL, geocoding `q`, `limit=1`, and `appid`, followed by weather `lat`, `lon`, `appid`, `units=metric`, and `lang=ru`, without exposing the API key in the application response

#### Scenario: Provider response contains weather conditions

- **WHEN** OpenWeatherMap returns a successful payload with main weather data, wind data, conditions, city metadata, and a timestamp
- **THEN** the API returns the corresponding normalized values without returning unrelated provider fields

#### Scenario: Provider response omits an optional field

- **WHEN** OpenWeatherMap omits an optional field such as wind direction or rain volume
- **THEN** the API returns a stable response with that field represented as `null` or omitted according to the documented response contract and still returns HTTP 200 when the required weather data is present

### Requirement: Handle provider and configuration failures

The API SHALL translate OpenWeatherMap failures and local configuration failures into stable JSON error responses without exposing provider credentials or raw upstream payloads.

#### Scenario: City is not found

- **WHEN** OpenWeatherMap returns its not-found response for a valid city query
- **THEN** the API responds with HTTP 404 and a client-safe message that the requested city was not found, without calling the Current Weather API

#### Scenario: Provider rejects credentials or rate limit

- **WHEN** OpenWeatherMap returns an authentication, authorization, or rate-limit failure
- **THEN** the API responds with HTTP 503 and a client-safe service-unavailable message

#### Scenario: Provider request times out or fails to connect

- **WHEN** the bounded HTTP request and its limited retries cannot reach OpenWeatherMap
- **THEN** the API responds with HTTP 503 and does not expose transport details or provider credentials

#### Scenario: API key is not configured

- **WHEN** the endpoint is called without a configured OpenWeatherMap API key
- **THEN** the API responds with HTTP 503 and does not make an upstream request

### Requirement: Keep weather lookups read-only

The API SHALL perform weather lookups without requiring authentication and SHALL NOT create or update durable weather domain records. Successful normalized results MAY be stored as expiring entries in the configured cache store.

#### Scenario: Anonymous client requests weather

- **WHEN** an unauthenticated client sends a valid weather request
- **THEN** the API processes the request without redirecting to a login page

#### Scenario: Successful lookup does not create persistent records

- **WHEN** a weather lookup completes successfully
- **THEN** the API returns the result without creating or updating a weather model or weather domain table, while permitting an expiring entry in Laravel's configured cache store

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
