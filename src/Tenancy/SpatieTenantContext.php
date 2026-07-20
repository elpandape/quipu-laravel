<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Spatie\Multitenancy\Models\Tenant;

/**
 * The spatie/laravel-multitenancy TenantContext: reads the tenant made current
 * ({@see Tenant::current()}) for the active key and disk, and switches with
 * {@see Tenant::makeCurrent()} / {@see Tenant::forgetCurrent()}. The tenant model
 * class is taken from config('multitenancy.tenant_model'), falling back to
 * spatie's default. Only instantiated when config('quipu.tenancy.driver') selects
 * "spatie" (or "auto" detects it), so an install without the package never loads
 * its classes.
 */
final readonly class SpatieTenantContext implements TenantContext
{
    public function __construct(private Repository $config) {}

    public function currentTenantKey(): ?string
    {
        $current = $this->tenantModelClass()::current();

        return $current === null ? null : TenantKey::toString($current->getKey());
    }

    public function currentTenantStorageDisk(): ?string
    {
        $current = $this->tenantModelClass()::current();

        return $current instanceof ProvidesQuipuEmitter ? $current->quipuStorageDisk() : null;
    }

    public function forTenant(string $tenantKey, Closure $callback): mixed
    {
        $modelClass = $this->tenantModelClass();
        $previous = $modelClass::current();
        $modelClass::query()->findOrFail($tenantKey)->makeCurrent();

        try {
            return $callback();
        } finally {
            if ($previous === null) {
                $modelClass::forgetCurrent();
            } else {
                $previous->makeCurrent();
            }
        }
    }

    /** @return class-string<Tenant> */
    private function tenantModelClass(): string
    {
        $modelClass = $this->config->get('multitenancy.tenant_model');

        return is_string($modelClass) && is_a($modelClass, Tenant::class, true)
            ? $modelClass
            : Tenant::class;
    }
}
