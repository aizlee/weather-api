<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class WeatherController extends Controller
{
    public function index(): Response
    {
        return Inertia::render(
            component: 'weather/index',
            props: [
                'locale' => app()->getLocale(),
                'copy' => [
                    'metaTitle' => __('weather.page.meta_title'),
                    'home' => __('weather.page.home'),
                    'eyebrow' => __('weather.page.eyebrow'),
                    'title' => __('weather.page.title'),
                    'description' => __('weather.page.description'),
                    'cityLabel' => __('weather.page.city_label'),
                    'cityPlaceholder' => __('weather.page.city_placeholder'),
                    'show' => __('weather.page.show'),
                    'loading' => __('weather.page.loading'),
                    'emptyTitle' => __('weather.page.empty_title'),
                    'emptyDescription' => __('weather.page.empty_description'),
                    'notFoundTitle' => __('weather.page.not_found_title'),
                    'unavailableTitle' => __('weather.page.unavailable_title'),
                    'networkTitle' => __('weather.page.network_title'),
                    'networkError' => __('weather.page.network_error'),
                    'requestError' => __('weather.page.request_error'),
                    'resultAnnouncement' => __('weather.page.result_announcement'),
                    'unavailable' => __('weather.page.unavailable_value'),
                    'metrics' => [
                        'feelsLike' => __('weather.page.metrics.feels_like'),
                        'conditions' => __('weather.page.metrics.conditions'),
                        'humidity' => __('weather.page.metrics.humidity'),
                        'pressure' => __('weather.page.metrics.pressure'),
                        'wind' => __('weather.page.metrics.wind'),
                        'coordinates' => __('weather.page.metrics.coordinates'),
                        'observedAt' => __('weather.page.metrics.observed_at'),
                    ],
                    'units' => [
                        'temperature' => __('weather.page.units.temperature'),
                        'humidity' => __('weather.page.units.humidity'),
                        'pressure' => __('weather.page.units.pressure'),
                        'windSpeed' => __('weather.page.units.wind_speed'),
                        'windDirection' => __('weather.page.units.wind_direction'),
                    ],
                ],
            ],
        );
    }
}
