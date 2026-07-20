<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;
use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use Illuminate\Support\Facades\Storage;

it('arma el emisor con la fuente path (por defecto)', function (): void {
    config()->set('quipu.certificate.source', 'path');
    config()->set('quipu.certificate.path', CertificateFile::plain());

    expect(app(CertificateResolver::class)->resolvePem())->toBe(CertificateFile::plainPem())
        ->and(app(Quipu::class))->toBeInstanceOf(Quipu::class);
});

it('arma el emisor con la fuente inline (PEM en base64)', function (): void {
    config()->set('quipu.certificate.source', 'inline');
    config()->set('quipu.certificate.inline', CertificateFile::plainBase64());

    expect(app(CertificateResolver::class)->resolvePem())->toBe(CertificateFile::plainPem())
        ->and(app(Quipu::class))->toBeInstanceOf(Quipu::class);
});

it('arma el emisor con la fuente disk (Storage::fake)', function (): void {
    Storage::fake('certs');
    Storage::disk('certs')->put('sunat/cert.pem', CertificateFile::plainPem());
    config()->set('quipu.certificate.source', 'disk');
    config()->set('quipu.certificate.disk', 'certs');
    config()->set('quipu.certificate.path', 'sunat/cert.pem');

    expect(app(CertificateResolver::class)->resolvePem())->toBe(CertificateFile::plainPem())
        ->and(app(Quipu::class))->toBeInstanceOf(Quipu::class);
});

it('rechaza una fuente de certificado desconocida con un error claro', function (): void {
    config()->set('quipu.certificate.source', 'ftp');

    expect(fn(): CertificateResolver => app(CertificateResolver::class))
        ->toThrow(EmitterConfigException::class, 'no es válida');
});

it('falla cuando el certificado inline no está configurado', function (): void {
    config()->set('quipu.certificate.source', 'inline');
    config()->set('quipu.certificate.inline', '');

    expect(fn(): string => app(CertificateResolver::class)->resolvePem())
        ->toThrow(EmitterConfigException::class);
});

it('falla cuando el certificado inline no es base64 válido', function (): void {
    config()->set('quipu.certificate.source', 'inline');
    config()->set('quipu.certificate.inline', '@@@no-es-base64@@@');

    expect(fn(): string => app(CertificateResolver::class)->resolvePem())
        ->toThrow(EmitterConfigException::class);
});

it('falla cuando la ruta del certificado en el disco no está configurada', function (): void {
    Storage::fake('certs');
    config()->set('quipu.certificate.source', 'disk');
    config()->set('quipu.certificate.disk', 'certs');
    config()->set('quipu.certificate.path', '');

    expect(fn(): string => app(CertificateResolver::class)->resolvePem())
        ->toThrow(EmitterConfigException::class);
});

it('falla cuando el certificado no existe en el disco', function (): void {
    Storage::fake('certs');
    config()->set('quipu.certificate.source', 'disk');
    config()->set('quipu.certificate.disk', 'certs');
    config()->set('quipu.certificate.path', 'sunat/ausente.pem');

    expect(fn(): string => app(CertificateResolver::class)->resolvePem())
        ->toThrow(EmitterConfigException::class);
});
