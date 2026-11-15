<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeatherRequest;
use App\Http\Resources\WeatherResource;
use App\Services\WeatherService;

class WeatherController extends Controller
{
    public function __construct(
        private readonly WeatherService $weatherService,
    ) {}

    public function search(WeatherRequest $request): WeatherResource
    {
        /** @var string $city */
        $city = $request->validated('city');

        return WeatherResource::make(
            $this->weatherService->getCurrentWeather($city),
        );
    }
}
