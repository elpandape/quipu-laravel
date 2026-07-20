<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Closure;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Tenancy;

/**
 * The stancl/tenancy TenantContext: reads the tenant stancl has initialised for
 * the current request/job ({@see Tenancy::$tenant}) for the active key and disk,
 * and switches with {@see Tenancy::initialize()} / {@see Tenancy::end()}. Only
 * instantiated when config('quipu.tenancy.driver') selects "stancl" (or "auto"
 * detects it), so an install without stancl/tenancy never loads its classes.
 */
final readonly class StanclTenantContext implements TenantContext
{
    public function __construct(private Tenancy $tenancy) {}

    public function currentTenantKey(): ?string
    {
        $tenant = $this->tenancy->tenant;

        return $tenant instanceof Tenant ? TenantKey::toString($tenant->getTenantKey()) : null;
    }

    public function currentTenantStorageDisk(): ?string
    {
        $tenant = $this->tenancy->tenant;

        return $tenant instanceof ProvidesQuipuEmitter ? $tenant->quipuStorageDisk() : null;
    }

    public function forTenant(string $tenantKey, Closure $callback): mixed
    {
        // stancl's initialize() accepts a key and resolves the tenant itself.
        $previous = $this->tenancy->tenant;
        $this->tenancy->initialize($tenantKey);

        try {
            return $callback();
        } finally {
            if ($previous instanceof Tenant) {
                $this->tenancy->initialize($previous);
            } else {
                $this->tenancy->end();
            }
        }
    }
}
