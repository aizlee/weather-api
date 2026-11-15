## Why

The application has no weather data endpoint yet, so clients cannot request current conditions for a city. This change adds a small public API backed by OpenWeatherMap while keeping provider credentials and provider-specific details on the server.

## What Changes

- Add a public `GET /api/weather?city={city}` endpoint for current weather by city name.
- Validate and normalize the requested city name, resolve it to coordinates through OpenWeatherMap Geocoding API, then call the current weather API with metric units and Russian localization by default.
- Return a stable, provider-independent JSON shape containing the resolved city, country, temperature, feels-like temperature, humidity, pressure, wind, conditions, and observation time.
- Read the OpenWeatherMap API key and base URL from environment-backed Laravel configuration.
- Map validation errors, unknown cities, upstream authorization/rate-limit failures, and upstream outages to documented API responses.
- Add feature tests covering successful responses, validation, provider errors, and unavailable upstream responses.
- Do not persist weather responses or add a frontend page in this change.

## Capabilities

### New Capabilities

- `weather-by-city`: Provide current weather data for a city through a public JSON API backed by OpenWeatherMap.

### Modified Capabilities

- None.

## Impact

- Add an API route, request validation, a thin weather controller, `WeatherService` under `app/Services`, and an `OpenWeatherMapClient` implementation under `app/APIClients` behind an interface in `app/Contracts`.
- Add OpenWeatherMap settings to `config/services.php`, `.env.example`, and the Docker/Sail environment workflow without exposing the API key.
- Use Laravel's built-in HTTP client; no additional runtime dependency is required.
- Add feature tests with mocked HTTP responses so tests do not call OpenWeatherMap or require network access.
- The endpoint is assumed to be public and read-only. The assumption can be changed during review if weather data must require authentication or rate limiting.
- OpenWeatherMap provides direct geocoding by city name and recommends coordinate-based current-weather requests; the implementation should follow the [Geocoding API](https://openweathermap.org/api/geocoding-api) and [Current Weather API](https://openweathermap.org/api/current) contracts.
