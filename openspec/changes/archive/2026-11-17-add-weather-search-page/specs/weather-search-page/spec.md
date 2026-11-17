# Spec Delta

## Purpose

Provide a public browser experience for searching a city and understanding its current weather without requiring API knowledge or authentication.

## ADDED Requirements

### Requirement: Public weather search page

The application SHALL provide a public weather page with a city field and a **Show** action, without requiring authentication.

#### Scenario: Guest opens the weather page

- **WHEN** an unauthenticated user visits `/weather`
- **THEN** the application displays the weather search page with an empty city field and no weather result

#### Scenario: Authenticated user opens the weather page

- **WHEN** an authenticated user visits `/weather`
- **THEN** the application displays the same weather search capability without changing the user's authentication state

### Requirement: Search current weather by city

The page SHALL submit the entered city only when the user activates **Show**, display request progress, and present the current weather without a full-page navigation.

#### Scenario: Successful city search

- **WHEN** a user enters a valid city and activates **Show**
- **THEN** the page displays a loading state and then replaces it with the returned current weather while remaining on the weather page

#### Scenario: Duplicate submission while loading

- **WHEN** a weather search is already in progress
- **THEN** the **Show** action is disabled and the page does not start a duplicate request

#### Scenario: Search another city

- **WHEN** a result is visible and the user submits a different valid city
- **THEN** the page replaces the previous result and messages with the outcome of the latest search

### Requirement: Present the weather result

The page SHALL present the resolved place, temperature, feels-like temperature, conditions, humidity, pressure, wind, coordinates, and observation time from a successful lookup.

#### Scenario: Complete weather data is returned

- **WHEN** a weather lookup returns all documented fields
- **THEN** the page displays the city and country, coordinates, temperature, feels-like temperature, condition, humidity, pressure, wind speed and direction, and observation time with clear labels and units

#### Scenario: Optional weather data is unavailable

- **WHEN** a successful lookup contains a null optional value
- **THEN** the page displays a consistent localized unavailable value instead of an empty or broken field

### Requirement: Surface validation and lookup failures

The page SHALL provide localized, non-technical feedback for invalid input, an unknown city, provider unavailability, and network failure without exposing provider details.

#### Scenario: City input is invalid

- **WHEN** the submitted city is missing, blank, non-string, or longer than the supported maximum
- **THEN** the page displays the validation message next to the city field and does not display a new weather result

#### Scenario: City is not found

- **WHEN** the weather lookup reports that the requested city was not found
- **THEN** the page displays a localized not-found message and keeps the city field available for correction

#### Scenario: Weather service is unavailable

- **WHEN** the weather lookup reports a provider or configuration failure
- **THEN** the page displays a localized service-unavailable message without provider credentials or raw response details

#### Scenario: Browser network request fails

- **WHEN** the browser cannot complete the weather request
- **THEN** the page displays a localized network-error message and allows the user to retry

### Requirement: Accessible and adaptive interaction

The weather page SHALL remain understandable and operable across supported screen sizes, color modes, keyboard navigation, and assistive technology.

#### Scenario: Keyboard and screen-reader interaction

- **WHEN** a user navigates and submits the form with a keyboard or assistive technology
- **THEN** the city field has an associated label, focus is visible, validation is associated with the field, and dynamic status and result changes are announced

#### Scenario: Mobile and desktop presentation

- **WHEN** the page is viewed at mobile or desktop widths
- **THEN** the form and weather details remain readable without horizontal scrolling and actions remain reachable

#### Scenario: Light and dark appearance

- **WHEN** the application uses light or dark appearance
- **THEN** the page preserves readable contrast and visible interactive states using the application's active theme
