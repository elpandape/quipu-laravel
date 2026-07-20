<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * Selects the active {@see TenantContext} from config('quipu.tenancy.driver'),
 * the sibling of {@see TenancyEmitterResolverFactory}:
 *   "none"    — {@see NoneTenantContext} (mono-tenant, the default).
 *   "stancl"  — {@see StanclTenantContext} (stancl/tenancy).
 *   "spatie"  — {@see SpatieTenantContext} (spatie/laravel-multitenancy).
 *   "auto"    — whichever of the two packages is installed.
 *   <class>   — a custom EmitterConfigResolver driver owns its own scoping, so
 *               the runtime stays mono-tenant here ({@see NoneTenantContext}); the
 *               app can bind its own TenantContext if it wants tenant scoping.
 * "auto" with neither package installed throws a clear
 * {@see TenancyNotImplementedException}. Package availability is injected (the
 * service provider computes it from class_exists) so the selection is a pure,
 * fully testable decision.
 */
final readonly class TenantContextFactory
{
    public function __construct(
        private Container $container,
        private Repository $config,
        private bool $stanclAvailable,
        private bool $spatieAvailable,
    ) {}

    public function make(): TenantContext
    {
        return match ($this->driver()) {
            'stancl' => $this->container->make(StanclTenantContext::class),
            'spatie' => $this->container->make(SpatieTenantContext::class),
            'auto' => $this->autoContext(),
            default => $this->container->make(NoneTenantContext::class),
        };
    }

    private function driver(): string
    {
        $driver = $this->config->get('quipu.tenancy.driver');

        return is_string($driver) && $driver !== '' ? $driver : 'none';
    }

    private function autoContext(): TenantContext
    {
        if ($this->stanclAvailable) {
            return $this->container->make(StanclTenantContext::class);
        }

        if ($this->spatieAvailable) {
            return $this->container->make(SpatieTenantContext::class);
        }

        throw TenancyNotImplementedException::autoWithoutPackage();
    }
}
