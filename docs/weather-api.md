# Weather API verification

Set the required OpenWeatherMap credential in `.env`:

```dotenv
OPENWEATHERMAP_API_KEY=your-openweathermap-api-key
```

After starting the application locally, verify the endpoint with:

```bash
curl --get 'http://127.0.0.1/api/weather' --data-urlencode 'city=Omsk'
```

When using `php artisan serve` on its default port, use
`http://127.0.0.1:8000/api/weather` instead.
