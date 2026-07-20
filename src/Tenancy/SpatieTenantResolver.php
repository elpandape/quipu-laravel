<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Tenant;

/**
 * Resolves the active emitter from spatie/laravel-multitenancy: the tenant made
 * current for the request/job ({@see IsTenant::current()}) must implement
 * {@see ProvidesQuipuEmitter}. The tenant model class is taken from
 * config('multitenancy.tenant_model'), falling back to spatie's default. Only
 * instantiated when config('quipu.tenancy.driver') selects "spatie" (or "auto"
 * detects it), so an install without spatie/laravel-multitenancy never loads
 * its classes.
 */
final class SpatieTenantResolver extends AbstractTenantEmitterConfigResolver
{
    protected function currentTenant(): ProvidesQuipuEmitter
    {
        return $this->requireEmitterTenant($this->tenantModelClass()::current(), 'spatie');
    }

    /** @return class-string<IsTenant> */
    private function tenantModelClass(): string
    {
        $modelClass = $this->config->get('multitenancy.tenant_model');

        return is_string($modelClass) && is_a($modelClass, IsTenant::class, true)
            ? $modelClass
            : Tenant::class;
    }
}
