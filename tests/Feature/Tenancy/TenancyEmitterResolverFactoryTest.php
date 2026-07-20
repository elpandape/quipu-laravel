<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Emitter\ConfigEmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Tenancy\SpatieTenantResolver;
use ElPandaPe\QuipuLaravel\Tenancy\StanclTenantResolver;
use ElPandaPe\QuipuLaravel\Tenancy\TenancyEmitterResolverFactory;
use ElPandaPe\QuipuLaravel\Tenancy\TenancyNotImplementedException;
use ElPandaPe\QuipuLaravel\Tests\Support\CustomEmitterConfigResolver;
use Illuminate\Contracts\Config\Repository;

it('con el driver "none" arma el ConfigEmitterConfigResolver', function (): void {
    config()->set('quipu.tenancy.driver', 'none');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(ConfigEmitterConfigResolver::class);
});

it('sin driver configurado usa "none"', function (): void {
    config()->set('quipu.tenancy.driver');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(ConfigEmitterConfigResolver::class);
});

it('con el driver "stancl" arma el StanclTenantResolver', function (): void {
    config()->set('quipu.tenancy.driver', 'stancl');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(StanclTenantResolver::class);
});

it('con el driver "spatie" arma el SpatieTenantResolver', function (): void {
    config()->set('quipu.tenancy.driver', 'spatie');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), true, true);

    expect($factory->make())->toBeInstanceOf(SpatieTenantResolver::class);
});

it('con "auto" y stancl presente arma el StanclTenantResolver', function (): void {
    config()->set('quipu.tenancy.driver', 'auto');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), true, false);

    expect($factory->make())->toBeInstanceOf(StanclTenantResolver::class);
});

it('con "auto" y solo spatie presente arma el SpatieTenantResolver', function (): void {
    config()->set('quipu.tenancy.driver', 'auto');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), false, true);

    expect($factory->make())->toBeInstanceOf(SpatieTenantResolver::class);
});

it('con "auto" y sin ningún paquete instalado lanza un error claro', function (): void {
    config()->set('quipu.tenancy.driver', 'auto');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), false, false);

    expect(fn(): \ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver => $factory->make())
        ->toThrow(TenancyNotImplementedException::class, 'auto');
});

it('con un driver que es una clase resolver lo resuelve del contenedor', function (): void {
    config()->set('quipu.tenancy.driver', CustomEmitterConfigResolver::class);

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), false, false);

    expect($factory->make())->toBeInstanceOf(CustomEmitterConfigResolver::class);
});

it('con un driver que es una clase pero no un resolver lanza un error claro', function (): void {
    config()->set('quipu.tenancy.driver', stdClass::class);

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), true, true);

    expect(fn(): \ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver => $factory->make())
        ->toThrow(TenancyNotImplementedException::class, stdClass::class);
});

it('con un driver desconocido lanza un error claro', function (): void {
    config()->set('quipu.tenancy.driver', 'marte');

    $factory = new TenancyEmitterResolverFactory(app(), app(Repository::class), true, true);

    expect(fn(): \ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver => $factory->make())
        ->toThrow(TenancyNotImplementedException::class, 'marte');
});
