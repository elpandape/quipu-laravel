<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tenancy\TenancyNotImplementedException;
use ElPandaPe\QuipuLaravel\Tenancy\TenantContext;
use ElPandaPe\QuipuLaravel\Tests\Support\SpatieTenantDouble;
use ElPandaPe\QuipuLaravel\Tests\Support\StanclDbTenant;
use ElPandaPe\QuipuLaravel\Tests\Support\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Tenancy;

uses(RefreshDatabase::class);

it('con stancl ejecuta el callback con el tenant activo y restaura la ausencia previa', function (): void {
    config()->set('quipu.tenancy.driver', 'stancl');
    config()->set('tenancy.tenant_model', StanclDbTenant::class);
    TenantScope::createStanclTenantsTable();
    StanclDbTenant::query()->create(['id' => 'acme']);

    $tenancy = new Tenancy();
    app()->instance(Tenancy::class, $tenancy);

    $captured = null;
    $result = app(TenantContext::class)->forTenant('acme', function () use ($tenancy, &$captured): string {
        $tenant = $tenancy->tenant;
        $captured = $tenant instanceof Tenant ? $tenant->getTenantKey() : null;

        return 'ok';
    });

    expect($result)->toBe('ok')
        ->and($captured)->toBe('acme')
        ->and($tenancy->tenant)->toBeNull();
});

it('con stancl restaura el tenant previamente inicializado', function (): void {
    config()->set('quipu.tenancy.driver', 'stancl');
    config()->set('tenancy.tenant_model', StanclDbTenant::class);
    TenantScope::createStanclTenantsTable();
    StanclDbTenant::query()->create(['id' => 'acme']);

    $previous = new StanclDbTenant(['id' => 'old']);
    $tenancy = new Tenancy();
    $tenancy->initialize($previous);
    app()->instance(Tenancy::class, $tenancy);

    app(TenantContext::class)->forTenant('acme', function (): void {});

    $tenant = $tenancy->tenant;
    $key = $tenant instanceof Tenant ? $tenant->getTenantKey() : null;
    expect($key)->toBe('old');
});

it('con spatie ejecuta el callback con el tenant actual y restaura la ausencia previa', function (): void {
    config()->set('quipu.tenancy.driver', 'spatie');
    config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);
    config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
    config()->set('multitenancy.current_tenant_context_key', 'currentTenant');
    config()->set('multitenancy.switch_tenant_tasks', []);
    TenantScope::createSpatieTenantsTable();

    $tenant = new SpatieTenantDouble();
    $tenant->save();

    $captured = null;
    app(TenantContext::class)->forTenant(TenantScope::keyString($tenant), function () use (&$captured): void {
        $captured = SpatieTenantDouble::current()?->getKey();
    });

    expect($captured)->toBe($tenant->getKey())
        ->and(SpatieTenantDouble::current())->toBeNull();
});

it('con spatie restaura el tenant que estaba activo', function (): void {
    config()->set('quipu.tenancy.driver', 'spatie');
    config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);
    config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
    config()->set('multitenancy.current_tenant_context_key', 'currentTenant');
    config()->set('multitenancy.switch_tenant_tasks', []);
    TenantScope::createSpatieTenantsTable();

    $previous = new SpatieTenantDouble();
    $previous->save();
    $target = new SpatieTenantDouble();
    $target->save();
    $previous->makeCurrent();

    app(TenantContext::class)->forTenant(TenantScope::keyString($target), function (): void {});

    expect(SpatieTenantDouble::current()?->getKey())->toBe($previous->getKey());
});

it('con el driver "none" forTenant lanza un error claro (solo multi-tenant)', function (): void {
    config()->set('quipu.tenancy.driver', 'none');

    expect(fn(): mixed => app(TenantContext::class)->forTenant('x', fn(): mixed => null))
        ->toThrow(TenancyNotImplementedException::class);
});
