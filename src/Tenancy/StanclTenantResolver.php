<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Illuminate\Contracts\Config\Repository;
use Stancl\Tenancy\Tenancy;

/**
 * Resolves the active emitter from stancl/tenancy: the tenant stancl has
 * initialised for the current request/job ({@see Tenancy::$tenant}) must
 * implement {@see ProvidesQuipuEmitter}. Only instantiated when
 * config('quipu.tenancy.driver') selects "stancl" (or "auto" detects it), so a
 * Lite/Pro install without stancl/tenancy never loads its classes.
 */
final class StanclTenantResolver extends AbstractTenantEmitterConfigResolver
{
    public function __construct(Repository $config, private readonly Tenancy $tenancy)
    {
        parent::__construct($config);
    }

    protected function currentTenant(): ProvidesQuipuEmitter
    {
        // Tenancy::$tenant is non-null exactly while a tenant is initialised.
        return $this->requireEmitterTenant($this->tenancy->tenant, 'stancl');
    }
}
