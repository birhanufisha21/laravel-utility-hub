# Requirements Document

## Introduction

A full-stack Laravel application that serves as a portfolio-quality demonstration of PHP/Laravel skills, external API integration, and modern frontend interactivity. The application provides a **Currency Converter** and/or **Weather Dashboard** by fetching live data from public APIs using Laravel's built-in Http Client. The frontend is built with Alpine.js for reactive, lightweight interactivity without a heavy JavaScript framework. The project is designed to be clean, well-structured, and representative of professional Laravel development practices.

## Glossary

- **Application**: The Laravel web application being built.
- **Http_Client**: Laravel's built-in `Illuminate\Support\Facades\Http` wrapper around Guzzle, used for all outbound HTTP requests.
- **Currency_Service**: The Laravel service class responsible for fetching and transforming currency exchange rate data.
- **Weather_Service**: The Laravel service class responsible for fetching and transforming weather data.
- **API_Controller**: The Laravel controller that exposes internal JSON endpoints consumed by the Alpine.js frontend.
- **Cache**: Laravel's caching layer (file or Redis driver) used to store API responses and reduce external calls.
- **Alpine_Component**: An Alpine.js component defined via `x-data` that manages UI state and communicates with the API_Controller.
- **Exchange_Rate_API**: The external public API providing currency exchange rate data (e.g., exchangerate.host or Open Exchange Rates free tier).
- **Weather_API**: The external public API providing weather data (e.g., Open-Meteo, which requires no API key).
- **Blade_View**: A Laravel Blade template file that renders the HTML page and bootstraps the Alpine_Component.
- **Rate_Limit_Error**: An HTTP 429 response or equivalent error returned by an external API when request limits are exceeded.
- **Validation_Error**: A structured error response returned when user-supplied input fails server-side validation rules.

---

## Requirements

### Requirement 1: Currency Conversion

**User Story:** As a portfolio visitor, I want to convert an amount from one currency to another using live exchange rates, so that I can see a working real-world API integration.

#### Acceptance Criteria

1. THE Application SHALL provide a currency conversion feature accessible at the `/currency` route.
2. WHEN a user submits a conversion request with a valid source currency, target currency, and positive numeric amount, THE API_Controller SHALL return the converted amount and the exchange rate used.
3. WHEN a user submits a conversion request, THE Currency_Service SHALL fetch the latest exchange rates from the Exchange_Rate_API using the Http_Client.
4. IF the Exchange_Rate_API returns an error response (4xx or 5xx), THEN THE Currency_Service SHALL throw a descriptive exception that THE API_Controller SHALL catch and return as a JSON error response with HTTP status 502.
5. THE Currency_Service SHALL support a minimum of 30 currency codes (e.g., USD, EUR, GBP, JPY, AUD, CAD, CHF, CNY, INR, MXN, and others).
6. WHEN exchange rate data is successfully fetched, THE Cache SHALL store the response for 60 minutes to avoid redundant external API calls.
7. WHILE cached exchange rate data exists, THE Currency_Service SHALL serve conversion results from the Cache without calling the Exchange_Rate_API.

---

### Requirement 2: Weather Dashboard

**User Story:** As a portfolio visitor, I want to view current weather conditions for a city I search for, so that I can see location-based API data rendered in a clean UI.

#### Acceptance Criteria

1. THE Application SHALL provide a weather dashboard feature accessible at the `/weather` route.
2. WHEN a user submits a city name, THE API_Controller SHALL return current weather data including temperature, weather condition description, humidity, and wind speed.
3. WHEN a city name is submitted, THE Weather_Service SHALL first resolve the city name to geographic coordinates using a geocoding endpoint, then fetch weather data for those coordinates from the Weather_API using the Http_Client.
4. IF the Weather_API returns an error response (4xx or 5xx), THEN THE Weather_Service SHALL throw a descriptive exception that THE API_Controller SHALL catch and return as a JSON error response with HTTP status 502.
5. IF a city name cannot be resolved to coordinates, THEN THE API_Controller SHALL return a JSON error response with HTTP status 404 and a human-readable message.
6. WHEN weather data is successfully fetched for a city, THE Cache SHALL store the response for 10 minutes keyed by the normalised city name.
7. WHILE cached weather data exists for a city, THE Weather_Service SHALL serve results from the Cache without calling the Weather_API.

