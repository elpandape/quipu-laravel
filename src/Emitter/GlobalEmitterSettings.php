<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use Illuminate\Contracts\Config\Repository;

/**
 * Reads the emitter settings that are global to the whole application rather
 * than per-tenant: the target SUNAT environment, the optional billService
 * endpoint override and TLS verification. Shared by the single-emitter
 * ConfigEmitterConfigResolver and the multi-tenant resolvers — the latter take
 * the issuer identity and certificate from the tenant but still honour these
 * process-wide settings.
 */
final readonly class GlobalEmitterSettings
{
    public function __construct(private Repository $config) {}

    public function environment(): Environment
    {
        $raw = $this->string('quipu.environment');

        return Environment::tryFrom($raw)
            ?? throw new EmitterConfigException(sprintf('El entorno "%s" no es válido; use "beta" o "produccion".', $raw));
    }

    /** billService override URL, or null to use the environment default. */
    public function billServiceEndpointOverride(): ?string
    {
        $value = $this->config->get('quipu.endpoints.bill_service');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function verifyTls(): bool
    {
        $value = $this->config->get('quipu.verify_tls');

        return is_bool($value) ? $value : true;
    }

    /**
     * Default IGV rate (percentage points) for taxed lines. The same across every
     * document type; a tenant may override it. A negative value is rejected later
     * by the builder's own withIgvRate() guard.
     */
    public function igvRate(): float
    {
        $value = $this->config->get('quipu.igv_rate');
        if (!is_numeric($value)) {
            throw new EmitterConfigException(sprintf('La tasa de IGV (quipu.igv_rate) debe ser numérica; se recibió %s.', get_debug_type($value)));
        }

        return (float) $value;
    }

    private function string(string $key): string
    {
        $value = $this->config->get($key);

        return is_string($value) ? $value : '';
    }
}
