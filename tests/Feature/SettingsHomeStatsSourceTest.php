<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * TAREA 1 del lote 2026-08: el panel debe permitir elegir, por cada slot de
 * la barra de stats del hero, si el valor es manual o calculado
 * (`home_stat_{n}_source`), y guardar el nuevo `company_started_year` que
 * alimenta la fuente "años de operación".
 */
class SettingsHomeStatsSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_a_stat_source_and_the_company_started_year(): void
    {
        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'home_stat_1_source' => 'rating_real',
                'home_stat_2_source' => 'tours_count',
                'company_started_year' => 2016,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('rating_real', Setting::get('home_stat_1_source'));
        $this->assertSame('tours_count', Setting::get('home_stat_2_source'));
        $this->assertSame('2016', (string) Setting::get('company_started_year'));
    }

    public function test_admin_can_save_the_external_reviews_snapshot(): void
    {
        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'home_stat_1_source' => 'rating_external',
                'reviews_external_rating' => '5.0',
                'reviews_external_count' => '255',
                'reviews_external_url' => 'https://www.google.com/maps/place/lima-america-tours',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('rating_external', Setting::get('home_stat_1_source'));
        $this->assertSame('5.0', Setting::get('reviews_external_rating'));
        $this->assertSame('255', Setting::get('reviews_external_count'));
        $this->assertSame('https://www.google.com/maps/place/lima-america-tours', Setting::get('reviews_external_url'));
    }

    public function test_manual_value_typed_before_switching_source_is_not_lost(): void
    {
        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'home_stat_1_value' => '4.95',
                'home_stat_1_source' => 'rating_real',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // El texto manual sigue en la BD aunque la fuente activa sea otra:
        // si la editora vuelve a "manual" no debe encontrar el campo vacío.
        $this->assertSame('4.95', Setting::get('home_stat_1_value'));
        $this->assertSame('rating_real', Setting::get('home_stat_1_source'));
    }
}
