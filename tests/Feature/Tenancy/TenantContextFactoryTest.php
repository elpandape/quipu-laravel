<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tenancy\NoneTenantContext;
use ElPandaPe\QuipuLaravel\Tenancy\SpatieTenantContext;
use ElPandaPe\QuipuLaravel\Tenancy\StanclTenantContext;
use ElPandaPe\QuipuLaravel\Tenancy\TenancyNotImplementedException;
use ElPandaPe\QuipuLaravel\Tenancy\TenantContextFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\CustomEmitterConfigResolver;
use Illuminate\Contracts\Config\Repository;

it('con el driver "none" arma el NoneTenantContext', function (): void {
    config()->set('quipu.tenancy.driver', 'none');

    $factory = new TenantContextFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(NoneTenantContext::class);
});

it('sin driver configurado usa "none"', function (): void {
    config()->set('quipu.tenancy.driver');

    $factory = new TenantContextFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(NoneTenantContext::class);
});

it('con el driver "stancl" arma el StanclTenantContext', function (): void {
    config()->set('quipu.tenancy.driver', 'stancl');

    $factory = new TenantContextFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(StanclTenantContext::class);
});

it('con el driver "spatie" arma el SpatieTenantContext', function (): void {
    config()->set('quipu.tenancy.driver', 'spatie');

    $factory = new TenantContextFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(SpatieTenantContext::class);
});

it('con "auto" y stancl presente arma el StanclTenantContext', function (): void {
    config()->set('quipu.tenancy.driver', 'auto');

    $factory = new TenantContextFactory(app(), app(Repository::class), true, false);

    expect($factory->make())->toBeInstanceOf(StanclTenantContext::class);
});

it('con "auto" y solo spatie presente arma el SpatieTenantContext', function (): void {
    config()->set('quipu.tenancy.driver', 'auto');

    $factory = new TenantContextFactory(app(), app(Repository::class), false, true);

    expect($factory->make())->toBeInstanceOf(SpatieTenantContext::class);
});

it('con "auto" y sin ningún paquete instalado lanza un error claro', function (): void {
    config()->set('quipu.tenancy.driver', 'auto');

    $factory = new TenantContextFactory(app(), app(Repository::class), false, false);

    expect(fn(): \ElPandaPe\QuipuLaravel\Tenancy\TenantContext => $factory->make())
        ->toThrow(TenancyNotImplementedException::class, 'auto');
});

it('con un driver de clase custom queda mono-tenant (NoneTenantContext)', function (): void {
    // A custom EmitterConfigResolver driver owns its own scoping; the runtime
    // stays mono-tenant unless the app binds its own TenantContext.
    config()->set('quipu.tenancy.driver', CustomEmitterConfigResolver::class);

    $factory = new TenantContextFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(NoneTenantContext::class);
});
