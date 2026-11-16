# Design

## Context

See `proposal.md` for motivation and `specs/weather-search-page/spec.md` for observable behavior.

The project already has a public `GET /api/weather` endpoint implemented through `WeatherRequest`, `WeatherService`, provider DTOs, and `WeatherResource`. It returns the complete response contract required by the page and already normalizes provider and configuration failures. The frontend is the Laravel React starter-kit stack: Inertia React 3, React 19, TypeScript, Tailwind CSS 4, shadcn-style UI primitives, Lucide icons, and generated Wayfinder routes.

The default `AppLayout` is dashboard-oriented, while `welcome` opts out of it. The weather page must be public and must not assume an authenticated user. There is no installed browser or client-unit-test runner, and this change must not add dependencies.

## Goals / Non-Goals

**Goals:**

- Keep Laravel responsible for page routing, localization, validation, and the weather API contract while React owns the interactive presentation.
- Reuse the existing weather service and JSON endpoint without duplicating provider orchestration in a web controller.
- Use the framework's current primitives for standalone HTTP state, generated routes, formatting, themes, and UI feedback.
- Keep request, result, and error states explicit, typed, accessible, and deterministic across repeated searches.

**Non-Goals:**

- Introducing another API endpoint, changing provider behavior, or moving weather business logic into React.
- Adding a general frontend state store, client data-cache library, frontend localization package, or test runner.
- Persisting user input or weather results in browser or server storage.
- Enabling SSR, autocomplete, geolocation, forecasts, or background refresh as part of this change.

## Decisions

### 1. Group web page controllers under the `Web` namespace

Add `App\Http\Controllers\Web\WeatherController` with an explicit `index` method and connect it to the named `GET /weather` route. The controller returns `Inertia::render('weather/index', ...)` and supplies only the active locale and explicit translated page copy from `lang/en/weather.php`. It does not inject `WeatherService` or perform a provider request.

Only web page delivery is grouped under `App\Http\Controllers\Web`. The existing API classes remain unchanged at `App\Http\Controllers\WeatherController`, `App\Http\Requests\WeatherRequest`, and `App\Http\Resources\WeatherResource`; they are not moved into a new namespace as part of this change. This keeps the requested separation explicit without creating unrelated API churn.

This keeps the initial page fast, leaves weather orchestration in the existing Service layer, and avoids mixing an Inertia response with the existing API controller. The route remains outside authentication middleware because both the existing API and the new capability are public.

Alternative considered: load weather through the page controller and submit the form as an Inertia visit. This was rejected because it duplicates the API boundary, turns each search into a page visit, and makes the page controller responsible for weather lookup failures.

### 2. Use Inertia v3 `useHttp` for the existing JSON endpoint

The page uses a typed `useHttp` instance with `{ city: string }` data and submits only from the form's `onSubmit`. Its `get` method sends the data as query parameters, exposes Laravel 422 validation errors, tracks `processing`, and provides separate callbacks for successful data, HTTP exceptions, and network errors.

The API URL comes from the generated Wayfinder helper for the named `weather.show` route. The page never hard-codes `/api/weather`. The current request clears stale result and failure state before submission; the disabled submit button prevents duplicate requests. A successful callback stores the typed resource payload, while failures store a small presentation error state. The input remains available for correction or retry.

Alternatives considered:

- `fetch` or Axios: rejected because `useHttp` already provides request lifecycle, validation, cancellation, and typed response support; Axios is not installed.
- Inertia `<Form>` or `useForm`: rejected because those are optimized for Inertia visits and redirect-based form flows, while this endpoint intentionally returns standalone JSON.
- Calling OpenWeatherMap from React: rejected because it would expose credentials and duplicate validation, mapping, retries, and error normalization.

### 3. Keep the API response contract explicitly typed at the UI boundary

Define page-local exported TypeScript types for the JSON resource envelope and its weather fields. Nullable provider fields remain nullable in TypeScript. The result component receives the normalized weather object rather than the raw HTTP response.

No new PHP DTO or Resource is needed: `WeatherDTO` and `WeatherResource` remain the backend source of truth. Wayfinder 0.1.23 supplies route typing but does not replace the explicit response type used by this installed stack.

### 4. Compose the page from existing UI primitives

Add a `weather/index` page with two focused components: a search form and a weather result card. Reuse the existing `Input`, `Label`, `Button`, `Card`, `Alert`, `Skeleton`, and `Spinner` primitives plus Lucide icons. Do not introduce a generic component abstraction until another page needs it.

The page opts out of the authenticated dashboard layout through the existing Inertia layout resolver and renders a self-contained public shell. The shell uses semantic `header`, `main`, `section`, and `footer` regions, the application's CSS variables, Tailwind responsive utilities, and matching `dark:` states. It includes a simple link to the named home route through Wayfinder.

