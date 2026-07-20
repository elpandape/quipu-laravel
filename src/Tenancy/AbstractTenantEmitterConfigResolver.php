<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use ElPandaPe\QuipuLaravel\Emitter\EmitterConfig;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Emitter\GlobalEmitterSettings;
use Illuminate\Contracts\Config\Repository;

/**
 * Shared machinery for the multi-tenant emitter resolvers: reads the active
 * tenant from its tenancy package (each subclass knows how), requires it to
 * implement {@see ProvidesQuipuEmitter}, and assembles the EmitterConfig from
 * it — the issuer identity, SOL credentials and the tenant's own decrypted
 * certificate PEM — combined with the process-wide settings (environment,
 * endpoint override, TLS) shared with the single-emitter path.
 */
abstract class AbstractTenantEmitterConfigResolver implements EmitterConfigResolver
{
    public function __construct(protected readonly Repository $config) {}

    final public function resolve(): EmitterConfig
    {
        $tenant = $this->currentTenant();
        $global = new GlobalEmitterSettings($this->config);

        return new EmitterConfig(
            ruc: $tenant->quipuRuc(),
            legalName: $tenant->quipuLegalName(),
            tradeName: $tenant->quipuTradeName(),
            solUser: $tenant->quipuSolUser(),
            solPass: $tenant->quipuSolPass(),
            certificatePassphrase: $tenant->quipuCertificatePassphrase(),
            environment: $global->environment(),
            billServiceEndpointOverride: $global->billServiceEndpointOverride(),
            verifyTls: $global->verifyTls(),
            // The tenant carries its own already-decrypted PEM, so the emitter
            // signs with the tenant certificate rather than the global one.
            certificatePem: $tenant->quipuCertificatePem(),
            // The tenant may run under a different IGV regime (e.g. 8% MYPE); fall
            // back to the global rate when it does not declare its own.
            igvRate: $tenant->quipuIgvRate() ?? $global->igvRate(),
        );
    }

    /**
     * The active tenant as a {@see ProvidesQuipuEmitter}. Implementations read
     * it from their tenancy package and throw a
     * {@see TenantEmitterResolutionException} when no tenant is in scope or it
     * does not implement the contract.
     */
    abstract protected function currentTenant(): ProvidesQuipuEmitter;

    /**
     * Narrows the raw current tenant to a {@see ProvidesQuipuEmitter}, or throws
     * a clear error. Shared by the driver subclasses.
     */
    final protected function requireEmitterTenant(?object $tenant, string $driver): ProvidesQuipuEmitter
    {
        if ($tenant === null) {
            throw TenantEmitterResolutionException::noActiveTenant($driver);
        }

        if (!$tenant instanceof ProvidesQuipuEmitter) {
            throw TenantEmitterResolutionException::doesNotProvideEmitter($driver, $tenant::class);
        }

        return $tenant;
    }
}
