<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize phone before validation: strip all spaces so both
     * "+51 999 888 777" and "+51999888777" and "999888777" are accepted.
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
            // payment_timing controls whether the charge is processed now or deferred
            'payment_timing' => ['required', 'string', 'in:now,later'],

            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            // Acepta número internacional con prefijo de país (+51, +1, etc.) tras quitar espacios
            'customer_phone' => ['required', 'string', 'regex:/^\+?\d{7,15}$/'],
            'travel_date' => ['required', 'date', 'after:today'],

            // Pickup information (optional but captured when provided)
            'pickup_point' => ['nullable', 'string', 'max:100'],
            'pickup_detail' => ['nullable', 'string', 'max:255'],

            // Only required when the customer is paying now with a card
            'culqi_token' => ['nullable', 'string', 'required_if:payment_timing,now'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_timing.required' => 'Debe indicar si pagará ahora o después.',
            'payment_timing.in' => 'Opción de pago no válida.',
            'customer_phone.regex' => 'Ingresa un teléfono válido con su prefijo de país.',
            'travel_date.after' => 'La fecha de viaje debe ser posterior a hoy.',
            'culqi_token.required_if' => 'No se recibió el token de pago. Intente nuevamente.',
        ];
    }

    /**
     * docs/qa/F7-personas.md §g #7 / §e: sin este mapeo, los mensajes de
     * validación por defecto de Laravel usan el nombre técnico del campo
     * ("El campo customer name es obligatorio"), porque `messages()` solo
     * cubre las reglas explícitas (regex, after, required_if) y deja que
     * `required`/`email` caigan al genérico de lang/es/validation.php con
     * :attribute = nombre del campo del formulario. Mapear cada campo a su
     * etiqueta en español (la misma que ya ve el cliente en el formulario)
     * arregla los mensajes sin duplicar una regla por campo.
     */
    public function attributes(): array
    {
        return [
            'payment_timing' => 'forma de pago',
            'customer_name' => 'nombre',
            'customer_email' => 'correo electrónico',
            'customer_phone' => 'teléfono',
            'travel_date' => 'fecha de viaje',
            'pickup_point' => 'punto de recogida',
            'pickup_detail' => 'detalle de recogida',
            'culqi_token' => 'token de pago',
        ];
    }
}
