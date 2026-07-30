<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private string $secretKey;

    private string $publicKey;

    private string $webhookSecret;

    private string $apiUrl;

    public function __construct()
    {
        // Administrables desde el panel (Configuración → Pagos), igual que
        // PayPal; el .env queda como respaldo. Antes solo se leían del .env, lo
        // que obligaba a un deploy para cargar las claves de prueba del cliente.
        // Se lee con try/catch porque este servicio se resuelve también en
        // contextos sin BD (comandos de instalación, tests unitarios).
        $fromDb = function (string $key): string {
            try {
                return (string) \App\Models\Setting::get($key, '');
            } catch (\Throwable $e) {
                return '';
            }
        };

        $this->secretKey = $fromDb('culqi_secret_key') ?: (string) config('services.culqi.secret_key', '');
        $this->publicKey = $fromDb('culqi_public_key') ?: (string) config('services.culqi.public_key', '');
        $this->webhookSecret = (string) config('services.culqi.webhook_secret', '');
        $this->apiUrl = rtrim((string) config('services.culqi.api_url', 'https://api.culqi.com/v2'), '/');
    }

    /**
     * Llave pública de Culqi (viaja al navegador para tokenizar la tarjeta:
     * es pública a propósito). La vista de pago la pedía con
     * config('services.culqi.public_key'), que ignoraba la del panel — con las
     * claves cargadas en Configuración → Pagos, el formulario se quedaba con
     * la del .env (o vacío) y la tokenización fallaba sin explicación.
     */
    public function publicKey(): string
    {
        return $this->publicKey;
    }

    /** ¿Hay llave pública cargada? Sin ella no se puede cobrar con tarjeta. */
    public function isConfigured(): bool
    {
        return $this->publicKey !== '' && ! str_contains($this->publicKey, 'REPLACE_ME');
    }

    /**
     * Create a charge in Culqi.
     *
     * @param  array{
     *   amount: int,
     *   currency: string,
     *   email: string,
     *   source_id: string,
     *   antifraud_details?: array,
     *   metadata?: array
     * } $data
     *
     * @throws \RuntimeException on non-2xx response
     */
    public function createCharge(array $data): array
    {
        try {
            // Guarda anti-cobro-real: aborta ANTES de golpear la API de Culqi
            // si se detectan credenciales LIVE fuera de un contexto productivo
            // autorizado (App\Services\PaymentGuard).
            PaymentGuard::assertChargeAllowed('culqi', $this->secretKey);

            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->post("{$this->apiUrl}/charges", $data);

            if ($response->failed()) {
                Log::error('culqi.create_charge.failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'metadata' => $data['metadata'] ?? [],
                ]);

                throw new \RuntimeException(
                    'Culqi charge failed: '.($response->json('user_message') ?? $response->body())
                );
            }

            Log::info('culqi.create_charge.success', [
                'charge_id' => $response->json('id'),
                'metadata' => $data['metadata'] ?? [],
            ]);

            return $response->json();
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('culqi.create_charge.exception', [
                'message' => $e->getMessage(),
                'metadata' => $data['metadata'] ?? [],
            ]);

            throw new \RuntimeException('Culqi connection error: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve a charge by ID.
     *
     * @throws \RuntimeException on non-2xx response
     */
    public function retrieveCharge(string $id): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->get("{$this->apiUrl}/charges/{$id}");

            if ($response->failed()) {
                Log::error('culqi.retrieve_charge.failed', [
                    'charge_id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException('Culqi retrieve charge failed: '.$response->body());
            }

            return $response->json();
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('culqi.retrieve_charge.exception', [
                'charge_id' => $id,
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Culqi connection error: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Verify that an incoming webhook payload matches the expected HMAC signature.
     *
     * Culqi signs the raw JSON body with HMAC-SHA256 using the webhook secret.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('culqi.webhook.secret_not_configured');

            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }
}
