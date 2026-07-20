<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('quipu.certificate.source', 'path');
    config()->set('quipu.certificate.path', CertificateFile::plain());
    Storage::fake('local');
});

it('se niega a correr sin Pro', function (): void {
    config()->set('quipu.pro', false);

    expect(Artisan::call('quipu:cert:inspect'))->toBe(1)
        ->and(Artisan::output())->toContain('requiere la edición Pro');
});

it('inspecciona el certificado configurado', function (): void {
    config()->set('quipu.pro', true);

    $exit = Artisan::call('quipu:cert:inspect');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('RUC: 20000000001')
        ->and($output)->toContain('2048 bits')
        ->and($output)->toContain('Clave privada: sí');
});

it('inspecciona un archivo PEM del disco', function (): void {
    config()->set('quipu.pro', true);
    Storage::disk('local')->put('certs/cert.pem', CertificateFile::plainPem());

    expect(Artisan::call('quipu:cert:inspect', ['file' => 'cert.pem', '--path' => 'certs']))->toBe(0)
        ->and(Artisan::output())->toContain('RUC: 20000000001');
});

it('falla cuando el archivo no existe', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:cert:inspect', ['file' => 'noexiste.pem']))->toBe(1)
        ->and(Artisan::output())->toContain('No se encontró');
});

it('falla cuando el PEM no es un certificado', function (): void {
    config()->set('quipu.pro', true);
    Storage::disk('local')->put('certs/bad.pem', 'esto no es un certificado');

    expect(Artisan::call('quipu:cert:inspect', ['file' => 'bad.pem', '--path' => 'certs']))->toBe(1)
        ->and(Artisan::output())->toContain('Certificado:');
});

it('falla cuando no se puede leer el certificado configurado', function (): void {
    config()->set('quipu.pro', true);
    config()->set('quipu.certificate.path', CertificateFile::missing());

    expect(Artisan::call('quipu:cert:inspect'))->toBe(1)
        ->and(Artisan::output())->toContain('Certificado:');
});
