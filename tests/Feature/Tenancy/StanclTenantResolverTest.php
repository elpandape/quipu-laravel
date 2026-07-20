<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Emitter\Environment;
use ElPandaPe\QuipuLaravel\Tenancy\StanclTenantResolver;
use ElPandaPe\QuipuLaravel\Tenancy\TenantEmitterResolutionException;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuLaravel\Tests\Support\StanclBareTenant;
use ElPandaPe\QuipuLaravel\Tests\Support\StanclTenantDouble;
use Illuminate\Contracts\Config\Repository;
use Stancl\Tenancy\Tenancy;

it('resuelve el EmitterConfig del tenant activo de stancl (identidad, credenciales y su PEM)', function (): void {
    config()->set('quipu.environment', 'produccion');
    config()->set('quipu.verify_tls', false);
    config()->set('quipu.endpoints.bill_service', 'https://proxy.example/billService');

    $tenancy = new Tenancy();
    $tenancy->tenant = new StanclTenantDouble();
    $tenancy->initialized = true;

    $config = new StanclTenantResolver(app(Repository::class), $tenancy)->resolve();

    expect($config->ruc)->toBe('20544332211')
        ->and($config->legalName)->toBe('TENANT STANCL SAC')
        ->and($config->tradeName)->toBe('STANCL')
        ->and($config->solUser)->toBe('STNCLUSER')
        ->and($config->solPass)->toBe('stnclpass')
        ->and($config->soapUsername())->toBe('20544332211STNCLUSER')
        ->and($config->certificatePem)->toBe(CertificateFile::plainPem())
        ->and($config->certificatePassphrase)->toBeNull()
        ->and($config->environment)->toBe(Environment::Production)
        ->and($config->verifyTls)->toBeFalse()
        ->and($config->billServiceEndpointOverride)->toBe('https://proxy.example/billService')
        // This tenant declares its own 8% MYPE rate, overriding the global default.
        ->and($config->igvRate)->toBe(8.0);
});

it('lanza un error claro cuando stancl no tiene un tenant activo', function (): void {
    $tenancy = new Tenancy();

    expect(fn(): \ElPandaPe\QuipuLaravel\Emitter\EmitterConfig => new StanclTenantResolver(app(Repository::class), $tenancy)->resolve())
        ->toThrow(TenantEmitterResolutionException::class, 'stancl');
});

it('lanza un error claro cuando el tenant de stancl no implementa ProvidesQuipuEmitter', function (): void {
    $tenancy = new Tenancy();
    $tenancy->tenant = new StanclBareTenant();
    $tenancy->initialized = true;

    expect(fn(): \ElPandaPe\QuipuLaravel\Emitter\EmitterConfig => new StanclTenantResolver(app(Repository::class), $tenancy)->resolve())
        ->toThrow(TenantEmitterResolutionException::class, StanclBareTenant::class);
});
