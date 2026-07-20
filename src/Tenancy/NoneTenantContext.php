<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Closure;

/**
 * The mono-tenant ("none" driver) TenantContext: there is no active tenant, so
 * persistence and correlativos stay unscoped (null key) and storage uses the
 * global disk (null). {@see forTenant()} has no tenant to switch to and fails
 * loud, with the remedy.
 */
final class NoneTenantContext implements TenantContext
{
    public function currentTenantKey(): ?string
    {
        return null;
    }

    public function currentTenantStorageDisk(): ?string
    {
        return null;
    }

    public function forTenant(string $tenantKey, Closure $callback): mixed
    {
        throw TenancyNotImplementedException::forTenantWithoutDriver();
    }
}
