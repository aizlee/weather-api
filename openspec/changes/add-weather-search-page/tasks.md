# Tasks

## 1. Laravel Page Boundary and Localization

- [ ] 1.1 Generate `App\Http\Controllers\Web\WeatherController` with `php artisan make:controller Web/WeatherController --no-interaction`, implement an explicit `index` method that returns `weather/index` with the active locale and named translated copy props, leave the existing API controller, Request, and Resource in their current namespaces, and verify the web controller passes targeted Larastan analysis.
- [ ] 1.2 Add the public named `GET /weather` route to `routes/web.php` using `Web\WeatherController`, keep it outside authentication middleware, and verify it resolves to the web controller with `php artisan route:list --path=weather --except-vendor`.
- [ ] 1.3 Extend `lang/en/weather.php` with every page heading, form label, action, state, metric, unit, unavailable value, and network fallback used by the UI; verify the page controller contains no hard-coded user-facing strings.
- [ ] 1.4 Generate `tests/Feature/Web/WeatherPageTest.php` with `php artisan make:test --phpunit Web/WeatherPageTest --no-interaction`, cover guest and authenticated access plus the Inertia component, locale, and explicit copy props, and verify it passes with `php artisan test --compact tests/Feature/Web/WeatherPageTest.php`.

## 2. Typed Frontend Request Flow

- [ ] 2.1 Regenerate typed route helpers with `php artisan wayfinder:generate --with-form --no-interaction`, add explicit TypeScript types for the `WeatherResource` envelope and nullable weather fields, and verify generated helpers expose both the page route and `weather.show` API route without hard-coded URLs.
- [ ] 2.2 Add `resources/js/pages/weather/index.tsx` and focused search-form state using typed Inertia v3 `useHttp`; submit only on form submission, clear stale state before each request, map 422, 404, 503, and network outcomes, disable duplicate submissions, and verify with `npm run types:check`.
- [ ] 2.3 Update the Inertia layout resolver so `weather/` pages do not use the authenticated dashboard shell, add page metadata and a Wayfinder home link, and verify guest rendering does not access a required authenticated user.

## 3. Weather Presentation and Accessibility

- [ ] 3.1 Build a focused weather result component that renders location, temperature, feels-like temperature, conditions, humidity, pressure, wind, coordinates, and observation time; format numbers and dates with the Laravel-provided locale, render translated fallbacks for null values, and verify `npm run types:check` passes.
- [ ] 3.2 Compose empty, loading skeleton, success, validation, HTTP-error, and network-error states from existing UI primitives and Lucide icons; verify field labels, `aria-invalid`, descriptions, live regions, alert roles, keyboard focus, and disabled states are present in the rendered JSX.
- [ ] 3.3 Apply responsive Tailwind CSS 4 styling with existing theme tokens and dark-mode variants, verify there is no horizontal overflow at mobile and desktop widths, and run `npm run check` plus `npm run build` successfully.

## 4. Integration Verification

- [ ] 4.1 Run `php artisan test --compact tests/Feature/Web/WeatherPageTest.php tests/Feature/WeatherTest.php` and verify the new Web page boundary and unchanged weather API contract both pass.
- [ ] 4.2 Run `vendor/bin/pint --dirty --format agent` and targeted `vendor/bin/phpstan analyse app/Http/Controllers/Web/WeatherController.php tests/Feature/Web/WeatherPageTest.php` for the changed PHP boundary, resolving all reported project errors.
- [ ] 4.3 Run `composer ci:check` and `npm run build` to verify the complete PHP, TypeScript, lint, test, and production-bundle gates without adding dependencies.

## Workflow follow-up

- Review the page against the acceptance scenarios, then archive the OpenSpec change after implementation is complete.
