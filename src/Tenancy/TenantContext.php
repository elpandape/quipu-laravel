<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Closure;

/**
 * The active tenant, as seen by the runtime: the key to scope persistence and
 * correlativos by, the tenant's own storage disk (when it defines one), and the
 * ability to run a callback with a given tenant made current. Resolved per
 * config('quipu.tenancy.driver') by the {@see TenantContextFactory}:
 *   "none"   — mono-tenant: no active tenant (null key/disk), forTenant unsupported.
 *   "stancl" — reads stancl/tenancy's initialised tenant.
 *   "spatie" — reads spatie/laravel-multitenancy's current tenant.
 * Only the active driver's package classes are ever referenced, so a Lite/Pro
 * install without a tenancy package never loads them.
 */
interface TenantContext
{
    /** Key of the active tenant, or null when none is in scope (mono-tenant). */
    public function currentTenantKey(): ?string;

    /**
     * The active tenant's own storage disk, or null to fall back to the global
     * config('quipu.storage.disk'). Non-null only when the current tenant model
     * implements {@see ProvidesQuipuEmitter} and defines a disk.
     */
    public function currentTenantStorageDisk(): ?string;

    /**
     * Run $callback with the tenant identified by $tenantKey made current, using
     * the driver's own tenancy API, and restore the previously active tenant
     * afterwards (even on failure). The mono-tenant "none" driver has no tenant to
     * switch to and throws a clear {@see TenancyNotImplementedException}.
     *
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public function forTenant(string $tenantKey, Closure $callback): mixed;
}
