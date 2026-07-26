<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for CheckoutController::paypalCaptureOrder().
 *
 * Extracted out of the inline $request->validate() call that used to live in
 * the controller (docs/pagos/PLAN-PASARELAS.md §2.3/§10.1: "separar
 * validación por método Culqi/PayPal" + project rule "validación en Form
 * Requests, no en el controller").
 */
class PayPalCaptureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize phone before validation — same rule as ProcessPaymentRequest,
     * so "+51 999 888 777" and "999888777" are both accepted.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('customer_phone')) {
            $this->merge([
                'customer_phone' => preg_replace('/\s+/', '', $this->input('customer_phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'orderID' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?\d{7,15}$/'],
            'travel_date' => ['required', 'date', 'after:today'],
            'pickup_point' => ['nullable', 'string', 'max:100'],
            'pickup_detail' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Ingresa un teléfono válido con su prefijo de país.',
            'travel_date.after' => 'La fecha de viaje debe ser posterior a hoy.',
        ];
    }

    public function attributes(): array
    {
        return [
            'orderID' => 'orden de PayPal',
            'customer_name' => 'nombre',
            'customer_email' => 'correo electrónico',
            'customer_phone' => 'teléfono',
            'travel_date' => 'fecha de viaje',
            'pickup_point' => 'punto de recogida',
            'pickup_detail' => 'detalle de recogida',
        ];
    }
}
