<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('quipu.emisor.ruc', '20000000001');
    config()->set('quipu.emisor.sol_user', 'MODDATOS');
    config()->set('quipu.emisor.sol_pass', 'moddatos');
    config()->set('quipu.certificate.path', CertificateFile::plain());
    Http::fake(['*' => Http::response('ok', 200)]);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('reporta todo en orden', function (): void {
    $exit = Artisan::call('quipu:doctor');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('Certificado: vigente hasta')
        ->and($output)->toContain('Conectividad: SUNAT respondió');
});

it('falla cuando falta configuración', function (): void {
    config()->set('quipu.emisor.ruc', '');
    config()->set('quipu.emisor.sol_user', '');
    config()->set('quipu.emisor.sol_pass', '');

    expect(Artisan::call('quipu:doctor'))->toBe(1)
        ->and(Artisan::output())->toContain('Configuración: falta RUC, usuario SOL, clave SOL.');
});

it('falla cuando el certificado no existe', function (): void {
    config()->set('quipu.certificate.path', CertificateFile::missing());

    expect(Artisan::call('quipu:doctor'))->toBe(1);
});

it('falla cuando el certificado tiene un formato inválido', function (): void {
    config()->set('quipu.certificate.path', CertificateFile::invalid());

    expect(Artisan::call('quipu:doctor'))->toBe(1)
        ->and(Artisan::output())->toContain('formato inválido');
});

it('falla cuando el certificado venció', function (): void {
    CarbonImmutable::setTestNow('2037-01-01');

    expect(Artisan::call('quipu:doctor'))->toBe(1)
        ->and(Artisan::output())->toContain('Certificado: venció el');
});

it('falla cuando no hay conectividad', function (): void {
    Http::fake(fn(): never => throw new ConnectionException('timeout'));

    expect(Artisan::call('quipu:doctor'))->toBe(1)
        ->and(Artisan::output())->toContain('no se pudo alcanzar');
});

it('falla con un entorno inválido', function (): void {
    config()->set('quipu.environment', 'marte');

    expect(Artisan::call('quipu:doctor'))->toBe(1)
        ->and(Artisan::output())->toContain('Configuración inválida');
});
