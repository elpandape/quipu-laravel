<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Quipu as QuipuEmitter;
use ElPandaPe\QuipuLaravel\Emitter\ConfigEmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Emitter\Environment;
use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use ElPandaPe\QuipuLaravel\Facades\Quipu as QuipuFacade;
use ElPandaPe\QuipuLaravel\QuipuServiceProvider;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use Illuminate\Support\ServiceProvider;

it('registra el emisor Quipu como singleton en el contenedor', function (): void {
    config()->set('quipu.certificate.path', CertificateFile::plain());

    $emitter = app(QuipuEmitter::class);

    expect($emitter)->toBeInstanceOf(QuipuEmitter::class)
        ->and(app(QuipuEmitter::class))->toBe($emitter);
});

it('usa el ConfigEmitterConfigResolver por defecto', function (): void {
    expect(app(EmitterConfigResolver::class))->toBeInstanceOf(ConfigEmitterConfigResolver::class);
});

it('resuelve el mismo emisor a través del facade', function (): void {
    config()->set('quipu.certificate.path', CertificateFile::plain());

    expect(QuipuFacade::getFacadeRoot())->toBe(app(QuipuEmitter::class));
});

it('publica el archivo de configuración bajo la etiqueta quipu-config', function (): void {
    $paths = ServiceProvider::pathsToPublish(QuipuServiceProvider::class, 'quipu-config');

    expect($paths)->toHaveCount(1);

    $target = array_values($paths)[0];

    expect(is_string($target) && str_ends_with($target, 'config/quipu.php'))->toBeTrue();
});

it('expone la configuración por defecto del emisor', function (): void {
    expect(config('quipu.environment'))->toBe('beta')
        ->and(config('quipu.verify_tls'))->toBeTrue()
        ->and(config('quipu.pro'))->toBe('auto');
});

it('lee la configuración del emisor desde el repositorio de config', function (): void {
    config()->set('quipu.emisor.ruc', '20123456789');
    config()->set('quipu.emisor.sol_user', 'USER01');
    config()->set('quipu.emisor.sol_pass', 'clave01');
    config()->set('quipu.environment', 'produccion');
    config()->set('quipu.verify_tls', false);
    config()->set('quipu.endpoints.bill_service', 'https://proxy.example/billService');
    config()->set('quipu.certificate.path', CertificateFile::plain());
    config()->set('quipu.certificate.passphrase', 'abc');

    $config = app(EmitterConfigResolver::class)->resolve();

    expect($config->ruc)->toBe('20123456789')
        ->and($config->solUser)->toBe('USER01')
        ->and($config->solPass)->toBe('clave01')
        ->and($config->environment)->toBe(Environment::Production)
        ->and($config->verifyTls)->toBeFalse()
        ->and($config->billServiceEndpointOverride)->toBe('https://proxy.example/billService')
        ->and($config->certificatePassphrase)->toBe('abc')
        ->and($config->soapUsername())->toBe('20123456789USER01');
});

it('lee la razón social y el nombre comercial del emisor', function (): void {
    config()->set('quipu.emisor.ruc', '20123456789');
    config()->set('quipu.emisor.legal_name', 'ACME CORP SAC');
    config()->set('quipu.emisor.trade_name', 'ACME');

    $config = app(EmitterConfigResolver::class)->resolve();

    expect($config->legalName)->toBe('ACME CORP SAC')
        ->and($config->tradeName)->toBe('ACME');
});

it('usa el RUC como razón social cuando falta legal_name', function (): void {
    config()->set('quipu.emisor.ruc', '20123456789');

    $config = app(EmitterConfigResolver::class)->resolve();

    expect($config->legalName)->toBe('20123456789')
        ->and($config->tradeName)->toBeNull();
});

it('deja el override de endpoint y la passphrase en null cuando no están configurados', function (): void {
    $config = app(EmitterConfigResolver::class)->resolve();

    expect($config->billServiceEndpointOverride)->toBeNull()
        ->and($config->certificatePassphrase)->toBeNull()
        ->and($config->environment)->toBe(Environment::Beta)
        ->and($config->igvRate)->toBe(18.0);
});

it('rechaza un entorno desconocido con un error claro', function (): void {
    config()->set('quipu.environment', 'marte');

    expect(fn() => app(EmitterConfigResolver::class)->resolve())
        ->toThrow(EmitterConfigException::class);
});

it('resuelve la tasa de IGV global configurada', function (): void {
    config()->set('quipu.igv_rate', 8.0);

    expect(app(EmitterConfigResolver::class)->resolve()->igvRate)->toBe(8.0);
});

it('rechaza una tasa de IGV no numérica con un error claro', function (): void {
    config()->set('quipu.igv_rate', 'no-es-un-numero');

    expect(fn() => app(EmitterConfigResolver::class)->resolve())
        ->toThrow(EmitterConfigException::class, 'debe ser numérica');
});

it('arma el emisor a partir de un certificado con passphrase', function (): void {
    config()->set('quipu.certificate.path', CertificateFile::encrypted('s3cr3t'));
    config()->set('quipu.certificate.passphrase', 's3cr3t');

    expect(app(QuipuEmitter::class))->toBeInstanceOf(QuipuEmitter::class);
});
