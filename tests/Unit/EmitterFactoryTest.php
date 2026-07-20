<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Certificate\PathCertificateResolver;
use ElPandaPe\QuipuLaravel\Emitter\EmitterFactory;
use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use ElPandaPe\QuipuLaravel\Tests\Factory\EmitterConfigFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;

it('arma un emisor Quipu de Lite desde un certificado sin passphrase', function (): void {
    $factory = new EmitterFactory(new PathCertificateResolver(CertificateFile::plain()));

    expect($factory->make(EmitterConfigFactory::make()))->toBeInstanceOf(Quipu::class);
});

it('descifra la llave privada cuando hay passphrase configurada', function (): void {
    $factory = new EmitterFactory(new PathCertificateResolver(CertificateFile::encrypted('s3cr3t')));

    expect($factory->make(EmitterConfigFactory::make(certificatePassphrase: 's3cr3t')))
        ->toBeInstanceOf(Quipu::class);
});

it('falla con un mensaje claro cuando la passphrase es incorrecta', function (): void {
    $factory = new EmitterFactory(new PathCertificateResolver(CertificateFile::encrypted('s3cr3t')));

    expect(fn(): Quipu => $factory->make(EmitterConfigFactory::make(certificatePassphrase: 'incorrecta')))
        ->toThrow(EmitterConfigException::class);
});

it('falla con un mensaje claro cuando no puede leer el certificado', function (): void {
    $factory = new EmitterFactory(new PathCertificateResolver('/ruta/inexistente/certificado.pem'));

    expect(fn(): Quipu => $factory->make(EmitterConfigFactory::make()))
        ->toThrow(EmitterConfigException::class);
});

it('usa el PEM del EmitterConfig (multi-tenant) en vez del CertificateResolver global', function (): void {
    // The global CertificateResolver would fail if consulted; the emitter must
    // sign with the tenant's own PEM carried on the EmitterConfig instead.
    $factory = new EmitterFactory(new PathCertificateResolver('/ruta/inexistente/certificado.pem'));

    expect($factory->make(EmitterConfigFactory::make(certificatePem: CertificateFile::plainPem())))
        ->toBeInstanceOf(Quipu::class);
});
