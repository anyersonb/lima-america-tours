<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cambiar "Moneda del sitio" en el panel re-etiqueta los tours que estaban en
 * la moneda anterior — sin convertir importes.
 *
 * Hallazgo de QA (2026-07-29): el Setting era editable sin nada que lo
 * mantuviera alineado con `tours.currency`. Al poner el sitio en soles con los
 * tours etiquetados en dólares, el front mostraba "S/" pero la guarda del
 * checkout rechazaba TODOS los tours: el cobro en línea quedaba muerto para el
 * catálogo completo, con un mensaje genérico y sin explicación en pantalla. Un
 * clic en un select del panel no puede apagar el checkout en silencio.
 */
class SiteCurrencyRealignsToursTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@limaamericatours.com']);
    }

    private function saveCurrency(string $currency): void
    {
        Livewire::actingAs($this->admin())
            ->test(SettingsPage::class)
            ->set('data.site_currency', $currency)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_changing_the_site_currency_retags_the_tours(): void
    {
        Setting::set('site_currency', 'USD');

        $tour = Tour::factory()->create(['price' => 720.00, 'currency' => 'USD', 'is_published' => true]);

        $this->saveCurrency('PEN');

        // Re-etiquetado, NO convertido: el número es el mismo.
        $tour->refresh();
        $this->assertSame('PEN', $tour->currency);
        $this->assertEquals(720.00, (float) $tour->price);
        $this->assertSame('PEN', Setting::get('site_currency'));
    }

    public function test_a_tour_in_a_third_currency_is_left_alone(): void
    {
        Setting::set('site_currency', 'USD');

        $euroTour = Tour::factory()->create(['price' => 100.00, 'currency' => 'EUR', 'is_published' => true]);

        $this->saveCurrency('PEN');

        // Se queda en EUR a propósito: el checkout debe seguir rechazándolo en
        // vez de que un cambio de moneda lo arrastre a una que nadie revisó.
        $this->assertSame('EUR', $euroTour->refresh()->currency);
    }

    /**
     * El efecto que de verdad importa: tras cambiar la moneda, el checkout
     * sigue aceptando los tours del catálogo. Antes de este realineado, la
     * guarda los rechazaba todos.
     */
    public function test_checkout_still_accepts_the_catalogue_after_a_currency_change(): void
    {
        Setting::set('site_currency', 'USD');

        $tour = Tour::factory()->create(['price' => 300.00, 'currency' => 'USD', 'is_published' => true]);

        $this->saveCurrency('PEN');

        $cart = app(CartService::class);
        $cart->add($tour->refresh(), 2, 0, now()->addDays(10)->format('Y-m-d'));

        $this->assertTrue(
            $cart->isSiteCurrencyOnly(),
            'Tras cambiar la moneda del sitio, la guarda del checkout rechaza todo el catálogo.'
        );
    }
}
