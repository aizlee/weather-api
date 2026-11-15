<?php

namespace App\Contracts;

use App\DTO\OpenWeatherMapCurrentWeatherDTO;
use App\DTO\OpenWeatherMapLocationDTO;

interface OpenWeatherMapClientInterface
{
    public function geocode(string $city): ?OpenWeatherMapLocationDTO;

    public function currentWeather(float $latitude, float $longitude): OpenWeatherMapCurrentWeatherDTO;
}
