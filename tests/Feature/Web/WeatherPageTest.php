<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeatherPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_weather_page(): void
    {
        $response = $this->get(route('weather.index'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('weather/index')
                    ->where(
                        key: 'locale',
                        expected: 'en',
                    )
                    ->where(
                        key: 'copy.title',
                        expected: __('weather.page.title'),
                    )
                    ->where(
                        key: 'copy.cityLabel',
                        expected: __('weather.page.city_label'),
                    )
                    ->where(
                        key: 'copy.networkError',
                        expected: __('weather.page.network_error'),
                    )
                    ->has('copy.metrics')
                    ->has('copy.units'),
            );
    }

    public function test_authenticated_user_can_view_weather_page(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('weather.index'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page->component('weather/index'),
            );

        $this->assertAuthenticatedAs($user);
    }
}