The result emphasizes place, current temperature, and condition first, followed by a responsive metric grid for feels-like temperature, humidity, pressure, wind, coordinates, and observation time. It uses built-in `Intl.NumberFormat` and `Intl.DateTimeFormat` with the locale supplied by Laravel. Null optional values render the translated unavailable label. Provider HTML is never rendered.

Alternative considered: load OpenWeatherMap icon assets directly. This was rejected to avoid a second external runtime dependency and because the installed Lucide set can provide stable decorative weather and metric icons while condition text remains authoritative.

### 5. Treat request states as an explicit UI state machine

The visible states are:

1. Empty: guidance is visible and no result exists.
2. Loading: inputs remain visible, **Show** is disabled, and an accessible skeleton/status is shown.
3. Success: one result card represents the latest completed search.
4. Validation failure: the Laravel field message is associated with the city input.
5. HTTP failure: not-found and service-unavailable responses show localized, non-technical alerts.
6. Network failure: a translated retryable alert is shown.

Each submission clears stale success and non-validation error state before the request begins. A polite live region announces loading and success; errors use an alert role. The city field has an explicit label, `aria-invalid`, and `aria-describedby`, and all controls retain visible keyboard focus.

### 6. Keep localization server-owned

Add page headings, form labels, actions, units, empty/loading text, unavailable text, and browser-network fallback copy to the existing weather translation file. The page controller passes a named, explicit copy object rather than exposing the whole translation file. API validation and service messages continue to come from Laravel's existing localized responses.

This satisfies the project rule that user-facing text lives in translation files without adding a frontend i18n dependency. The weather condition description is displayed exactly as returned by the current API contract.

### 7. Verify the server boundary and existing frontend quality gates

Add a PHPUnit feature test that makes one request as a guest and asserts the named route returns the `weather/index` Inertia component with the locale and expected copy keys. Existing `WeatherTest` coverage continues to own API success, validation, provider failures, and read-only behavior; those scenarios are not duplicated in the page test.

Because no JavaScript test runner or browser harness is installed, implementation verification uses `npm run types:check`, `npm run check`, and `npm run build` for the React code, plus focused PHPUnit, Pint, and Larastan checks. Adding Vitest, Testing Library, Cypress, or Dusk requires a separate dependency decision.

## Backend, Data, and Deployment Impact

- **Laravel backend:** One thin controller under `App\Http\Controllers\Web`, one public named web route, and explicit translated page props. The existing API controller, Request, Resource, Service, DTOs, and exception renderer remain in their current namespaces and are unchanged.
- **Inertia/React frontend:** One page, focused presentation components, explicit types, a public layout branch, and regenerated Wayfinder output.
- **Database:** No migration, model, factory, seeder, query, cache entry, session value, or persisted weather data.
- **Authentication/security:** No authorization change. React escapes returned strings by default; the UI does not use raw HTML or receive the OpenWeatherMap key. Existing server-side validation remains authoritative.
- **Deployment:** Existing PHP, Node, Vite, Sail, and OpenWeatherMap configuration are sufficient. No new container, environment variable, queue worker, SSR process, or external asset host is required.

## Risks / Trade-offs

- **[Risk] The manual TypeScript response type can drift from `WeatherResource`.** → Keep field names and nullability adjacent to the page, validate with TypeScript, and retain exact API response tests as the backend contract check.
- **[Risk] A public browser page can increase traffic to the already-public provider-backed endpoint.** → Disable duplicate submissions and retain the existing bounded timeout/retry behavior; rate limiting remains a separate API policy decision.
- **[Risk] Provider errors and browser network errors have different response shapes.** → Normalize them into a small presentation error state and never expose raw exception or provider payload data.
- **[Risk] Opting out of `AppLayout` creates a page-specific shell.** → Keep the shell intentionally small and use existing global tokens/components; extract a shared public layout only when a second page needs it.
- **[Trade-off] No client-side component tests are added.** → Cover the Laravel/Inertia boundary and run static/build checks now; add a frontend test stack only through an explicit dependency change.

## Migration Plan

1. Add translations, `App\Http\Controllers\Web\WeatherController`, and the public route while leaving all existing API classes in place.
2. Generate Wayfinder route helpers after the route is available.
3. Add typed React page/components and the layout resolver entry.
4. Add the page feature test and run focused backend and frontend quality checks.
5. Deploy with the existing application build; no data migration or maintenance window is required.

Rollback removes the web route, `Web\WeatherController`, page/components/types, translation entries, and layout resolver entry, then regenerates Wayfinder output. The existing weather API remains in place and operational throughout deployment and rollback.
