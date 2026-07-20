<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Quipu as QuipuEmitter;
use ElPandaPe\QuipuLaravel\Facades\Quipu;
use ElPandaPe\QuipuLaravel\Tenancy\TenancyNotImplementedException;
use ElPandaPe\QuipuLaravel\Tests\Support\SpatieTenantDouble;
use ElPandaPe\QuipuLaravel\Tests\Support\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('Quipu::forTenant re-resuelve el emisor en vez de reusar el del tenant previo', function (): void {
    config()->set('quipu.tenancy.driver', 'spatie');
    config()->set('quipu.pro', false);
    config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);
    config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
    config()->set('multitenancy.current_tenant_context_key', 'currentTenant');
    config()->set('multitenancy.switch_tenant_tasks', []);
    TenantScope::createSpatieTenantsTable();

    $tenant = new SpatieTenantDouble();
    $tenant->save();
    $tenant->makeCurrent();

    $outer = app(QuipuEmitter::class);

    $inner = null;
    Quipu::forTenant(TenantScope::keyString($tenant), function () use (&$inner): mixed {
        $inner = app(QuipuEmitter::class);

        return null;
    });

    $after = app(QuipuEmitter::class);

    // El singleton se olvida al entrar y al salir de forTenant, así que cada
    // resolución arma un emisor fresco (nunca el del primer tenant, cacheado).
    expect($inner)->not->toBe($outer)
        ->and($after)->not->toBe($inner);

    SpatieTenantDouble::forgetCurrent();
});

it('Quipu::forTenant delega en el TenantContext, cambia de tenant y devuelve el resultado', function (): void {
    config()->set('quipu.tenancy.driver', 'spatie');
    config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);
    config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
    config()->set('multitenancy.current_tenant_context_key', 'currentTenant');
    config()->set('multitenancy.switch_tenant_tasks', []);
    TenantScope::createSpatieTenantsTable();

    $tenant = new SpatieTenantDouble();
    $tenant->save();

    $seen = Quipu::forTenant(TenantScope::keyString($tenant), fn(): mixed => SpatieTenantDouble::current()?->getKey());

    expect($seen)->toBe($tenant->getKey())
        ->and(SpatieTenantDouble::current())->toBeNull();
});

it('Quipu::forTenant con el driver "none" lanza un error claro', function (): void {
    config()->set('quipu.tenancy.driver', 'none');

    expect(fn(): mixed => Quipu::forTenant('x', fn(): mixed => null))
        ->toThrow(TenancyNotImplementedException::class);
});
