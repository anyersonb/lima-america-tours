<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las pantallas del panel que muestran dinero siguen abriendo después del
 * cambio de moneda.
 *
 * Motivo: el paso a USD reemplazó los prefijos escritos a mano ('S/') por
 * `Money::prefix(Money::site())` en cuatro recursos y en la página de
 * Configuración. Eso se ejecuta al construir el formulario/tabla, no al
 * arrancar la app: un error ahí NO lo ve ningún test de front ni el deploy
 * (que solo comprueba la home pública) — se descubre cuando la clienta abre
 * Reservas y recibe un 500. Estas pantallas no estaban cubiertas.
 */
class AdminPanelRendersWithSiteCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@limaamericatours.com']);
    }

    public function test_money_screens_of_the_panel_open(): void
    {
        $tour = Tour::factory()->create(['price' => 250.00, 'is_published' => true]);

        Booking::create([
            'tour_id' => $tour->id,
            'tour_title_snapshot' => $tour->title_es,
            'customer_name' => 'Ana López',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '987000001',
            'travel_date' => now()->addDays(10)->format('Y-m-d'),
            'adults' => 2,
            'children' => 0,
            'unit_price' => 250.00,
            'total_price' => 500.00,
            'currency' => Money::site(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'culqi',
            'locale' => 'es',
        ]);

        $this->actingAs($this->admin());

        foreach ([
            'filament.admin.resources.bookings.index',
            'filament.admin.resources.bookings.create',
            'filament.admin.resources.offers.index',
            'filament.admin.resources.abandoned-carts.index',
            'filament.admin.pages.settings',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }
}
