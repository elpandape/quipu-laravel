<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Emitter\Environment;
use ElPandaPe\QuipuLaravel\Tenancy\SpatieTenantResolver;
use ElPandaPe\QuipuLaravel\Tenancy\TenantEmitterResolutionException;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuLaravel\Tests\Support\SpatieTenantDouble;
use Illuminate\Contracts\Config\Repository;
use Spatie\Multitenancy\Models\Tenant;

beforeEach(function (): void {
    // spatie's Tenant::current() reads the current tenant from this container
    // key; in production spatie's provider sets it (default "currentTenant").
    config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
});

it('resuelve el EmitterConfig del tenant actual de spatie (identidad, credenciales y su PEM)', function (): void {
    config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);
    config()->set('quipu.environment', 'beta');
    config()->set('quipu.verify_tls', true);

    app()->instance('currentTenant', new SpatieTenantDouble());

    $config = new SpatieTenantResolver(app(Repository::class))->resolve();

    expect($config->ruc)->toBe('20655443322')
        ->and($config->legalName)->toBe('TENANT SPATIE SAC')
        ->and($config->tradeName)->toBe('SPATIE')
        ->and($config->solUser)->toBe('SPTUSER')
        ->and($config->solPass)->toBe('sptpass')
        ->and($config->soapUsername())->toBe('20655443322SPTUSER')
        ->and($config->certificatePem)->toBe(CertificateFile::plainPem())
        ->and($config->certificatePassphrase)->toBeNull()
        ->and($config->environment)->toBe(Environment::Beta)
        ->and($config->verifyTls)->toBeTrue()
        // This tenant declares no rate of its own, so it falls back to the global default.
        ->and($config->igvRate)->toBe(18.0);
});

it('lanza un error claro cuando spatie no tiene un tenant actual', function (): void {
    // No tenant bound and no tenant_model configured: falls back to spatie's
    // default model, whose current() is null.
    expect(fn(): \ElPandaPe\QuipuLaravel\Emitter\EmitterConfig => new SpatieTenantResolver(app(Repository::class))->resolve())
        ->toThrow(TenantEmitterResolutionException::class, 'spatie');
});

it('lanza un error claro cuando el tenant de spatie no implementa ProvidesQuipuEmitter', function (): void {
    app()->instance('currentTenant', new Tenant());

    expect(fn(): \ElPandaPe\QuipuLaravel\Emitter\EmitterConfig => new SpatieTenantResolver(app(Repository::class))->resolve())
        ->toThrow(TenantEmitterResolutionException::class, Tenant::class);
});
