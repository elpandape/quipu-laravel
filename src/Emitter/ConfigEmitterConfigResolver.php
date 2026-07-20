<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

use Illuminate\Contracts\Config\Repository;

/**
 * Default single-emitter resolver: reads the emitter, certificate, environment
 * and endpoint overrides from the "quipu" config repository. The environment,
 * endpoint override and TLS settings come from the shared GlobalEmitterSettings
 * so the multi-tenant resolvers honour them identically.
 */
final readonly class ConfigEmitterConfigResolver implements EmitterConfigResolver
{
    public function __construct(private Repository $config) {}

    public function resolve(): EmitterConfig
    {
        $ruc = $this->string('quipu.emisor.ruc');
        $global = new GlobalEmitterSettings($this->config);

        return new EmitterConfig(
            ruc: $ruc,
            // The issuer's razón social; falls back to the RUC when unset so the
            // Company is never assembled with an empty legal name (retrocompat).
            legalName: $this->nullableString('quipu.emisor.legal_name') ?? $ruc,
            tradeName: $this->nullableString('quipu.emisor.trade_name'),
            solUser: $this->string('quipu.emisor.sol_user'),
            solPass: $this->string('quipu.emisor.sol_pass'),
            certificatePassphrase: $this->nullableString('quipu.certificate.passphrase'),
            environment: $global->environment(),
            billServiceEndpointOverride: $global->billServiceEndpointOverride(),
            verifyTls: $global->verifyTls(),
            igvRate: $global->igvRate(),
        );
    }

    private function string(string $key): string
    {
        $value = $this->config->get($key);

        return is_string($value) ? $value : '';
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->config->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
