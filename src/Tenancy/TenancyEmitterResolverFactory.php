<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use ElPandaPe\QuipuLaravel\Emitter\ConfigEmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * Selects the active-emitter resolver from config('quipu.tenancy.driver'):
 *   "none"    — the single-emitter ConfigEmitterConfigResolver (mono-tenant).
 *   "stancl"  — StanclTenantResolver (stancl/tenancy).
 *   "spatie"  — SpatieTenantResolver (spatie/laravel-multitenancy).
 *   "auto"    — whichever of the two packages is installed.
 *   <class>   — a custom EmitterConfigResolver resolved from the container.
 * An unknown driver, or "auto" with neither package installed, throws a clear
 * {@see TenancyNotImplementedException}. Package availability is injected (the
 * service provider computes it from class_exists) so the selection is a pure,
 * fully testable decision.
 */
final readonly class TenancyEmitterResolverFactory
{
    public function __construct(
        private Container $container,
        private Repository $config,
        private bool $stanclAvailable,
        private bool $spatieAvailable,
    ) {}

    public function make(): EmitterConfigResolver
    {
        $driver = $this->driver();

        return match ($driver) {
            'none' => $this->container->make(ConfigEmitterConfigResolver::class),
            'stancl' => $this->container->make(StanclTenantResolver::class),
            'spatie' => $this->container->make(SpatieTenantResolver::class),
            'auto' => $this->autoResolver(),
            default => $this->customResolver($driver),
        };
    }

    private function driver(): string
    {
        $driver = $this->config->get('quipu.tenancy.driver');

        return is_string($driver) && $driver !== '' ? $driver : 'none';
    }

    private function autoResolver(): EmitterConfigResolver
    {
        if ($this->stanclAvailable) {
            return $this->container->make(StanclTenantResolver::class);
        }

        if ($this->spatieAvailable) {
            return $this->container->make(SpatieTenantResolver::class);
        }

        throw TenancyNotImplementedException::autoWithoutPackage();
    }

    private function customResolver(string $driver): EmitterConfigResolver
    {
        if (!class_exists($driver) || !is_a($driver, EmitterConfigResolver::class, true)) {
            throw TenancyNotImplementedException::forDriver($driver);
        }

        // $driver is narrowed to class-string<EmitterConfigResolver> above, so
        // the container returns the resolver already typed.
        return $this->container->make($driver);
    }
}
