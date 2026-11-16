# Proposal

## Why

The application already exposes current weather through a public JSON API, but users have no browser interface for entering a city and understanding the result. A focused Inertia + React page will make the existing capability usable without duplicating provider integration or introducing a separate frontend application.

## What Changes

- Add a public, named `/weather` web route that renders a dedicated Inertia page for weather search.
- Add a React search form that submits a city only when the user presses **Show**, calls the existing `GET /api/weather` endpoint through Inertia v3 `useHttp`, and obtains the endpoint URL from Wayfinder instead of hard-coding it.
- Present the returned location, current temperature, feels-like temperature, conditions, humidity, pressure, wind, coordinates, and observation time in a responsive weather card.
- Provide distinct empty, loading, success, validation, city-not-found, provider-unavailable, and network-error states without navigating away from the page.
- Reuse the existing TypeScript, Tailwind CSS 4, dark-mode tokens, Lucide icons, and UI primitives, with accessible labels, focus states, disabled states, and live status announcements.
- Source all user-facing copy from Laravel translation files and pass page copy through explicit Inertia props.
- Add server-side feature coverage for the public Inertia page and retain the existing API tests as the source of truth for weather lookup behavior.

## Non-Goals

- Changing the existing `/api/weather` contract, OpenWeatherMap integration, DTOs, service logic, or provider configuration.
- Adding city autocomplete, geolocation, forecasts, search history, favorites, authentication requirements, persistence, or new frontend dependencies.
- Replacing the existing application layout or redesigning unrelated pages.

## Acceptance Criteria

- A guest can open `/weather`, enter a valid city, press **Show**, and see the current weather returned by the existing API without a full-page navigation.
- The form prevents duplicate submissions while loading and exposes Laravel validation errors next to the city field.
- The page gives clear, non-technical feedback for city-not-found, service-unavailable, and network failures, and a later successful search replaces the previous state.
- The result remains readable and operable on mobile and desktop in light and dark modes, including keyboard and screen-reader use.
- No weather data is persisted, no authentication behavior changes, and no database migration or deployment service change is required.

## Capabilities

### New Capabilities

- `weather-search-page`: Public Inertia + React weather search experience, including submission, result presentation, and user-visible request states.

### Modified Capabilities

None. The existing `weather-by-city` API requirements and response contract are reused unchanged.

## Impact

- **Laravel:** Add a thin page controller, a named public web route, localized page copy, and an Inertia response test.
- **React/Inertia:** Add a typed page and focused presentation components using `useHttp` for the standalone JSON request.
- **Wayfinder:** Regenerate route helpers so the page and API routes are referenced through generated TypeScript functions.
- **API:** No contract change; the page consumes the existing public `GET /api/weather` endpoint.
- **Authentication and data:** The page remains public and read-only; SQLite schema and stored data are unchanged.
- **Dependencies and deployment:** No new Composer, npm, Docker, queue, cache, or deployment requirements.
