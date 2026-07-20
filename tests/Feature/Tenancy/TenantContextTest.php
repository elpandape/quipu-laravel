<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tenancy\NoneTenantContext;
use ElPandaPe\QuipuLaravel\Tenancy\SpatieTenantContext;
use ElPandaPe\QuipuLaravel\Tenancy\StanclTenantContext;
use ElPandaPe\QuipuLaravel\Tests\Support\SpatieTenantDouble;
use ElPandaPe\QuipuLaravel\Tests\Support\StanclTenantDouble;
use Illuminate\Contracts\Config\Repository;
use Stancl\Tenancy\Tenancy;

it('el contexto "none" no tiene tenant activo ni disco', function (): void {
    $context = new NoneTenantContext();

    expect($context->currentTenantKey())->toBeNull()
        ->and($context->currentTenantStorageDisk())->toBeNull();
});

it('el contexto stancl expone la clave y el disco del tenant inicializado', function (): void {
    $tenancy = new Tenancy();
    $tenancy->tenant = new StanclTenantDouble();
    $tenancy->initialized = true;

    $context = new StanclTenantContext($tenancy);

    expect($context->currentTenantKey())->toBe('stancl-tenant')
        ->and($context->currentTenantStorageDisk())->toBe('stancl-tenant-disk');
});

it('el contexto stancl sin tenant inicializado no expone clave ni disco', function (): void {
    $context = new StanclTenantContext(new Tenancy());

    expect($context->currentTenantKey())->toBeNull()
        ->and($context->currentTenantStorageDisk())->toBeNull();
});

it('el contexto spatie expone la clave y el disco del tenant actual', function (): void {
    config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
    config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);

    $tenant = new SpatieTenantDouble();
    $tenant->id = 77;
    app()->instance('currentTenant', $tenant);

    $context = new SpatieTenantContext(app(Repository::class));

    expect($context->currentTenantKey())->toBe('77')
        ->and($context->currentTenantStorageDisk())->toBe('spatie-tenant-disk');
});

it('el contexto spatie sin tenant actual no expone clave ni disco', function (): void {
    config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
    config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);

    $context = new SpatieTenantContext(app(Repository::class));

    expect($context->currentTenantKey())->toBeNull()
        ->and($context->currentTenantStorageDisk())->toBeNull();
});