---

### Requirement 3: Input Validation

**User Story:** As a developer, I want all user inputs validated on the server before any external API call is made, so that the application is robust and does not waste API quota on invalid requests.

#### Acceptance Criteria

1. WHEN a currency conversion request is received, THE API_Controller SHALL validate that the amount is a positive number greater than 0, the source currency code is a 3-letter uppercase string, and the target currency code is a 3-letter uppercase string.
2. WHEN a weather request is received, THE API_Controller SHALL validate that the city name is a non-empty string with a maximum length of 100 characters.
3. IF any input fails validation, THEN THE API_Controller SHALL return a Validation_Error JSON response with HTTP status 422 listing each invalid field and a descriptive message.
4. THE API_Controller SHALL perform all validation before invoking any Service class or making any external API call.

---

### Requirement 4: Alpine.js Frontend — Currency Converter UI

**User Story:** As a portfolio visitor, I want an interactive currency converter UI that updates results without a full page reload, so that the application feels modern and responsive.

#### Acceptance Criteria

1. THE Blade_View at `/currency` SHALL include an Alpine_Component that renders a form with fields for amount, source currency (dropdown), and target currency (dropdown).
2. WHEN a user submits the currency conversion form, THE Alpine_Component SHALL send an asynchronous fetch request to the API_Controller and display the converted result without a full page reload.
3. WHILE the API_Controller request is in progress, THE Alpine_Component SHALL display a loading indicator and disable the submit button.
4. IF the API_Controller returns an error response, THE Alpine_Component SHALL display a user-friendly error message within the form area.
5. THE Blade_View SHALL load Alpine.js from a CDN link included in the Blade layout, requiring no build step.

---

### Requirement 5: Alpine.js Frontend — Weather Dashboard UI

**User Story:** As a portfolio visitor, I want an interactive weather search UI that shows current conditions for any city, so that I can experience a polished full-stack feature.

#### Acceptance Criteria

1. THE Blade_View at `/weather` SHALL include an Alpine_Component that renders a search form with a city name input field.
2. WHEN a user submits the weather search form, THE Alpine_Component SHALL send an asynchronous fetch request to the API_Controller and display the returned weather data without a full page reload.
3. WHILE the API_Controller request is in progress, THE Alpine_Component SHALL display a loading indicator and disable the submit button.
4. IF the API_Controller returns an error response, THE Alpine_Component SHALL display a user-friendly error message within the search area.
5. WHEN weather data is successfully returned, THE Alpine_Component SHALL display temperature (in both Celsius and Fahrenheit), weather condition description, humidity percentage, and wind speed.

---

### Requirement 6: API Rate Limit Handling

**User Story:** As a developer, I want the application to handle external API rate limit errors gracefully, so that users receive a clear message rather than an unhandled exception.

#### Acceptance Criteria

1. IF the Exchange_Rate_API or Weather_API returns a Rate_Limit_Error, THEN THE Currency_Service or Weather_Service SHALL throw a dedicated `RateLimitException`.
2. WHEN a `RateLimitException` is thrown, THE API_Controller SHALL return a JSON error response with HTTP status 429 and a message informing the user to try again later.
3. THE Application SHALL log all Rate_Limit_Errors using Laravel's logging facade at the `warning` level, including the affected service name and timestamp.

---

### Requirement 7: Application Structure and Code Quality

**User Story:** As a developer reviewing this portfolio project, I want the codebase to follow Laravel conventions and best practices, so that I can assess the author's professional coding standards.

#### Acceptance Criteria

1. THE Application SHALL organise currency and weather logic into dedicated Service classes (`CurrencyService`, `WeatherService`) registered in the Laravel service container.
2. THE Application SHALL use Laravel's Http_Client for all outbound HTTP requests and SHALL NOT use `curl` functions or the Guzzle client directly.
3. THE Application SHALL define all external API base URLs and keys as environment variables referenced via `config()` helpers, and SHALL NOT hardcode credentials or URLs in PHP class files.
4. THE Application SHALL include a `.env.example` file listing all required environment variables with placeholder values and inline comments describing each variable.
5. THE Application SHALL include a `README.md` file with setup instructions, a list of required environment variables, and a brief description of each feature.
6. THE Application SHALL use Laravel's built-in Form Request classes for input validation rather than inline `$request->validate()` calls in controller methods.
