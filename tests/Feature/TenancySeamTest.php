<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Emitter\ConfigEmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Tenancy\SpatieTenantResolver;
use ElPandaPe\QuipuLaravel\Tenancy\StanclTenantResolver;
use ElPandaPe\QuipuLaravel\Tenancy\TenancyNotImplementedException;

it('expone el driver de tenancy "none" por defecto (mono-tenant)', function (): void {
    expect(config('quipu.tenancy.driver'))->toBe('none');
});

it('con el driver "none" resuelve el resolver de un solo emisor', function (): void {
    expect(app(EmitterConfigResolver::class))->toBeInstanceOf(ConfigEmitterConfigResolver::class);
});

it('con el driver "stancl" resuelve el StanclTenantResolver', function (): void {
    config()->set('quipu.tenancy.driver', 'stancl');

    expect(app(EmitterConfigResolver::class))->toBeInstanceOf(StanclTenantResolver::class);
});

it('con el driver "spatie" resuelve el SpatieTenantResolver', function (): void {
    config()->set('quipu.tenancy.driver', 'spatie');

    expect(app(EmitterConfigResolver::class))->toBeInstanceOf(SpatieTenantResolver::class);
});

it('con el driver "auto" detecta el paquete de tenancy instalado', function (): void {
    config()->set('quipu.tenancy.driver', 'auto');

    // Both packages are installed in dev; stancl is detected first.
    expect(app(EmitterConfigResolver::class))->toBeInstanceOf(StanclTenantResolver::class);
});

it('con un driver desconocido lanza un error claro de tenancy', function (): void {
    config()->set('quipu.tenancy.driver', 'desconocido');

    expect(fn() => app(EmitterConfigResolver::class))
        ->toThrow(TenancyNotImplementedException::class, 'desconocido');
});
